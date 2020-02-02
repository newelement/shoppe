<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Traits\CartData;

class TaxesController extends Controller
{
    use CartData;

    private $avalara;
    private $taxjar;
    private $message = 'Tax collected successful.';

    function __construct()
    {
        $sandbox = env('APP_ENV') === 'production'? '' : 'sandbox';
        $this->avalara = new \Avalara\AvaTaxClient('neutrino', '1.0', 'localhost', $sandbox);
        $this->avalara->withSecurity( config('shoppe.avalara_user') , config('shoppe.avalara_pass'));

        $this->taxjar = \TaxJar\Client::withApiKey( config('shoppe.taxjar_token_live') );
    }

    public function getTaxes( $shippingCost, $address, $cartItems = [] )
    {
        $message = 'Tax collected successful.';

        //$response = $this->alavaraFree($address);
        //$response = $this->avalaraPro($shippingCost, $address, $cartItems);
        $response = $this->taxJar($shippingCost, $address, $cartItems);

        $success = $response? true : false;
        $taxAmount = $response? $response : 0.00;

        return ['tax_amount' => $taxAmount, 'success' => $success, 'message' => $this->message];
    }

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

            return $taxAmount;

        } catch ( \Exception $e ){
            $success = false;
            $this->message = $e->getMessage();
            return false;
        }
    }

    protected function avalaraPro($shippingCost, $address, $cartItems)
    {
        $tb = new \Avalara\TransactionBuilder($this->avalara, "DEFAULT", \Avalara\DocumentType::C_SALESINVOICE, 'ABC');
        $tb->withAddress('SingleLocation', $address['street1'], $address['street2'], null, $address['city'], $address['state'], $address['zip'], $address['country']);

        foreach( $cartItems['items'] as $item ){
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

    protected function taxJar($shippingCost, $address, $cartItems)
    {

        $order = [
          'to_country' => $address['country'],
          'to_zip' => $address['zip'],
          'to_state' => $address['state'],
          'to_city' => $address['city'],
          'to_street' => $address['street1'],
          'to_street2' => $address['street2'],
          'amount' => $cartItems['sub_total'] + $shippingCost,
          'shipping' => $shippingCost,
        ];

        $i = 0;
        foreach( $cartItems['items'] as $item ){
            $order['line_items'][] =
            [
              'id' => $i,
              'quantity' => $item['qty'],
              //'product_tax_code' => '31000',
              'unit_price' => $item['price'],
              'discount' => 0
            ];
            $i++;
        }

        $order_taxes = $this->taxjar->taxForOrder($order);

        return $order_taxes->amount_to_collect;
    }

}
