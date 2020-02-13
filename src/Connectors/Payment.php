<?php
namespace Newelement\Shoppe\Connectors;

use \Stripe\Stripe;
use Newelement\Shoppe\Traits\CartData;
use Newelement\Shoppe\Traits\PaymentConnector;

class Payment
{
    use CartData, PaymentConnector;

    public $payment_connector = 'stripe';

    function __construct()
    {
        Stripe::setApiKey(config('shoppe.stripe_secret'));
    }


    public function charge( $payment, $saveCard = false )
    {

        return $this->chargeStripe($payment, $saveCard);

    }

    private function chargeStripe($payment, $saveCard)
    {
        $error = false;
        $transactionId = '';
        $message = '';
        $charge = '';

        $amount = $this->stripeAmount($payment['amount']);

        if( $saveCard ){

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

        if( $saveCard ){
            $arr['customer_id'] = $customerId;
        }

        return $arr;
    }

    private function stripeAmount($amount)
    {
        return preg_replace('~\D~', '', $amount);
    }

}
