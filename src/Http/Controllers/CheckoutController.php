<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\Page;
use Newelement\Shoppe\Traits\CartData;
use Newelement\Shoppe\Models\AddressBook;
use Newelement\Shoppe\Models\Customer;
use Newelement\Shoppe\Models\Order;
use Illuminate\Support\Facades\Hash;

class CheckoutController extends Controller
{
    use CartData;

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

    public function processCheckout(Request $request)
    {
        $code = 200;

        //Validate initial data
        $validateArr = [];

        $paymentConnector = app('Payment');
        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');

        $email = $request->email;
        $cart = $this->getCartItems();
        $items = $cart['items'];
        $subTotal = $cart['sub_total'];
        $refId = sha1( uniqid().microtime().$subTotal.$email.env('APP_KEY') );
        $checkout = [];

        // Get request params
        $token = $request->token;
        $saveShipping = $request->save_shipping? true : false;
        $saveCard = $request->save_card? true : false;

        // Start the checkout array
        $checkout['email'] = $email;
        $checkout['token'] = $token;
        $checkout['description'] = $refId;
        $checkout['ref_id'] = $refId;

        /*
        * Shipping
        *
        *
        */
        $serviceId = $request->shipping_rate;
        $address = [
            'street1' => $request->shipping_address,
            'street2' => $request->shipping_address2,
            'city' => $request->shipping_city,
            'state' => $request->shipping_state,
            'zip' => $request->shipping_zipcode,
            'country' => $request->shipping_country,
        ];

        $rate = $shippingConnector->getShippingRates( $address, $serviceId );

        if( !$rate['success'] ){
            $code = 500;
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $rate['message']], $code);
            } else {
                return back()->with('error', $rate['message']);
            }
        }

        $checkout['shipping_amount'] = $rate['rates']['amount'];
        $checkout['shipping_service'] = $rate['rates']['service'];
        $checkout['shipping_service_id'] = $rate['rates']['service_id'];
        $checkout['shipping_est_days'] = $rate['rates']['estimated_days'];
        $checkout['object_id'] = isset($rate['rates']['object_id'])? $rate['rates']['object_id'] : null ;


        /*
        * Taxes
        *
        *
        */
        $taxes = $taxesConnector->getTaxes( $checkout['shipping_amount'], $address );
        if( !$taxes['success'] ){
            $code = 500;
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $taxes['message']], $code);
            } else {
                return back()->with('error', $taxes['message']);
            }
        }

        $checkout['tax_amount'] = $taxes['tax_amount'];


        /*
        * Totals
        *
        *
        */
        $amount = (float) $subTotal + (float) $checkout['shipping_amount'] + (float) $checkout['tax_amount'];

        $checkout['amount'] = $amount;

        $charge = $paymentConnector->charge( $checkout, $saveCard );

        if( !$charge['success'] ){
            $code = 500;
            if( $request->ajax() ){
                return response()->json(['success' => false, 'message' => $charge['message']], $code);
            } else {
                return back()->with('error', $charge['message']);
            }
        }

        if( $saveCard ){
            $checkout['customer_id'] = $charge['customer_id'];
            Customer::saveCard( $checkout );
        }

        // Insert new order
        $orderId = Order::create( $checkout );
        $checkout['order_id'] = $orderId;

        // Send out order confirmation emails
        //

        // Empty the user's cart
        $this->deleteUserCart();

        if( $request->ajax() ){
            return response()->json($checkout, $code);
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
        $shippingCost = $request->shipping;
        $address = [
            'street1' => $request->address,
            'street2' => $request->address2,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country,
        ];

        $taxConnector = app('Taxes');
        $cartItems = $this->getCartItems();
        $taxes = $taxConnector->getTaxes( $shippingCost, $address, $cartItems );

        $code = $taxes['success'] ? 200 : 500;

        return response()->json(['taxes' => $taxes['tax_amount'], 'message' => $taxes['message'] ], $code);
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
        $address = [
            'street1' => $request->address,
            'street2' => $request->address2,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country,
        ];

        $shippingConnector = app('Shipping');
        $rates = $shippingConnector->getShippingRates( $address );

        return response()->json(['rates' => $rates], $code);
    }
}
