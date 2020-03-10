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
use Newelement\Shoppe\Models\PaymentType;
use Newelement\Shoppe\Traits\Transactions;
use Newelement\Shoppe\Traits\ShippingService;
use Newelement\Shoppe\Traits\TaxService;
use Illuminate\Support\Facades\Hash;
use Auth;

class CheckoutController extends Controller
{
    use CartData, Transactions, ShippingService, TaxService;

    public function __construct()
    {}

    public function index(Request $request)
    {
        $cart = $this->getCartItems();
        $data = Page::where('slug', 'checkout')->first();
        $data->data_type = 'page';
        $data->items = $cart['items'];
        $data->sub_total = $cart['sub_total'];

        $paymentConnector = app('Payment');
        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');

        $data->payment_connector = $paymentConnector->connector_name;
        $data->tax_connector = $taxesConnector->connector_name;
        $data->shipping_connector = $shippingConnector->connector_name;

        $data->shipping_addresses = AddressBook::where(
                                    [
                                        'address_type' => 'shipping',
                                        'user_id' => auth()->user()? auth()->user()->id : 0
                                    ])
                                    ->orderBy('default', 'desc')
                                    ->orderBy('address', 'desc')
                                    ->get();

        $data->payment_types = [];

        $customer = Customer::where('user_id', auth()->user()->id )->first();
        if( $customer ){
            $paymentTypes = $paymentConnector->getStoredPaymentTypes($customer->customer_id);
            if( $paymentTypes['success'] ){
                $data->payment_types = $paymentTypes['items'];
            }
        }

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

        $paymentConnector = app('Payment');
        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');
        $cart = $this->getCartItems();
        $items = $cart['items'];
        $eligibleShipping = $cart['eligible_shipping'];
        $checkout = [];

        //Validate initial data
        $validateArr = [
            'email' => 'required|email',
        ];

        if( $paymentConnector->connector_name === 'shoppe_stripe' ){
            $validateArr['saved_payment'] = 'required_without:token';
            $validateArr['token'] = 'required_without:saved_payment';
        }

        if( $eligibleShipping ){
            $validateArr['shipping_rate'] = 'required';
            if( !$request->shipping_address_option ){
                $validateArr['shipping_name'] = 'required';
                $validateArr['shipping_address'] = 'required';
                $validateArr['shipping_city'] = 'required';
                $validateArr['shipping_state'] = 'required';
                $validateArr['shipping_zipcode'] = 'required';
                $validateArr['shipping_country'] = 'required';
            }
        }

        $validatedData = $request->validate(
            $validateArr
        );


        $email = $request->email;
        $billing_name = $request->cc_name;
        $savedShipping = $request->shipping_address_option && $request->shipping_address_option !== 'new_shipping_address'? $request->shipping_address_option : false;
        $subTotal = $cart['sub_total'];
        $taxableTotal = $cart['taxable_total'];
        $refId = sha1( uniqid().microtime().$subTotal.$email.env('APP_KEY') );
        $user = Customer::createOrGet( $billing_name, $email );
        $customerId = false;

        // Get request params
        $isStoredPayment = $request->saved_payment? true : false;
        $token = $request->token? $request->token : $request->saved_payment;
        $saveCard = $request->save_card? true : false;

        // Start the checkout array
        $checkout['customer_name'] = $billing_name;
        $checkout['email'] = $email;
        $checkout['token'] = $token;
        $checkout['is_stored_payment'] = $isStoredPayment;
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
        $this->savedShipping = $savedShipping;
        $this->eligibleShipping = $eligibleShipping;
        $this->email = $email;
        $this->user = $user;

        $shippingAddress = $this->processShippingAddress($request);
        // SAVE CUSTOMER'S SHIPPING ADDRESS
        $this->saveShippingAddress();

        $checkout['shipping_address'] = $shippingAddress;
        $flatRate = $cart['flat_rate_total'];
        $shippingAmount = $flatRate;

        if( $cart['estimated_weight'] ){
            $estimatedRate = $shippingConnector->getShippingRates( $checkout );
            $checkout['shipping_connector'] = $shippingConnector->connector_name;

            if( !$estimatedRate['success'] ){
                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' => $estimatedRate['message'], 'failed_on' => 'shipping'], 500);
                } else {
                    return back()->with('error', $estimatedRate['message']);
                }
            }

            $checkout['shipping_amount'] = $estimatedRate['rates']['amount'];
            $checkout['shipping_carrier'] = $estimatedRate['rates']['carrier'];
            $checkout['shipping_service'] = $estimatedRate['rates']['service'];
            $checkout['shipping_service_id'] = $estimatedRate['rates']['service_id'];
            $checkout['shipping_est_days'] = $estimatedRate['rates']['estimated_days'];
            $checkout['shipping_object_id'] = isset($estimatedRate['rates']['object_id'])? $estimatedRate['rates']['object_id'] : null ;

            $shippingAmount = (float) $checkout['shipping_amount'] + (float) $flatRate;

        }



        /*
        * Taxes
        *
        *
        */
        $taxes = $taxesConnector->getTaxes( $checkout );
        $checkout['tax_connector'] = $taxesConnector->connector_name;

        if( !$taxes['success'] ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $taxes['message'], 'failed_on' => 'taxes'], 500);
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
        $amount = (float) $subTotal + $shippingAmount + (float) $checkout['tax_amount'];
        $checkout['amount'] = $amount;

        // THE CHARGE
        $paymentConnector->email = strtolower($checkout['email']);
        $charge = $paymentConnector->charge( $checkout );

        $checkout['payment_connector'] = $paymentConnector->connector_name;

        if( !$charge['success'] ){
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $charge['message'], 'failed_on' => 'payment'], 500);
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
            $checkout['last_four'] = $charge['last_four'];
            $checkout['card_brand'] = $charge['card_brand'];
            $checkout['payment_type'] = $charge['payment_type'];
            $checkout['billing_id'] = $charge['billing_id'];
            $savedCustomer = Customer::saveCustomer( $checkout, $user );
            $savedPaymentType = PaymentType::savePayment( $checkout, $user );
        }

        if( $isStoredPayment ){
            $checkout['last_four'] = $charge['last_four'];
            $checkout['card_brand'] = $charge['card_brand'];
            $checkout['payment_type'] = $charge['payment_type'];
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
            if( $saveCard || $isStoredPayment ){
                $order->last_four = isset($checkout['last_four'])? $checkout['last_four'] : null;
                $order->card_brand = isset($checkout['card_brand'])? $checkout['card_brand'] : null;
                $order->payment_type = isset($checkout['payment_type'])? $checkout['payment_type'] : null;
            }
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
                'notes' => 'Order created.',
                'user_id' => $user->id
        ];
        $this->createTransaction( $transArr );

        // Send out order confirmation emails
        // Mail::send();

        $checkout['order_complete_route'] = config('shoppe.slugs.order_complete', 'order-complete');

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
        if( !$order ){
            abort(404);
        }
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
        $cart = $this->getCartItems();
        $code = 200;
        $checkout = [];
        $shoppeSettings = getShoppeSettings();
        $rates = [ 'rates' => [] ];

        if( $cart['estimated_weight'] > 0 ){

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

        }

        if( $shoppeSettings['shipping_type'] === 'flat' ){

            $flat = [
                'amount' => formatCurrency( $cart['flat_rate_total'] + (float) $shoppeSettings['flat_rate']),
                'carrier' => 'UPS',
                'estimated_days' => '2-3',
                'object_id' => null,
                'service' => 'Ground',
                'service_id' => 'ground',
                'rate_type' => 'flat'
            ];

            array_unshift( $rates['rates'], $flat );

        }

        return response()->json(['rates' => $rates], $code);
    }
}
