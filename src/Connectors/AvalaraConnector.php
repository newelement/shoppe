<?php
namespace Newelement\Shoppe\Connectors;

use Newelement\Shoppe\Traits\CartData;

class AvalaraConnector
{
    use CartData;

    private $avalara;
    private $message = 'Tax collected successful.';
    public $connector_name = 'shoppe_avalara';

    function __construct()
    {
        $sandbox = env('APP_ENV') === 'production'? '' : 'sandbox';
        //$this->avalara = new \Avalara\AvaTaxClient('neutrino', '1.0', 'localhost', $sandbox);
        //$this->avalara->withSecurity( config('shoppe.avalara_user') , config('shoppe.avalara_pass'));

    }

    public function getTaxes( $checkout )
    {
        $response = $this->avalaraFree($checkout);
        //$response = $this->avalaraPro($shippingCost, $address, $cart);

        $success = $response? true : false;
        $taxAmount = $response? $response['tax_amount'] : 0.00;
        $rate = $response? $response['rate'] : 0.00;

        return ['tax_amount' => $taxAmount, 'tax_rate' => $rate, 'success' => $success, 'message' => $this->message];
    }


    protected function avalaraFree($checkout)
    {
        $client = new \GuzzleHttp\Client();
        $cart = $this->getCartItems();
        $taxAmount = 0.00;
        $rate = 0.00;
        try{
            $response = $client->get('https://sandbox-rest.avatax.com/api/v2/taxrates/byaddress', [
                'query' => [
                    'line1' => $checkout['shipping_address']['street1'],
                    'line2' => $checkout['shipping_address']['street2'],
                    'city' => $checkout['shipping_address']['city'],
                    'region' => $checkout['shipping_address']['state'],
                    'postalCode' => $checkout['shipping_address']['zip'],
                    'country' => $checkout['shipping_address']['country']
                ],
                'auth' => [
                    config('shoppe.avalara_user'),
                    config('shoppe.avalara_pass')
                ]
            ]);

            $data = json_decode( $response->getBody() );
            $rate = $data->totalRate;
            $amount = round( ( $rate * ($cart['sub_total'] + $checkout['shipping_amount']) ), 2, PHP_ROUND_HALF_DOWN);

        } catch ( \Exception $e ){
            $this->message = $e->getMessage();
            return false;
        }

        return ['tax_amount' => $amount, 'rate' => $rate];
    }


    /*
    * AVALARA PRO
    *
    *
    */
    /*
    protected function avalaraPro($shippingCost, $address, $cart)
    {
        $tb = new \Avalara\TransactionBuilder($this->avalara, "DEFAULT", \Avalara\DocumentType::C_SALESINVOICE, 'ABC');
        $tb->withAddress('SingleLocation', $address['street1'], $address['street2'], null, $address['city'], $address['state'], $address['zip'], $address['country']);

        foreach( $cart['items'] as $item ){
            $tb->withLine( ($item['price'] * $item['qty']), $item['qty'], null, null)->withItemDiscount('false');
        }

        $tb->withLine( $shippingCost, 1, 'Shipping', 'FR020100');

        try{
            $t = $tb->create();
            $taxAmount = $t->totalTax;
            return $taxAmount;

        } catch( \Exception $e ){
            $this->message = $e->getMessage();
            return false;
        }

    }
    */

}
