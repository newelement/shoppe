<?php
namespace Newelement\Shoppe\Connectors;

use Newelement\Shoppe\Traits\CartData;

class Taxes
{
    use CartData;

    //private $avalara;
    private $taxjar;
    private $message = 'Tax collected successful.';
    public $connector_name = 'taxjar';

    function __construct()
    {
        //$sandbox = env('APP_ENV') === 'production'? '' : 'sandbox';
        //$this->avalara = new \Avalara\AvaTaxClient('neutrino', '1.0', 'localhost', $sandbox);
        //$this->avalara->withSecurity( config('shoppe.avalara_user') , config('shoppe.avalara_pass'));

        $this->taxjar = \TaxJar\Client::withApiKey( config('shoppe.taxjar_token_live') );
    }

    public function getTaxes( $checkout )
    {
        $cart = $this->getCartItems();
        //$response = $this->alavaraFree($address);
        //$response = $this->avalaraPro($shippingCost, $address, $cart);
        $response = $this->taxJar($checkout);

        $success = $response? true : false;
        $taxAmount = $response? $response['tax_amount'] : 0.00;
        $rate = $response? $response['rate'] : 0.00;

        return ['tax_amount' => $taxAmount, 'tax_rate' => $rate, 'success' => $success, 'message' => $this->message];
    }

    /*
    protected function avalaraFree($address)
    {
        $client = new \GuzzleHttp\Client();
        $taxAmount = 0.00;
        try{
            $response = $client->get('https://sandbox-rest.avatax.com/api/v2/taxrates/byaddress', [
                'query' => [
                    'line1' => $address['street1'],
                    'line2' => $address['street2'],
                    'city' => $address['city'],
                    'region' => $address['state'],
                    'postalCode' => $address['zip'],
                    'country' => $address['country']
                ],
                'auth' => [
                    config('shoppe.avalara_user'),
                    config('shoppe.avalara_pass')
                ]
            ]);

            $data = json_decode( $response->getBody() );
            $rate = $data->totalRate;
            $taxAmount = round( ( $rate * $cartItems['sub_total'] ), 2, PHP_ROUND_HALF_DOWN);

        } catch ( \Exception $e ){
            $this->message = $e->getMessage();
            return false;
        }

        return $taxAmount;
    }*/


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


    /*
    * TAXJAR
    *
    *
    */
    protected function taxJar($checkout)
    {

        $order = [
          'to_country' => $checkout['shipping_address']['country'],
          'to_zip' => $checkout['shipping_address']['zip'],
          'to_state' => $checkout['shipping_address']['state'],
          'to_city' => $checkout['shipping_address']['city'],
          'to_street' => $checkout['shipping_address']['street1'],
          'to_street2' => $checkout['shipping_address']['street2'],
          'amount' => $checkout['taxable_total'],
          'shipping' => $checkout['shipping_amount'],
        ];

        $i = 0;
        foreach( $checkout['items'] as $item ){
            if( $item->product->is_taxable ){
                $order['line_items'][] =
                [
                  'id' => $i,
                  'quantity' => $item['qty'],
                  'product_identifier' => $item['product']->sku? $item['product']->sku : $item['product']->mfg_part_number ,
                  'unit_price' => $item['price'],
                ];
                $i++;
            }
        }

        try{
            $order_taxes = $this->taxjar->taxForOrder($order);
        } catch( \Exception $e ){
            $this->message = $e->getMessage();
            return false;
        }

        return ['tax_amount' => $order_taxes->amount_to_collect, 'rate' => $order_taxes->rate ];
    }



    public function createTransaction( $checkout )
    {
        $success = true;
        $message = 'Successful';

        $order = [
            'transaction_id' => $checkout['order_id'],
            'transaction_date' => now(),
            'to_country' => $checkout['shipping_address']['country'],
            'to_zip' => $checkout['shipping_address']['zip'],
            'to_state' => $checkout['shipping_address']['state'],
            'to_city' => $checkout['shipping_address']['city'],
            'to_street' => $checkout['shipping_address']['street1'],
            'to_street2' => $checkout['shipping_address']['street2'],
            'amount' => $checkout['taxable_total'] + $checkout['shipping_amount'] ,
            'shipping' => $checkout['shipping_amount'],
            'sales_tax' => $checkout['tax_amount'],
        ];

        foreach( $checkout['items'] as $item ){
            if( $item->product->is_taxable ){
                $order['line_items'][] =
                [
                    'product_identifier' => $item['product']->sku? $item['product']->sku : $item['product']->mfg_part_number ,
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                ];
            }
        }

        try{
            $order_taxes = $this->taxjar->createOrder($order);
        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;

            return [ 'success' => $success, 'message' => $message];
        }

        $arr = [
            'success' => $success, 'message' => $message, 'tax_object_id' => $order_taxes->transaction_id
        ];

        return $arr;
    }


    public function createRefund( $arr )
    {
        $message = 'Successful';
        $success = true;
        $id = uniqid();

        try{
            $refund = $client->createRefund([
                'transaction_id' => $id,
                'transaction_reference_id' => $arr['tax_object_id'],
                'transaction_date' => now(),
                'to_country' => $arr['shipping_address']['country'],
                'to_zip' => $arr['shipping_address']['zip'],
                'to_state' => $arr['shipping_address']['state'],
                'to_city' => $arr['shipping_address']['city'],
                'to_street' => $arr['shipping_address']['street1'],
                'to_street2' => $arr['shipping_address']['street2'],
                'amount' => $arr['amount'] + $arr['shipping_amount'],
                'sales_tax' => $checkout['tax_amount'],
            ]);
        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;

            return [ 'success' => $success, 'message' => $message ];
        }

        $arr = [
            'success' => $success, 'message' => $message, 'tax_object_id' => $refund->transaction_id
        ];

        return $arr;

    }

}
