<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Traits\CartData;


class ShippingController extends Controller
{
    use CartData;

    function __construct()
    {
        \Shippo::setApiKey(config('shoppe.shippo_api_token'));
    }

    public function getShippingRates( $cartItems, $toAddress )
    {
        $cartItems = $cartItems;
        $weight = 0.00;
        $length = 0.00;
        $width = 0.00;
        $height = 0.00;

        $lengths = [];
        $width = [];
        $heights = [];

        // Lets get all the weight and sizes and add some padding
        // We will need to get the max dimensions
        foreach( $cartItems['items'] as $item ){

            if( $item->variation_id ){



            } else {

                $weight += (float) $item->product->weight;
                $widths[] = (float) $item->product->width;
                $heights[] = (float) $item->product->height;
                $lengths[] = (float) $item->product->depth;

            }

        }

        $length = max( $lengths ) + 1;
        $width = max( $widths ) + 1;
        $height = max( $heights ) + 1;
        $weight = $weight + 1;

        $parcel = [
            "weight"=> $weight,
            "mass_unit"=> "lb",
            "distance_unit"=> "in",
            'length' => $length,
            'width' => $width,
            'height' => $height
        ];

        $fromAddress = [
            'street1' => '130 Tannin Way',
            'city' => 'Lexington',
            'state' => 'NC',
            'zip' => '27295',
            'country' => 'US'
        ];

        $shipment = \Shippo_Shipment::create(
            array(
                "address_from" => $fromAddress,
                "address_to" => $toAddress,
                "parcels" => $parcel,
                "async" => false
            )
        );


        $shippoRates = \Shippo_Shipment::get_shipping_rates(
          array(
            'id' => $shipment->object_id,
            'currency' => config('shoppe.currency', 'USD')
          )
        );

        $rates = [];

        foreach( $shippoRates->results as $rate ){
            $rates[] = [
                    'carrier' => $rate->provider,
                    'service' => $rate->servicelevel->name,
                    'service_id' => $rate->servicelevel->token,
                    'amount' => $rate->amount,
                    'estimated_days' => $rate->estimated_days,
                    'message' => $rate->duration_terms
                ];
        }

        return $rates;
    }

}
