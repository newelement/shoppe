<?php
namespace Newelement\Shoppe\Connectors;

use Newelement\Shoppe\Traits\CartData;

class Shipping
{
    use CartData;

    function __construct()
    {
        \Shippo::setApiKey(config('shoppe.shippo_api_token'));
    }

    public function getShippingRates( $address, $service_id = false )
    {

        $error = false;
        $message = 'Successful';
        $rates = [];
        $cart = $this->getCartItems();
        $weight = 0.00;
        $length = 0.00;
        $width = 0.00;
        $height = 0.00;

        $lengths = [];
        $width = [];
        $heights = [];

        // Lets get all the weight and sizes and add some padding
        // We will need to get the max dimensions
        foreach( $cart['items'] as $item ){

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

        $toAddress = [
            'street1' => $address['street1'],
            'street2' => $address['street2'],
            'city' => $address['city'],
            'state' => $address['state'],
            'zip' => $address['zip'],
            'country' => $address['country'],
        ];

        try{
            $shipment = \Shippo_Shipment::create(
                [
                    "address_from" => $fromAddress,
                    "address_to" => $toAddress,
                    "parcels" => $parcel,
                    "async" => false
                ]
            );
        } catch( \Exception $e ){
            $error = true;
            $message = $e->getMessage();
        }

        if( !$error ){
            try{
                $shippoRates = \Shippo_Shipment::get_shipping_rates(
                  array(
                    'id' => $shipment->object_id,
                    'currency' => config('shoppe.currency', 'USD')
                  )
                );
            } catch( \Exception $e ) {
                $error = true;
                $message = $e->getMessage();
            }
        }


        if( !$error ){
            foreach( $shippoRates->results as $rate ){
                $rates[] = [
                        'carrier' => $rate->provider,
                        'service' => $rate->servicelevel->name,
                        'service_id' => $rate->servicelevel->token,
                        'object_id' => $rate->object_id,
                        'amount' => $rate->amount,
                        'estimated_days' => $rate->estimated_days,
                        'message' => $rate->duration_terms
                    ];
            }

            if( $service_id ){
                foreach( $rates as $key => $rate ){
                    if( $service_id === $rate['service_id'] ){
                        $rates = $rate;
                    }
                }

                if( !$rates ){
                    $error = true;
                    $message = 'Could not find your shipping rate.';
                }
            }
        }

        return [ 'success' => $error? false : true, 'message' => $message,  'rates' => $rates];
    }


}
