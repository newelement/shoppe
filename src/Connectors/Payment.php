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

        $amount = $this->stripeAmount($payment['amount']);

        if( $payment['save_card'] ){

            $customerId = $this->getCustomerId();

            if( !$customerId ){

                try{

                    $customer = \Stripe\Customer::create([
                        'source' => $payment['token'],
                        'email' => $payment['email'],
                    ]);

                    $customerId = $customer->id;

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
            ];

        } else {

            $chargeData = [
              'amount' => $amount,
              'currency' => config('shoppe.currency', 'USD'),
              'source' => $payment['token'],
              'description' => $payment['description'],
            ];
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
            $message = 'Too many API requests.';
            $error = true;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $message = 'Invalid API parameters.';
            $error = true;
        } catch (\Stripe\Exception\AuthenticationException $e) {
            $message = 'API auth failed.';
            $error = true;
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            $message = 'Could not communicate with Stripe API. Network error.';
            $error = true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $message = 'There was a problem with the Stripe API. Generic.';
            $error = true;
        } catch (Exception $e) {
            $message = 'There was a problem with the Stripe API. Other';
            $error = true;
        }

        $arr = ['transaction_id' => $transactionId, 'success' => $error? false : true, 'message' => $message, 'payload' => $charge ];

        if( $payment['save_card'] ){
            $arr['customer_id'] = $customerId;
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

    private function stripeAmount($amount)
    {
        return preg_replace('~\D~', '', $amount);
    }

}
