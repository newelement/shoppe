<?php

namespace Newelement\Shoppe\Listeners;

use Newelement\Shoppe\Models\Subscription;
use Newelement\Neutrino\Models\ActivityLog;
use Newelement\Shoppe\Events\SubscriptionChargeSucceeded;
use Spatie\WebhookClient\Models\WebhookCall;

class ChargeSucceeded
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

        event(new SubscriptionChargeSucceeded($payload));

    }
}
