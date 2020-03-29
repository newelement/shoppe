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

            $customer = $this->createCustomer( $payment );
            $customerId = $customer['customer_id'];
            $source = $customer['source'];

            if( !$customer['success'] ){
                return [
                    'success' => false,
                    'message' => $customer['message'],
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


    private function createCustomer($payment)
    {
        $message = '';
        $success = true;
        $source = '';
        $customerId = $this->getCustomerId();

        if( !$customerId ){

            try{

                $customer = \Stripe\Customer::create([
                    'email' => $payment['email'],
                    'shipping' => [
                        'name' => $payment['shipping_address']['name'],
                        'address' => [
                            'line1' => $payment['shipping_address']['street1'],
                            'line2' => $payment['shipping_address']['street2'],
                            'city' => $payment['shipping_address']['city'],
                            'country' => $payment['shipping_address']['country'],
                            'zip' => $payment['shipping_address']['postal_code'],
                            'state' => $payment['shipping_address']['state']
                        ]
                    ]
                ]);

                $customerId = $customer->id;

                $createSource = \Stripe\Customer::createSource(
                    $customerId,
                    ['source' => $payment['token'] ]
                );

                $source = $createSource->id;

            } catch( \Exception $e ){
                $success = false;
                $message = $e->getMessage();
            }

        } else {

            try{

                $createSource = \Stripe\Customer::createSource(
                    $customerId,
                    ['source' => $payment['token'] ]
                );

                \Stripe\Customer::update(
                    $customerId,
                    [
                        'shipping' => [
                            'name' => $payment['shipping_address']['name'],
                            'address' => [
                                'line1' => $payment['shipping_address']['street1'],
                                'line2' => $payment['shipping_address']['street2'],
                                'city' => $payment['shipping_address']['city'],
                                'country' => $payment['shipping_address']['country'],
                                'zip' => $payment['shipping_address']['postal_code'],
                                'state' => $payment['shipping_address']['state']
                            ]
                        ]
                    ]
                );

                $source = $createSource->id;

            } catch( \Exception $e ){
                    $success = false;
                    $message = $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'message' => $message,
            'source' => $source,
            'customer_id' => $customerId
        ];
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

    public function getSubscriptions($customerId){
        $message = 'Successful';

        try{
            $subscriptions = \Stripe\Subscription::all(['customer' => $customerId]);
        } catch( \Exception $e ){
            return ['success' => false, 'message' => $e->getMessage() ];
        }

        return ['success' => true, 'message' => $message, 'subscriptions' => $subsrciptions ];
    }

    public function getSubscription($subscriptionId)
    {
        $message = 'Successful';
        $plans = [];

        try{
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $planData = \Stripe\Plan::all();
        } catch( \Exception $e ){
            return ['success' => false, 'message' => $e->getMessage() ];
        }

        foreach($planData['data'] as $key => $plan){
            $p = \Stripe\Product::retrieve($plan->product);
            $plan->product = $p;
            $plans[] = $plan;
        }

        return ['success' => true, 'message' => $message, 'subscription' => $subscription , 'plans' => $plans];
    }

    public function createSubscription( $checkout )
    {
        $customer = $this->createCustomer($checkout);
        $message = '';
        $success = true;
        $items = [];

        foreach( $checkout['plan_ids'] as $planId ){
            $items[] = [
                    'plan' => $planId,
                    'tax_rates' => $checkout['tax_rates']
                ];
        }

        try{
            $create = \Stripe\Subscription::create([
                'customer' => $customer['customer_id'],
                'metadata' => ['refid' => $checkout['ref_id'] ],
                'items' => $items,
                'trial_from_plan' => true
            ]);
        } catch( \Exception $e ){
            $success = false;
            $message = $e->getMessage();
            return ['success' => false, 'message' => $message];
        }

        return ['success' => $success, 'message' => $message, 'transaction_id' => $create->id, 'payload' => $create ];
    }

    public function updateSubscription($id, $args)
    {
        $message = 'Successful';

        try{
            \Stripe\Subscription::update(
              $id,
              $args
            );
        } catch( \Exception $e ) {
            return ['success' => false, 'message' => $e->getMessage() ];
        }

        return ['success' => true, 'message' => $message ];
    }

    public function cancelSubscription($id)
    {
        try{
            $subscription = \Stripe\Subscription::retrieve(
                $id
            );
            $subscription->delete();
        } catch( \Exception $e ){
            return [ 'success' => false, 'message' => $e->getMessage() ];
        }

        return [ 'success' => true, 'message' => 'Successful', 'payload' => $subscription ];
    }

    public function getProduct($productId)
    {
        $message = '';
        $success = true;

        try{
            $payload = \Stripe\Product::retrieve($productId);
        } catch( \Exception $e ){
            $success = false;
            $message = $e->getMessage();
            return ['success' => false, 'message' => $message];
        }
        return ['success' => $success, 'message' => $message, 'payload' => $payload ];
    }

    public function getSubscriptionPlans()
    {
        $success = true;
        $message = 'Successful';
        $subs = [];
        $plans = [];

        try{
            $plans = \Stripe\Plan::all();
        } catch(\Exception $e){
            $success = false;
            $message = $e->getMessage();
            return ['success' => $success, 'message' => $message];
        }

        foreach( $plans->data as $plan ){
            if( $plan->active ){
                $product = \Stripe\Product::retrieve($plan->product);
                $subs[] = [
                    'id' => $plan->id,
                    'amount' => number_format($plan->amount/100, 2),
                    'interval' => $plan->interval,
                    'interval_count' => $plan->interval_count,
                    'name' => $product->name,
                    'trial' => $plan->trial_period_days
                ];
            }
        }

        return ['success' => $success, 'message' => $message, 'plans' => $subs];
    }

    public function createSubscriptionPlan($arr)
    {
        $success = true;
        $message = 'Successful';
        $amount = $this->stripeAmount($arr['amount']);

        try{
            $create = \Stripe\Plan::create([
                'amount' => $amount,
                'currency' => strtolower( config('shoppe.currency') ),
                'interval' => $arr['interval'],
                'interval_count' => $arr['interval_count'],
                'product' => ['name' => $arr['name']],
                'trial_period_days' => $arr['trial']
            ]);
        } catch( \Exception $e ) {
            $success = false;
            $message = $e->getMessage();
            return ['success' => $success, 'message' => $message];
        }

        return ['success' => $success, 'message' => $message, 'id' => $create->id];
    }

    public function getSubscriptionPlan($id)
    {
        $success = true;
        $message = 'Successful';
        $sub = [];

        try{
            $plan = \Stripe\Plan::retrieve($id);
            $product = \Stripe\Product::retrieve($plan->product);
        } catch( \Exception $e){
            $success = false;
            $message = $e->getMessage();
            return ['success' => $success, 'message' => $message];
        }

        $sub = [
            'id' => $plan->id,
            'amount' => number_format($plan->amount/100, 2),
            'interval' => $plan->interval,
            'name' => $product->name,
            'interval_count' => $plan->interval_count,
            'trial' => $plan->trial_period_days
        ];

        return ['success' => $success, 'message' => $message, 'plan' => $sub];
    }

    public function updateSubscriptionPlan($arr)
    {
        $success = true;
        $message = 'Successful';

        try{
            \Stripe\Plan::update(
                $arr['id'],
                [
                'trial_period_days' => $arr['trial']
            ]);
        } catch( \Exception $e ) {
            $success = false;
            $message = $e->getMessage();
        }

        return ['success' => $success, 'message' => $message];
    }

    public function deleteSubscriptionPlan($id)
    {
        $success = true;
        $message = 'Successful';

        try{
            $plan = \Stripe\Plan::retrieve(
                  $id
                );
            $plan->delete();
        } catch( \Exception $e ) {
            $success = false;
            $message = $e->getMessage();
        }

        return ['success' => $success, 'message' => $message];
    }

    public function getTaxRates()
    {
        $success = true;
        $message = 'Successful';
        $rates = [];

        try{
            $taxrates = \Stripe\TaxRate::all();
        } catch(\Exception $e){
            $success = false;
            $message = $e->getMessage();
            return ['success' => $success, 'message' => $message];
        }

        foreach( $taxrates->data as $rate ){
            $rates[] = [
                'id' => $rate->id,
                'description' => $rate->description,
                'display_name' => $rate->display_name,
                'inclusive' => $rate->inclusive,
                'jurisdiction' => $rate->jurisdiction,
                'percentage' => $rate->percentage,
                'active' => $rate->active
            ];
        }

        return ['success' => $success, 'message' => $message, 'rates' => $rates ];
    }

    public function getTaxRate($id)
    {
        $success = true;
        $message = 'Successful';
        $sub = [];

        try{
            $taxrate = \Stripe\TaxRate::retrieve($id);
        } catch( \Exception $e){
            $success = false;
            $message = $e->getMessage();
            return ['success' => $success, 'message' => $message];
        }

        $rate = [
            'id' => $taxrate->id,
            'description' => $taxrate->description,
            'display_name' => $taxrate->display_name,
            'inclusive' => $taxrate->inclusive,
            'jurisdiction' => $taxrate->jurisdiction,
            'percentage' => $taxrate->percentage,
            'active' => $taxrate->active
        ];

        return ['success' => $success, 'message' => $message, 'rate' => $rate];
    }

    public function createTaxRate($arr)
    {
        $success = true;
        $message = 'Successful';

        try{
            $create = \Stripe\TaxRate::create([
                'description' => $arr['description'],
                'display_name' => $arr['display_name'],
                'inclusive' => $arr['inclusive'],
                'jurisdiction' => $arr['jurisdiction'],
                'percentage' => $arr['percentage']
            ]);
        } catch( \Exception $e ) {
            $success = false;
            $message = $e->getMessage();
            return ['success' => $success, 'message' => $message];
        }

        return ['success' => $success, 'message' => $message, 'id' => $create->id];
    }

    public function updateTaxRate($arr)
    {
        $success = true;
        $message = 'Successful';

        try{
            \Stripe\TaxRate::update(
                $arr['id'],
                [
                'active' => $arr['active'],
                'description' => $arr['description'],
                'display_name' => $arr['display_name'],
                'jurisdiction' => $arr['jurisdiction'],
            ]);
        } catch( \Exception $e ) {
            $success = false;
            $message = $e->getMessage();
        }

        return ['success' => $success, 'message' => $message];
    }

    private function stripeAmount($amount)
    {
        $amount = preg_replace('~\D~', '', $amount);
        $amount = intval($amount);
        return $amount;
    }

}
