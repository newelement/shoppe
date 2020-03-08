<?php
namespace Newelement\Shoppe\Connectors;

use Newelement\Shoppe\Traits\CartData;

class Shipping
{
    use CartData;

    public $connector_name = 'shoppe_shippo';

    function __construct()
    {
        \Shippo::setApiKey(config('shoppe.shippo_api_token'));
    }

    public function getShippingRates( $checkout )
    {

        $error = false;
        $message = 'Successful';
        $rates = [];
        $cart = $this->getCartItems();
        $weight = 0.00;
        $length = 0.00;
        $width = 0.00;
        $height = 0.00;

        $dims = $cart['dimensions']['estimated'];

        $length = $dims['length'] + 1;
        $width = $dims['width'] + 1;
        $height = $dims['height'] + 1;
        $weight = $cart['estimated_weight'] + 1;

        $parcel = [
            "weight"=> $weight,
            "mass_unit"=> "lb",
            "distance_unit"=> "in",
            'length' => $length,
            'width' => $width,
            'height' => $height
        ];

        $from = [
            'name' => 'Neutrino',
            'street1' => '130 Tannin Way',
            'city' => 'Lexington',
            'state' => 'NC',
            'zip' => '27295',
            'country' => 'US',
            "phone" => "+1 555 341 9393",
            "email" => "shippotle@goshippo.com"
        ];

        $to = [
            'name' => $checkout['shipping_address']['name'],
            'company_name' => $checkout['shipping_address']['company_name'],
            'street1' => $checkout['shipping_address']['street1'],
            'street2' => $checkout['shipping_address']['street2'],
            'city' => $checkout['shipping_address']['city'],
            'state' => $checkout['shipping_address']['state'],
            'zip' => $checkout['shipping_address']['zip'],
            'country' => $checkout['shipping_address']['country'],
            "email" => $checkout['shipping_address']['email']
        ];

        try{
            $fromAddress = \Shippo_Address::create($from);
        } catch( \Exception $e ){
            $error = true;
            $message = $e->getMessage();
        }

        try{
            $toAddress = \Shippo_Address::create($to);
        } catch( \Exception $e ){
            $error = true;
            $message = $e->getMessage();
        }

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

            if( $checkout['shipping_service_id'] ){
                foreach( $rates as $key => $rate ){
                    if( $checkout['shipping_service_id'] === $rate['service_id'] ){
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

    public function getShippingLabel($objectId)
    {
        $error = false;
        $message = 'Successful';

        try{
            $transaction = \Shippo_Transaction::create([
                'rate' => $objectId,
                'label_file_type' => "PDF",
                'async' => false
            ]);
        } catch( \Exception $e ){
            $error = true;
            $message = $e->getMessage();
        }

        if( !$error ){
            if ($transaction["status"] === "SUCCESS"){
                $label = $transaction["label_url"];
                $tracking_number = $transaction["tracking_number"];
                $tracking_url_provider = $transaction['tracking_url_provider'];
            }else {
                $error = true;
                $message = $transaction["messages"];
            }
        }

        if( $error ){
            return ['success' => false, 'message' => $message];
        }

        return [
            'success' => true,
            'message' => $message,
            'tracking_number' => $tracking_number,
            'label_url' => $label,
            'tracking_url' => $tracking_url_provider
        ];
    }

}
