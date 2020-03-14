<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\OrderLine;
use Newelement\Shoppe\Models\OrderNote;
use Newelement\Shoppe\Traits\Transactions;
use Newelement\Neutrino\Models\ActivityLog;
use Auth;

class OrderController extends Controller
{

    use Transactions;

    public function index(Request $request)
    {
        $orders = Order::orderBy('status', 'asc')->orderBy('created_at', 'asc')->paginate(20);

        if( $request->s && strlen($request->s) ){
            $orders = Order::search($request->s)->sortable('status')->orderBy('created_at', 'asc')->paginate(20);
        } else {
            $orders = Order::sortable('status')->orderBy('created_at', 'asc')->paginate(20);
        }

        if( $request->ajax() ){
            return response()->json(['orders' => $orders]);
        } else {
            return view('shoppe::admin.orders.index', ['orders' => $orders]);
        }
    }

    public function get(Request $request, Order $order)
    {
        if( !$order ){
            abort(404);
        }

        $paymentConnector = app('Payment');
        $order->transaction_details = $paymentConnector->getCharge( $order->transaction_id );

        if( $request->ajax() ){
            return response()->json($order);
        } else {
            return view('shoppe::admin.orders.edit', ['order' => $order]);
        }
    }

    public function getShippingLabel(Order $order)
    {
        $shipping = app('Shipping');
        $shippingInfo = $shipping->getShippingLabel( $order->shipping_object_id );

        $order->tracking_number = $shippingInfo['tracking_number'];
        $order->label_url = isset( $shippingInfo['label_url'] ) ? $shippingInfo['label_url'] : null;
        $order->tracking_url = isset( $shippingInfo['tracking_url'] )? $shippingInfo['tracking_url'] : null;
        $order->save();

        return response()->json($shippingInfo);

    }

    public function updateStatus(Request $request, Order $order)
    {
        $validArr = [
            'status' => 'required'
        ];
        if( (int) $request->status === 4 || (int) $request->status === 86 ){
           $validArr['notes'] = 'required';
        }
        $validatedData = $request->validate($validArr);

        if( !$order ){
            abort(404);
        }

        $status = (int) $request->status;
        $refundOrder = (bool) $request->refund_order? true : false;

        switch($status){
            case 2: // ORDER HOLD
                // Mail::send();
            break;
            case 3: // ORDER COMPLETE
                $order->complete_order_sent = 1;
                $order->shipped_on = now();
                // Mail::send();
            break;
            case 4: // REFUNDED
                $order->complete_order_sent = 1;
                return $this->refundOrder($request, $order);
            break;
            case 86: // ORDER CANCELED
                if( $refundOrder){
                    return $this->refundOrder($request, $order);
                }
                // Mail::send();
            break;
        }

        $order->updated_by = Auth::user()->id;
        $order->updated_at = now();
        $order->status = $status;
        $order->save();

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'order.status',
            'object_type' => 'order',
            'object_id' => $order->id,
            'content' => 'Status was updated to '.$order->status_formatted.' on order '.$order->id,
            'log_level' => 0,
            'created_by' => auth()->user()->id,
            'created_at' => now()
        ]);

        if( $request->ajax() ){
            return response()->json($order);
        } else {
            return redirect()->route('shoppe.orders', [$order])->with('success', 'Order status updated.');
        }

    }

    public function refundOrder(Request $request, Order $order)
    {

        if( !$order){
            abort(404);
        }

        $orderTotal = getOrderTotal( $order );
        $paymentConnector = app('Payment');
        $taxConnector = app('Taxes');
        $inventoryConnector = app('Inventory');

        $shippingAddress = $order->shippingAddress;
        $address = [
                'name' => $shippingAddress->name,
                'street1' => $shippingAddress->address,
                'street2' => $shippingAddress->address2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'zip' => $shippingAddress->zipcode,
                'country' => $shippingAddress->country
            ];

        $arr = [
            'transaction_id' => $order->transaction_id,
            'amount' => $orderTotal,
            'tax_amount' => 0.00,
            'shipping_amount' => 0.00
        ];

        $response = $paymentConnector->createRefund( $arr );

        if( $response['success'] ){

            // INSERT TRANSACTION LOG
            $transArr = [
                'type' => 'credit',
                'amount' => $orderTotal,
                'tax_amount' => (float) $order->tax_amount,
                'shipping_amount' => (float) $order->shipping_amount,
                'order_id' => $order->id,
                'transaction_id' => $response['transaction_id'],
                'notes' => $request->notes,
                'user_id' => Auth::user()->id
            ];

            $order->status = 4;
            $order->save();

            $this->createTransaction( $transArr );

            // Return QTY back into stock
            if( getShoppeSetting('manage_stock') ){
                foreach( $order->orderLines as $orderLine ){
                    if( $orderLine->qty ){
                        $qty = ( $orderLine->qty - $orderLine->returned_qty );
                        $inventoryConnector->addStock($orderLine->product->product_type, $qty, $orderLine->product_id, $orderLine->variation_id);
                    }
                }
            }

            // We need to also refund the tax transaction
            if(  method_exists($taxConnector, 'createRefund') && $order->tax_object_id && $order->tax_amount ){
                $taxObjectId = $order->tax_object_id;

                $arr = [
                    'amount' => (float) $orderTotal,
                    'tax_amount' => (float) $order->tax_amount,
                    'shipping_amount' => (float) $order->shipping_mount,
                    'tax_object_id' => $taxObjectId,
                    'shipping_address' => $address,
                ];

                $taxRefund = $taxConnector->createRefund( $arr );

                if( !$taxRefund['success'] ){
                    // Log it, possibly email it
                    ActivityLog::insert([
                        'activity_package' => 'shoppe',
                        'activity_group' => 'order.tax.refund',
                        'object_type' => 'order',
                        'object_id' => $order->id,
                        'content' => $taxRefund['message'],
                        'log_level' => 5,
                        'created_by' => auth()->user()->id,
                        'created_at' => now()
                    ]);
                }

            }

            $order->updated_by = Auth::user()->id;
            $order->updated_at = now();
            $order->save();



            // Log
            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'order.refund',
                'object_type' => 'order',
                'object_id' => $order->id,
                'content' => 'Order '.$order->id.' was refunded.',
                'log_level' => 0,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

            if( $request->ajax() ){
                return response()->json(['success' => $response['success'], 'message' => 'Successful']);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('success', 'Order refunded.' );
            }

        } else {

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'order.refund',
                'object_type' => 'order',
                'object_id' => $order->id,
                'content' => 'Order '.$order->id.' was NOT refunded. '.$response['message'],
                'log_level' => 5,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

            if( $request->ajax() ){
                return response()->json(['success' => $response['success'], 'message' => $response['message']], 500);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('error', $response['message'] );
            }
        }
    }

    public function refundOrderLine(Request $request, OrderLine $orderLine)
    {
        if( !$orderLine ){
            abort(404);
        }

        $validatedData = $request->validate([
            'ref_id' => 'required',
            'notes' => 'required',
            'qty' => 'required'
        ]);

        $refId = $request->ref_id;
        $qty = (int) $request->qty;
        $notes = $request->notes;
        $shippingAmount = (float) ( $request->shipping_amount )? $request->shipping_amount : 0.00;
        $taxes = 0.00;

        if( $qty > ( $orderLine->qty - $orderLine->returned_qty )  ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => 'QTY cannot exceed QTY on line'], 500);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('error', 'QTY cannot exceed QTY on line');
            }
        }

        $order = Order::where('ref_id', $refId)->first();

        if( !$order ){
            abort(404);
        }

        $shippingAddress = $order->shippingAddress;
        $address = [
                'name' => $shippingAddress->name,
                'street1' => $shippingAddress->address,
                'street2' => $shippingAddress->address2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'zip' => $shippingAddress->zipcode,
                'country' => $shippingAddress->country
            ];

        if( $shippingAmount > $order->shipping_amount  ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => 'Shipping amount cannot exceed order shipping amount'], 500);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('error', 'Shipping amount cannot exceed order shipping amount');
            }
        }

        $lineTotal = $orderLine->price * $qty;
        if( $orderLine->product->is_taxable ){
            $taxes = $lineTotal * $order->tax_rate;
            $taxes = round( $taxes, 2);
        }

        $amount = $lineTotal;


        $orderTotal = getOrderTotal( $order );

        if( $amount > $orderTotal  ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => 'Refunded amount ( '.$amount.' ) cannot exceed order total ( '.$orderTotal.' )'], 500);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('error', 'Refunded amount ( '.$amount.' ) cannot exceed order total ( '.$orderTotal.' )');
            }
        }

        $arr = [
            'transaction_id' => $order->transaction_id,
            'amount' => $amount,
            'tax_amount' => $taxes,
            'shipping_amount' => $shippingAmount
        ];

        $paymentConnector = app('Payment');
        $taxConnector = app('Taxes');
        $inventoryConnector = app('Inventory');

        $response = $paymentConnector->createRefund( $arr );

        if( $response['success'] ){

            $transactionId = $response['transaction_id'];

            // INSERT TRANSACTION LOG
            $transArr = [
                'type' => 'credit',
                'amount' => $amount + $shippingAmount + $taxes,
                'tax_amount' => $taxes,
                'shipping_amount' => $shippingAmount,
                'qty' => $qty,
                'order_id' => $order->id,
                'line_id' => $orderLine->id,
                'transaction_id' => $transactionId,
                'notes' => $notes,
                'user_id' => Auth::user()->id
            ];
            $this->createTransaction( $transArr );

            // Return QTY back into stock
            if( getShoppeSetting('manage_stock') && $qty ){
                $inventoryConnector->addStock($orderLine->product->product_type, $qty, $orderLine->product_id, $orderLine->variation_id);
            }

            if( $qty === ( $orderLine->qty - $order->returned_qty )  ){
                $orderLine->status = 4;
                $orderLine->save();
            }

            // Update order info
            $order->updated_by = Auth::user()->id;
            $order->updated_at = now();
            $order->save();


            // We need to also refund the tax transaction
            if(  method_exists($taxConnector, 'createRefund') && $order->tax_object_id && $taxes ){
                $taxObjectId = $order->tax_object_id;

                $arr = [
                    'amount' => (float) $amount,
                    'tax_amount' => (float) $taxes,
                    'shipping_amount' => (float) $shippingAmount,
                    'tax_object_id' => $taxObjectId,
                    'shipping_address' => $address,
                ];

                $taxRefund = $taxConnector->createRefund( $arr );

                if( !$taxRefund['success'] ){
                    ActivityLog::insert([
                        'activity_package' => 'shoppe',
                        'activity_group' => 'order.tax.refund',
                        'object_type' => 'order',
                        'object_id' => $order->id,
                        'content' => $taxRefund['message'],
                        'log_level' => 5,
                        'created_by' => auth()->user()->id,
                        'created_at' => now()
                    ]);
                }

            }

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'order.line.refund',
                'object_type' => 'order',
                'object_id' => $order->id,
                'content' => 'Refund on order '.$order->id.', line '.$orderLine->id.' was successful. Amount: '.$amount,
                'log_level' => 0,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

            if( $request->ajax() ){
                $request->session()->flash('success', 'Refund was successful.');
                return response()->json(['success' => $response['success'], 'message' => $response['message'], 'transaction_id' => $response['transaction_id'], 'payload' => $response['payload']]);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('success', $response['message']);
            }
        } else {

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'order.line.refund',
                //'object_type' => 'cart',
                //'object_id' => $cart->id,
                'content' => $response['message'],
                'log_level' => 5,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

            if( $request->ajax() ){
                return response()->json(['success' => $response['success'], 'message' => $response['message']], 500);
            } else {
                return redirect()->route('shoppe.orders', [$order])->with('error', $response['message']);
            }
        }

    }

    public function createNote(Request $request, Order $order)
    {
        $validatedData = $request->validate([
            'note' => 'required'
        ]);

        if( !$order ){
            abort(404);
        }

        $ordernote = $request->note;
        $public = $request->allow_public? 1 : 0;

        $note = new OrderNote;
        $note->order_id = $order->id;
        $note->notes = $ordernote;
        $note->public = $public;
        $note->save();

        if($public){
            $email = $order->user->email;
            // Notify user that a public note was entered
        }

        $note->notes = nl2br($ordernote);
        $user = $note->createdUser;
        $note->user = $user->name;
        $note->created = $note->created_at->timezone(config('neutrino.timezone'))->format('M j, Y g:i a');

        ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'order.note',
                'object_type' => 'order',
                'object_id' => $order->id,
                'content' => 'Note created on order '.$order->id,
                'log_level' => 0,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

        if( $request->ajax() ){
            return response()->json([ 'note' => $note ]);
        } else {
            return redirect()->back()->with('success', 'Note added.');
        }

    }

    public function resendReceipt(Request $request, Order $order)
    {
        if( !$order ){
            abort(404);
        }

        $email = $order->user->email;
        $sent = true;

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'order.receipt',
            'object_type' => 'order',
            'object_id' => $order->id,
            'content' => 'Resent receipt on order '.$order->id,
            'log_level' => 0,
            'created_by' => auth()->user()->id,
            'created_at' => now()
        ]);

        return response()->json(['sent' => $sent]);
    }

}
