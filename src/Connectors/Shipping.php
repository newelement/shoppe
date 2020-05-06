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

        $dims = $cart['dimensions']['total'];

        $length = $dims['length'];
        $width = $dims['width'];
        $height = $dims['height'];
        $weight = $cart['total_weight'];

        $shoppeSettings = getShoppeSettings();

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

    public function getShippingRate($args)
    {
        $shoppeSettings = getShoppeSettings();
        $error = false;
        $message = 'Successful';
        $rates = [];

        $parcel = [
            "weight"=> $args['weight'],
            "mass_unit"=> "lb",
            "distance_unit"=> "in",
            'length' => $args['length'],
            'width' => $args['width'],
            'height' => $args['height']
        ];

        if( $args['parcel'] ){
            $parcel['template'] = $args['parcel'];
        }

        $carrier = [
            $args['carrier']
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
            'name' => $args['shipping_name'],
            'company_name' => $args['shipping_company_name'],
            'street1' => $args['shipping_street1'],
            'street2' => $args['shipping_street2'],
            'city' => $args['shipping_city'],
            'state' => $args['shipping_state'],
            'zip' => $args['shipping_zip'],
            'country' => $args['shipping_country']
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
                    "address_from" => $from,
                    "address_to" => $to,
                    "parcels" => $parcel,
                    "carrier_accounts" => $carrier,
                    "async" => false
                ]
            );
        } catch( \Exception $e ){
            $error = true;
            $message = $e->getMessage();
        }

        $serviceName = '';
        $serviceLevels = $this->getServiceLevels();
        foreach( $serviceLevels as $group ){
            foreach( $group['levels'] as $key => $level ){
                if( $args['service'] === $key ){
                    $serviceName = $level;
                }
            }
        }

        $servicelevel = [ 'name' => $serviceName , 'token' => $args['service'], 'terms' => '' ];

        if( !$error ){
            try{
                $shippoRates = \Shippo_Shipment::get_shipping_rates(
                  array(
                    'id' => $shipment->object_id,
                    'currency' => config('shoppe.currency', 'USD'),
                    'servicelevel' => $servicelevel
                  )
                );
            } catch( \Exception $e ) {
                $error = true;
                $message = $e->getMessage();
            }
        }

        if( !$error ){
            foreach( $shippoRates->results as $rate ){
                if( $args['service'] === $rate->servicelevel->token ){
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

    public function getAdhocShippingLabel($order, $carrierId, $serviceLevel)
    {
        $error = false;
        $message = 'Successful';

        $shoppeSettings = getShoppeSettings();

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
            'name' => $order->shippingAddress->name,
            'company_name' => $order->shippingAddress->company_name,
            'street1' => $order->shippingAddress->address,
            'street2' => $order->shippingAddress->address2,
            'city' => $order->shippingAddress->city,
            'state' => $order->shippingAddress->state,
            'zip' => $order->shippingAddress->zipcode,
            'country' => $order->shippingAddress->country,
            "email" => $order->user->email
        ];

        try{

            $transaction = \Shippo_Transaction::create([
                "shipment" => [
                    "address_to" => $to,
                    "address_from" => $from,
                    "parcels" => [
                        [
                            "length" => $order->shipping_max_length,
                            "width" => $order->shipping_max_width,
                            "height" => $order->shipping_max_height,
                            "distance_unit" => "in",
                            "weight" => $order->shipping_weight,
                            "mass_unit" => "lb"
                        ]
                    ]
                ],
                "carrier_account" => $carrierId,
                "servicelevel_token" => $serviceLevel
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

    public function getCarrierAccounts()
    {
        $carriers = [];

        try{
            $response = \Shippo_CarrierAccount::all();
        } catch ( \Exception $e ){
            return ['success' => false, 'message' => $e->getMessage()];
        }

        foreach( $response->results as $key => $carrier ){
            $carriers[$key]['object_id'] = $carrier->object_id;
            $carriers[$key]['carrier'] = $carrier->carrier;
        }

        return ['success' => true, 'carriers' => $carriers];
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

    public function getParcelTemplates()
    {
        $templates = [];

        $templates[] = [
            'carrier' => 'FedEx',
            'templates' => [
                'FedEx_Box_10kg' => 'FedEx® 10kg Box - 15.81 x 12.94 x 10.19 in',
                'FedEx_Box_25kg' => 'FedEx® 25kg Box - 54.80 x 42.10 x 33.50 in',
                'FedEx_Box_Extra_Large_1' => 'FedEx® Extra Large Box (X1) - 11.88 x 11.00 x 10.75 in',
                'FedEx_Box_Extra_Large_2' => 'FedEx® Extra Large Box (X2) - 15.75 x 14.13 x 6.00 in',
                'FedEx_Box_Large_1' => 'FedEx® Large Box (L1) - 17.50 x 12.38 x 3.00 in',
                'FedEx_Box_Large_2' => 'FedEx® Large Box (L2) - 11.25 x 8.75 x 7.75 in',
                'FedEx_Box_Medium_1' => 'FedEx® Medium Box (M1) - 13.25 x 11.50 x 2.38 in',
                'FedEx_Box_Medium_2' => 'FedEx® Medium Box (M2) - 11.25 x 8.75 x 4.38 in',
                'FedEx_Box_Small_1' => 'FedEx® Small Box (S1) - 12.38 x 10.88 x 1.50 in',
                'FedEx_Box_Small_2' => 'FedEx® Small Box (S2) - 11.25 x 8.75 x 4.38 in',
                'FedEx_Envelope' => 'FedEx® Envelope - 12.50 x 9.50 x 0.80 in',
                'FedEx_Padded_Pak' => 'FedEx® Padded Pak - 11.75 x 14.75 x 2.00 in',
                'FedEx_Pak_1' => 'FedEx® Large Pak - 15.50 x 12.00 x 0.80 in',
                'FedEx_Pak_2' => 'FedEx® Small Pak - 12.75 x 10.25 x 0.80 in',
                'FedEx_Tube' => 'FedEx® Tube - 38.00 x 6.00 x 6.00 in',
                'FedEx_XL_Pak' => 'FedEx® Extra Large Pak - 17.50 x 20.75 x 2.00 in'
            ]
        ];

        $templates[] = [
            'carrier' => 'UPS',
            'templates' => [
                'UPS_Box_10kg' => 'Box 10kg - 410.00 x 335.00 x 265.00 mm',
                'UPS_Box_25kg' => 'Box 25kg - 484.00 x 433.00 x 350.00 mm',
                'UPS_Express_Box' => 'Express Box - 460.00 x 315.00 x 95.00 mm',
                'UPS_Express_Box_Large' => 'Express Box Large - 18.00 x 13.00 x 3.00 in',
                'UPS_Express_Box_Medium' => 'Express Box Medium - 15.00 x 11.00 x 3.00 in',
                'UPS_Express_Box_Small' => 'Express Box Small - 13.00 x 11.00 x 2.00 in',
                'UPS_Express_Envelope' => 'Express Envelope - 12.50 x 9.50 x 2.00 in',
                'UPS_Express_Hard_Pak' => 'Express Hard Pak - 14.75 x 11.50 x 2.00 in',
                'UPS_Express_Legal_Envelope' => 'Express Legal Envelope - 15.00 x 9.50 x 2.00 in',
                'UPS_Express_Pak' => 'Express Pak - 16.00 x 12.75 x 2.00 in',
                'UPS_Express_Tube' => 'Express Tube - 970.00 x 190.00 x 165.00 mm',
                'UPS_Laboratory_Pak' => 'Laboratory Pak - 17.25 x 12.75 x 2.00 in',
                'UPS_MI_First_Class' => 'First Class (Mail Innovations - Domestic only)',
                'UPS_MI_Flat' => 'Flat (Mail Innovations - Domestic only)',
                'UPS_MI_Irregular' => 'Irregular (Mail Innovations - Domestic only)',
                'UPS_MI_Machinable' => 'Machinable (Mail Innovations - Domestic only)',
                'UPS_MI_MEDIA_MAIL' => 'Media Mail (Mail Innovations - Domestic only)',
                'UPS_MI_Parcel_Post' => 'Parcel Post (Mail Innovations - Domestic only)',
                'UPS_MI_Priority' => 'Priority (Mail Innovations - Domestic only)',
                'UPS_MI_Standard_Flat' => 'Standard Flat (Mail Innovations - Domestic only)',
                'UPS_Pad_Pak' => 'Pad Pak - 14.75 x 11.00 x 2.00 in',
                'UPS_Pallet' => 'Pallet - 120.00 x 80.00 x 200.00 cm'
            ]
        ];

        $templates[] = [
            'carrier' => 'USPS',
            'templates' => [
                'USPS_FlatRateCardboardEnvelope' => 'Flat Rate Cardboard Envelope - 12.50 x 9.50 x 0.75 in',
                'USPS_FlatRateEnvelope' => 'Flat Rate Envelope - 12.50 x 9.50 x 0.75 in',
                'USPS_FlatRateGiftCardEnvelope' => 'Flat Rate Gift Card Envelope - 10.00 x 7.00 x 0.75 in',
                'USPS_FlatRateLegalEnvelope' => 'Flat Rate Legal Envelope - 15.00 x 9.50 x 0.75 in',
                'USPS_FlatRatePaddedEnvelope' => 'Flat Rate Padded Envelope - 12.50 x 9.50 x 1.00 in',
                'USPS_FlatRateWindowEnvelope' => 'Flat Rate Window Envelope - 10.00 x 5.00 x 0.75 in',
                'USPS_IrregularParcel' => 'Irregular Parcel - 0.00 x 0.00 x 0.00 in',
                'USPS_LargeFlatRateBoardGameBox' => 'Large Flat Rate Board Game Box - 24.06 x 11.88 x 3.13 in',
                'USPS_LargeFlatRateBox' => 'Large Flat Rate Box - 12.25 x 12.25 x 6.00 in',
                'USPS_APOFlatRateBox' => 'APO/FPO/DPO Large Flat Rate Box - 12.25 x 12.25 x 6.00 in',
                'USPS_LargeVideoFlatRateBox' => 'Flat Rate Large Video Box (Intl only) - 9.60 x 6.40 x 2.20 in',
                'USPS_MediumFlatRateBox1' => 'Medium Flat Rate Box 1 - 11.25 x 8.75 x 6.00 in',
                'USPS_MediumFlatRateBox2' => 'Medium Flat Rate Box 2 - 14.00 x 12.00 x 3.50 in',
                'USPS_RegionalRateBoxA1' => 'Regional Rate Box A1 - 10.13 x 7.13 x 5.00 in',
                'USPS_RegionalRateBoxA2' => 'Regional Rate Box A2 - 13.06 x 11.06 x 2.50 in',
                'USPS_RegionalRateBoxB1' => 'Regional Rate Box B1 - 12.25 x 10.50 x 5.50 in',
                'USPS_RegionalRateBoxB2' => 'Regional Rate Box B2 - 16.25 x 14.50 x 3.00 in',
                'USPS_SmallFlatRateBox' => 'Small Flat Rate Box - 8.69 x 5.44 x 1.75 in',
                'USPS_SmallFlatRateEnvelope' => 'Small Flat Rate Envelope - 10.00 x 6.00 x 4.00 in',
                'USPS_SoftPack' => 'Soft Pack Padded Envelope'
            ]
        ];

        return $templates;
    }

}
