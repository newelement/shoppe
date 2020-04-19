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

        $shoppeSettings = getShoppSettings();

        $parcel = [
            "weight"=> $weight,
            "mass_unit"=> "lb",
            "distance_unit"=> "in",
            'length' => $length,
            'width' => $width,
            'height' => $height
        ];

        $from = [
            'name' => $shoppeSettings['shipping_name'],
            'street1' => $shoppeSettings['shipping_address'],
            'street2' => $shoppeSettings['shipping_address2'],
            'city' => $shoppeSettings['shipping_city'],
            'state' => $shoppeSettings['shipping_state'],
            'zip' => $shoppeSettings['shipping_postal'],
            'country' => $shoppeSettings['shipping_country'],
            "phone" => $shoppeSettings['shipping_phone'],
            "email" => $shoppeSettings['shipping_email']
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

    public function getServiceLevels()
    {
        $serviceLevels = [];

        $serviceLevels[] = [
            'carrier' => 'FedEx',
            'levels' => [
                'fedex_ground' => 'FedEx Ground®',
                'fedex_home_delivery' => 'FedEx Home Delivery®',
                'fedex_smart_post' => 'FedEx SmartPost®',
                'fedex_2_day' => 'FedEx 2Day®',
                'fedex_2_day_am' => 'FedEx 2Day® A.M.',
                'fedex_express_saver' => 'FedEx Express Saver®',
                'fedex_standard_overnight' => 'FedEx Standard Overnight®',
                'fedex_priority_overnight' => 'FedEx Priority Overnight®',
                'fedex_first_overnight' => 'FedEx First Overnight®',
                'fedex_freight_priority' => 'FedEx Freight® Priority',
                'fedex_next_day_freight' => 'FedEx Next Day Freight',
                'fedex_freight_economy' => 'FedEx Freight® Economy',
                'fedex_first_freight' => 'FedEx First Freight',
                'fedex_international_economy' => 'FedEx International Economy®',
                'fedex_international_priority' => 'FedEx International Priority®',
                'fedex_international_first' => 'FedEx International First®',
                'fedex_europe_first_international_priority' => 'FedEx International First®',
                'fedex_international_priority_express' => 'FedEx International Priority Express',
                'international_economy_freight' => 'FedEx International Economy® Freight',
                'international_priority_freight' => 'FedEx International Priority® Freight'
            ]
        ];

        $serviceLevels[] = [
            'carrier' => 'UPS',
            'levels' => [
                'ups_standard' => 'Standard℠',
                'ups_ground' => 'Ground',
                'ups_saver' => 'Saver®',
                'ups_3_day_select' => 'Three-Day Select®',
                'ups_second_day_air' => 'Second Day Air®',
                'ups_second_day_air_am' => 'Second Day Air A.M.®',
                'ups_next_day_air' => 'Next Day Air®',
                'ups_next_day_air_saver' => 'Next Day Air Saver®',
                'ups_next_day_air_early_am' => 'Next Day Air Early A.M.®',
                'ups_mail_innovations_domestic' => 'Mail Innovations (domestic)',
                'ups_surepost' => 'Surepost',
                'ups_surepost_lightweight' => 'Surepost Lightweight',
                'ups_express' => 'Express®',
                'ups_express_1200' => 'Express 12:00',
                'ups_express_plus' => 'Express Plus®',
                'ups_expedited' => 'Expedited®'
            ]
        ];

        $serviceLevels[] = [
            'carrier' => 'USPS',
            'levels' => [
                'usps_priority' => 'Priority Mail',
                'usps_priority_express' => 'Priority Mail Express',
                'usps_first' => 'First Class Mail/Package',
                'usps_parcel_select' => 'Parcel Select',
                'usps_priority_mail_international' => 'Priority Mail International',
                'usps_priority_mail_express_international' => 'Priority Mail Express International',
                'usps_first_class_package_international_service' => 'First Class Package International'
            ]
        ];

        return $serviceLevels;
    }

}
