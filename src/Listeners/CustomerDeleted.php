<?php

namespace Newelement\Shoppe\Listeners;

use Newelement\Shoppe\Models\Subscription;
use Newelement\Shoppe\Models\Customer;
use Newelement\Neutrino\Models\ActivityLog;
use Spatie\WebhookClient\Models\WebhookCall;


class CustomerDeleted
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  OrderShipped  $event
     * @return void
     */
    public function handle(WebhookCall $webhookCall)
    {
        $payload = $webhookCall->payload;

        $customer = Customer::where('customer_id', $payload->data->object->id)->first();
        if( $customer ){
            $userId = $customer->user_id;
            Subscription::where('user_id', $userId)->delete();
            $customer->delete();
        }
    }
}
