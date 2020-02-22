<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\Page;
use Newelement\Shoppe\Traits\CartData;
use Newelement\Shoppe\Models\AddressBook;
use Newelement\Shoppe\Models\Customer;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\OrderLine;
use Newelement\Shoppe\Traits\Transactions;
use Illuminate\Support\Facades\Hash;
use Auth;

class CheckoutController extends Controller
{
    use CartData, Transactions;

    public function __construct()
    {}

    public function index(Request $request)
    {
        $cartItems = $this->getCartItems();
        $data = Page::where('slug', 'checkout')->first();
        $data->data_type = 'page';
        $data->items = $cartItems['items'];
        $data->sub_total = $cartItems['sub_total'];

        $data->shipping_addresses = AddressBook::where(
                                    [
                                        'address_type' => 'shipping',
                                        'user_id' => auth()->user()? auth()->user()->id : 0
                                    ])
                                    ->orderBy('default', 'desc')
                                    ->orderBy('address', 'desc')
                                    ->get();

        if( $request->ajax() ){
            return response()->json($data);
        } else {
            return view('shoppe::checkout', ['data' => $data]);
        }
    }

    /*
    * PROCESS CHECKOUT
    *
    *
    */
    public function processCheckout(Request $request)
    {
        //Validate initial data
        $validateArr = []; /*[
            'title' => 'required|unique:posts|max:255',
            'body' => 'required',
            ]*/;
        $validatedData = $request->validate(
            $validateArr
        );

        $paymentConnector = app('Payment');
        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');

        $email = $request->email;
        $billing_name = $request->cc_name;
        $cart = $this->getCartItems();
        $savedShipping = $request->shipping_address_option && $request->shipping_address_option !== 'new_shipping_address'? $request->shipping_address_option : false;
        $shippingAddress = [];
        $items = $cart['items'];
        $eligibleShipping = true;
        $subTotal = $cart['sub_total'];
        $taxableTotal = $cart['taxable_total'];
        $refId = sha1( uniqid().microtime().$subTotal.$email.env('APP_KEY') );
        $user = Customer::createOrGet( $billing_name, $email );
        $checkout = [];

        // Get request params
        $token = $request->token;
        $saveCard = $request->save_card? true : false;

        // Start the checkout array
        $checkout['customer_name'] = $billing_name;
        $checkout['email'] = $email;
        $checkout['token'] = $token;
        $checkout['ref_id'] = $refId;
        $checkout['save_card'] = $saveCard;
        $checkout['description'] = $refId;
        $checkout['ref_id'] = $refId;
        $checkout['items'] = $cart['items'];
        $checkout['sub_total'] = $subTotal;
        $checkout['taxable_total'] = $taxableTotal;
        $checkout['shipping_service_id'] = $request->shipping_rate? $request->shipping_rate : false ;


        /*
        * Shipping
        *
        *
        */
        if( $savedShipping ){
            $savedAddress = AddressBook::find($savedShipping);
            $shippingAddress = [
                'name' => $savedAddress->name,
                'company_name' => $request->company_name,
                'street1' => $savedAddress->address,
                'street2' => $savedAddress->address2,
                'city' => $savedAddress->city,
                'state' => $savedAddress->state,
                'zip' => $savedAddress->zipcode,
                'country' => $savedAddress->country,
                'email' => $email
            ];
        } else {
            $shippingAddress = [
                'name' => $request->shipping_name,
                'company_name' => $request->shipping_company_name,
                'street1' => $request->shipping_address,
                'street2' => $request->shipping_address2,
                'city' => $request->shipping_city,
                'state' => $request->shipping_state,
                'zip' => $request->shipping_zipcode,
                'country' => $request->shipping_country,
                'email' => $email
            ];
        }

        $checkout['shipping_address'] = $shippingAddress;

        $rate = $shippingConnector->getShippingRates( $checkout );
        $checkout['shipping_connector'] = $shippingConnector->connector_name;

        if( !$rate['success'] ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $rate['message']], 500);
            } else {
                return back()->with('error', $rate['message']);
            }
        }

        // SAVE CUSTOMER'S SHIPPING ADDRESS
        if( $eligibleShipping && !$savedShipping ){
            $address = AddressBook::checkExistingAddress($user, $shippingAddress, 'SHIPPING');
            if( !$address ){
                $address = new AddressBook;
                $address->user_id = $user->id;
                $address->address_type = 'SHIPPING';
                $address->name = $shippingAddress['name'];
                $address->company_name = $shippingAddress['company_name'];
                $address->address = $shippingAddress['street1'];
                $address->address2 = $shippingAddress['street2'];
                $address->city = $shippingAddress['city'];
                $address->state = $shippingAddress['state'];
                $address->zipcode = $shippingAddress['zip'];
                $address->country = $shippingAddress['country'];
                $address->save();
            }
        }

        $checkout['shipping_amount'] = $rate['rates']['amount'];
        $checkout['shipping_carrier'] = $rate['rates']['carrier'];
        $checkout['shipping_service'] = $rate['rates']['service'];
        $checkout['shipping_service_id'] = $rate['rates']['service_id'];
        $checkout['shipping_est_days'] = $rate['rates']['estimated_days'];
        $checkout['shipping_object_id'] = isset($rate['rates']['object_id'])? $rate['rates']['object_id'] : null ;


        /*
        * Taxes
        *
        *
        */
        $taxes = $taxesConnector->getTaxes( $checkout );
        $checkout['tax_connector'] = $taxesConnector->connector_name;

        if( !$taxes['success'] ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $taxes['message']], 500);
            } else {
                return back()->with('error', $taxes['message']);
            }
        }

        $checkout['tax_amount'] = $taxes['tax_amount'];
        $checkout['tax_rate'] = $taxes['tax_rate'];


        /*
        * Totals and Charge
        *
        *
        */
        $amount = (float) $subTotal + (float) $checkout['shipping_amount'] + (float) $checkout['tax_amount'];
        $checkout['amount'] = $amount;

        // THE CHARGE
        $paymentConnector->email = strtolower($checkout['email']);
        $charge = $paymentConnector->charge( $checkout );

        $checkout['payment_connector'] = $paymentConnector->connector_name;

        if( !$charge['success'] ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $charge['message']], 500);
            } else {
                return back()->with('error', $charge['message']);
            }
        }

        $checkout['transaction_id'] = $charge['transaction_id'];


        /*
        * Create customer
        *
        *
        */
        if( $saveCard ){
            $checkout['customer_id'] = $charge['customer_id'];
            $savedCard = Customer::saveCard( $checkout, $user );
        }


        /*
        * Insert order
        *
        *
        */
        try{
            $order = new Order;
            $order->ref_id = $refId;
            $order->user_id = Auth::check()? Auth::user()->id : $user->id;
            $order->transaction_id = isset($checkout['transaction_id'])? $checkout['transaction_id'] : null;
            $order->payment_connector = isset($checkout['payment_connector'])? $checkout['payment_connector'] : null;
            $order->shipping_connector = isset($checkout['shipping_connector'])? $checkout['shipping_connector'] : null;
            $order->tax_connector = isset($checkout['tax_connector'])? $checkout['tax_connector'] : null;
            $order->status = 1;
            if( $eligibleShipping ){
                $order->address_book_id = $savedShipping? $savedShipping : $address->id;
            }
            $order->carrier = isset( $checkout['shipping_carrier'] )? $checkout['shipping_carrier'] : null;
            $order->shipping_service = isset( $checkout['shipping_service'] )? $checkout['shipping_service'] : null;
            $order->shipping_id = isset( $checkout['shipping_service_id'] )? $checkout['shipping_service_id'] : null;
            $order->shipping_object_id = isset( $checkout['shipping_object_id'] )? $checkout['shipping_object_id'] : null;
            $order->shipping_amount = isset( $checkout['shipping_amount'] )? $checkout['shipping_amount'] : 0.00;
            $order->tax_amount = isset( $checkout['tax_amount'] )? $checkout['tax_amount'] : 0.00;
            $order->tax_rate = isset( $checkout['tax_rate'] )? $checkout['tax_rate'] : 0.00;
            $order->discount_code = isset( $checkout['discount_code'] )? $checkout['discount_code'] : null;
            $order->discount_amount = isset( $checkout['discount_amount'] )? $checkout['discount_amount'] : 0.00;
            $order->created_by = Auth::check()? Auth::user()->id : $user->id;
            $order->updated_by = Auth::check()? Auth::user()->id : $user->id;
            $order->save();
        } catch ( \Exception $e  ){

            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' =>  $e->getMessage() ], 500);
            } else {
                return back()->with('error', $e->getMessage() );
            }

        }

        // ORDER ID
        $checkout['order_id'] = $order->id;


        // INSERT ORDER LINES
        $lines = [];
        foreach( $items as $item ){
            $lines[] = [
                'order_id' => $checkout['order_id'],
                'product_id' => $item->product->id,
                'price' => $item->price,
                'qty' => $item->qty,
                'image' => $item->image,
                'variation' => $item->variationFormatted? $item->variationFormatted : null,
                'created_by' => Auth::check()? Auth::user()->id : $user->id,
                'updated_by' => Auth::check()? Auth::user()->id : $user->id,
            ];
        }

        try{
            $orderLines = new OrderLine;
            OrderLine::insert($lines);
        } catch ( \Exception $e ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' =>  $e->getMessage() ], 500);
            } else {
                return back()->with('error', $e->getMessage() );
            }
        }

        // COMMIT TAX TRANSACTION
        if( method_exists( $taxesConnector, 'createTransaction' ) ){
            $taxTransaction = $taxesConnector->createTransaction( $checkout );
            if( $taxTransaction['success'] ){
                $order->tax_object_id = $taxTransaction['tax_object_id'];
                $order->save();
            }
        }

        // INSERT TRANSACTION LOG
        $transArr = [
                'type' => 'debit',
                'amount' => $checkout['amount'],
                'order_id' => $checkout['order_id'],
                'transaction_id' => $checkout['transaction_id'],
                'notes' => 'Initial order complete.',
                'user_id' => $user->id
        ];
        $this->createTransaction( $transArr );

        // Send out order confirmation emails
        // Mail::send();


        // Empty the user's cart
        $this->deleteUserCart();

        if( $request->ajax() ){
            return response()->json($checkout);
        } else {
            return redirect()->route( 'shoppe.'.config('shoppe.slugs.order_complete', 'order-complete'), ['ref_id' => $refId ] );
        }
    }


    /*
    * ORDER COMPLETE / CHECKOUT SUCCESS
    *
    *
    *
    */
    public function checkoutSuccess($ref_id)
    {
        $order = Order::where('ref_id', $ref_id)->first();
        return view('shoppe::checkout-complete', ['data' => $order]);
    }


    /*
    *
    *
    *
    *
    */
    public function getTaxes(Request $request)
    {
        $taxes = 0.00;
        $code = 200;
        $arr = [];

        $shippingCost = $request->shipping;
        $shipping_address_id = $request->shipping_address_id;
        if( !$shipping_address_id ){
            $address = [
                'name' => $request->name,
                'street1' => $request->address,
                'street2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country,
            ];
        }

        if( $shipping_address_id ){
            $shippingAddress = AddressBook::find($shipping_address_id);
            $address = [
                'name' => $shippingAddress->name,
                'street1' => $shippingAddress->address,
                'street2' => $shippingAddress->address2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'zip' => $shippingAddress->zipcode,
                'country' => $shippingAddress->country,
            ];
        }

        $taxConnector = app('Taxes');
        $cart = $this->getCartItems();
        $arr['shipping_address'] = $address;
        $arr['items'] = $cart['items'];
        $arr['shipping_amount'] = $shippingCost;
        $arr['taxable_total'] = $cart['taxable_total'];
        $taxes = $taxConnector->getTaxes( $arr );

        $code = $taxes['success'] ? 200 : 500;

        return response()->json(['taxes' => isset($taxes['tax_amount'])? $taxes['tax_amount']: 0.00 , 'message' => $taxes['message'] ], $code);
    }


    /*
    *
    *
    *
    *
    */
    public function getShipping(Request $request)
    {
        $code = 200;
        $checkout = [];

        $shipping_address_id = $request->shipping_address_id;
        if( !$shipping_address_id ){
            $address = [
                'name' => $request->name,
                'company_name' => $request->company_name,
                'street1' => $request->address,
                'street2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country,
                'email' => ''
            ];
        }

        if( $shipping_address_id ){
            $shippingAddress = AddressBook::find($shipping_address_id);
            $address = [
                'name' => $shippingAddress->name,
                'company_name' => $request->company_name,
                'street1' => $shippingAddress->address,
                'street2' => $shippingAddress->address2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'zip' => $shippingAddress->zipcode,
                'country' => $shippingAddress->country,
                'email' => ''
            ];
        }

        $checkout['shipping_service_id'] = false;
        $checkout['shipping_address'] = $address;
        $shippingConnector = app('Shipping');
        $rates = $shippingConnector->getShippingRates( $checkout );
        if( !$rates['success'] ){
            $code = 500;
        }
        return response()->json(['rates' => $rates], $code);
    }
}
