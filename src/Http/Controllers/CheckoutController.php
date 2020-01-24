<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\Page;
use Newelement\Shoppe\Traits\CartData;

class CheckoutController extends Controller
{
    use CartData;

    public function index(Request $request)
    {
        $cartItems = $this->getCartItems();
        $data = Page::where('slug', 'checkout')->first();
        $data->data_type = 'page';
        $data->items = $cartItems['items'];
        $data->sub_total = $cartItems['sub_total'];
        if( $request->ajax() ){
            return response()->json($data);
        } else {
            return view('shoppe::checkout', ['data' => $data]);
        }
    }

    public function processCheckout(Request $request)
    {

    }

    public function checkoutSuccess()
    {

        return view('shoppe::checkout-success', ['data' => $data]);
    }

    public function getShippingOptions()
    {
        $shippingConConfig = config('shoppe.shipping_connector');
        $sp = explode('@', $shippingConConfig);
        if( count($sp) = 2 ){
            $ShippingClass = new $sp[0];
            $method = $sp[1];
            $cartItems = $this->getCartItems();
            $shippingConnector = $ShippingClass->$method( $cartItems, $address  );
        }
    }

    public function getTaxes(Request $request)
    {
        $taxes = 0.00;
        return response()->json(['taxes' => $taxes]);
    }

    public function getShipping(Request $request)
    {
        $shipping = 0.00;
        $shippingConConfig = config('shoppe.shipping_connector', 'Newelement\\Shoppe\\Http\\Controllers\\ShippingController@getShippingCosts');
        $sp = explode('@', $shippingConConfig);
        if( count($sp) = 2 ){
            $ShippingClass = new $sp[0];
            $method = $sp[1];
            $cartItems = $this->getCartItems();
            $shippingConnector = $ShippingClass->$method( $cartItems, $address  );
        }

        return response()->json(['shipping' => $shipping]);
    }
}
