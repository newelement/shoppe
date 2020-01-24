<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Traits\CartData;


class CartController extends Controller
{
    use CartData;

    function __construct()
    {
        \Shippo::setApiKey(config('shoppe.shippo_api_token'));
    }

    public function getShippingCosts( $address )
    {
        $cartItems = $this->getCartItems();


        $shipment = \Shippo_Shipment::create(
            array(
                "address_from" => $fromAddress,
                "address_to" => $toAddress,
                "parcels" => $parcel,
                "async" => false
            )
        );

        return $shipment;
    }

}
