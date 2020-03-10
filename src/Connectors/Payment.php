<?php
namespace Newelement\Shoppe\Connectors;

use \Stripe\Stripe;
use Newelement\Shoppe\Traits\CartData;
use Newelement\Shoppe\Traits\PaymentConnector;

class Payment
{
    use CartData, PaymentConnector;

    public $connector_name = 'shoppe_stripe';

    function __construct()
    {
        Stripe::setApiKey(config('shoppe.stripe_secret'));
    }

    public function charge( $payment )
    {
        $error = false;
        $transactionId = '';
        $message = '';
        $charge = '';
        $source = $payment['token'];
        $chargeData = [];

        $amount = $this->stripeAmount($payment['amount']);

        if( $payment['save_card'] ){

            $customerId = $this->getCustomerId();

            if( !$customerId ){

                try{

                    $customer = \Stripe\Customer::create([
                        'email' => $payment['email'],
                    ]);

                    $customerId = $customer->id;

                    $createSource = \Stripe\Customer::createSource(
                        $customerId,
                        ['source' => $payment['token'] ]
                    );

                    $source = $createSource->id;

                } catch( \Exception $e ){
                    $error = true;
                    $message = $e->getMessage();
                }

            } else {

                try{

                    $createSource = \Stripe\Customer::createSource(
                        $customerId,
                        ['source' => $payment['token'] ]
                    );

                    $source = $createSource->id;

                } catch( \Exception $e ){
                    $error = true;
                    $message = $e->getMessage();
                }
            }

            if( $error ){
                return [
                    'success' => $error? false : true,
                    'message' => $message,
                    'transaction_id' => ''
                ];
            }

            $chargeData = [
                'amount' => $amount,
                'currency' => config('shoppe.currency', 'USD'),
                'customer' => $customerId,
                'source' => $source
            ];

        } else {

            $chargeData = [
              'amount' => $amount,
              'currency' => config('shoppe.currency', 'USD'),
              'source' => $source,
              'description' => $payment['description'],
            ];
        }

        if( $payment['is_stored_payment'] ){
            $chargeData['customer'] = $this->getCustomerId();
        }

        try{

            $charge = \Stripe\Charge::create( $chargeData );
            $transactionId = $charge->id;
            $message = 'Successful';

        } catch( \Stripe\Exception\CardException $e ) {
            $error = true;
            $code = $e->getError()->code;
            $message = $code.': '.$e->getError()->message;
        } catch (\Stripe\Exception\RateLimitException $e) {
            $message = 'Payment service: Too many API requests.';
            $error = true;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $message = 'Payment service: Invalid API parameters.';
            $error = true;
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $message = 'Payment service: API auth failed.';
            $error = true;
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $message = 'Payment service: Could not communicate with Stripe API. Network error.';
            $error = true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $message = 'Payment service: There was a problem with the Stripe API.';
            $error = true;
        } catch (Exception $e) {
            $message = 'Payment service: There was a problem with the Stripe API. Other.';
            $error = true;
        }

        $arr = [
            'transaction_id' => $transactionId,
            'success' => $error? false : true,
            'message' => $message,
            'payload' => $charge
        ];

        if( $payment['save_card'] ){
            $arr['customer_id'] = $customerId;
            $arr['billing_id'] = $source;
            $arr['last_four'] = $charge->payment_method_details->card->last4;
            $arr['card_brand'] = $charge->payment_method_details->card->brand;
            $arr['payment_type'] = $charge->payment_method_details->type;
        }

        if( $payment['is_stored_payment'] ){
            $arr['last_four'] = $charge->payment_method_details->card->last4;
            $arr['card_brand'] = $charge->payment_method_details->card->brand;
            $arr['payment_type'] = $charge->payment_method_details->type;
        }

        return $arr;
    }


    public function getCharge( $id )
    {
        $error = false;
        $message = '';

        try{
            $charge = \Stripe\Charge::retrieve($id);
            $message = 'Successful';
        } catch( \Exception $e ) {
            $error = true;
            $message = 'There was an issue getting the charge. '.$e->getMessage();
            return [ 'success' => false, 'message' => $message ];
        }

        $arr = [
            'success' => true,
            'message' => $message,
            'status' => $charge->status,
            'amount' => number_format($charge->amount/100, 2, '.', '' ),
            'billing_details' => [
                'email' => $charge->billing_details->email,
                'name' => $charge->billing_details->name,
                'phone' => $charge->billing_details->phone,
                'address' => [
                   'street1' => $charge->billing_details->address->line1,
                   'street2' => $charge->billing_details->address->line2,
                   'city' => $charge->billing_details->address->city,
                   'state' => $charge->billing_details->address->state,
                   'zip' => $charge->billing_details->address->postal_code,
                   'country' => $charge->billing_details->address->country
                ],
            ],
            'payment_details' => [
                'type' => $charge->payment_method_details->type,
                'method' => $charge->payment_method_details->card->brand,
                'last_four' => $charge->payment_method_details->card->last4
            ],
            'payload' => $charge
        ];

        return $arr;
    }

    public function createRefund( $charge )
    {
        $message = 'Successful';
        $success = true;
        $transactionId = '';
        $refund = '';

        $total = $charge['amount'] + $charge['tax_amount'] + $charge['shipping_amount'];

        try{
            $refund = \Stripe\Refund::create([
              'charge' => $charge['transaction_id'],
              'amount' => $this->stripeAmount($total)
            ]);
        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;
        }

        if( $success ){

            $transactionId = $refund->id;

            if( $refund->status !== 'succeeded' ){
                $success = false;
                $message = 'Error processing refund';
            }
        }

        return ['success' => $success, 'message' => $message, 'transaction_id' => $transactionId, 'payload' => $refund ];
    }

    public function getStoredPaymentTypes($customerId)
    {
        $message = 'Successful';
        $success = true;
        $payment_types = [];

        try{

            $customer = \Stripe\Customer::retrieve($customerId);

            $cards = \Stripe\Customer::allSources(
                $customerId,
                [ 'object' => 'card' ]
            );

            foreach( $cards->data as $card ){
                $default = $card->id === $customer->default_source? true : false;
                $payment_types[] = [
                    'type' => 'card',
                    'id' => $card->id,
                    'card_brand' => $card->brand,
                    'exp_month' => $card->exp_month,
                    'exp_year' => $card->exp_year,
                    'name' => $card->name,
                    'zip' => $card->address_zip,
                    'last_four' => $card->last4,
                    'default' => $default
                ];
            }

        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;
        }

        return ['success' => $success, 'message' => $message, 'items' => $payment_types];
    }

    public function updateStoredPaymentType($customerId, $billingId, $fields)
    {
        $message = 'Successful';
        $success = true;
        $payment_types = [];

        try{

            \Stripe\Customer::updateSource(
                $customerId,
                $billingId,
                [
                    'address_zip' => $fields['zipcode'],
                    'exp_month' => $fields['exp_month'],
                    'exp_year' => $fields['exp_year'],
                ]
            );
        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;
        }

        return ['success' => $success, 'message' => $message ];
    }

    public function createStoredPaymentType($customerId, $token)
    {
        $message = 'Successful';
        $success = true;
        $payment_types = [];

        try{

            \Stripe\Customer::createSource(
              $customerId,
              ['source' => $token]
            );

        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;
        }

        return ['success' => $success, 'message' => $message ];
    }

    public function deleteStoredPaymentType($customerId, $billingId)
    {
        $message = 'Successful';
        $success = true;

        try{
            $delete = \Stripe\Customer::deleteSource(
                $customerId,
                $billingId
            );
        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;
        }

        return ['success' => $success, 'message' => $message ];
    }

    public function defaultStoredPaymentType($customerId, $billingId)
    {
        $message = 'Successful';
        $success = true;

        try{
            \Stripe\Customer::update(
              $customerId,
              [ 'default_source' => $billingId ]
            );
        } catch( \Exception $e ){
            $message = $e->getMessage();
            $success = false;
        }

        return ['success' => $success, 'message' => $message ];
    }

    private function stripeAmount($amount)
    {
        return preg_replace('~\D~', '', $amount);
    }

}
