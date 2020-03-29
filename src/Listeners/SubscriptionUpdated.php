<?php

namespace Newelement\Shoppe\Listeners;

use Newelement\Shoppe\Models\Subscription;
use Newelement\Neutrino\Models\ActivityLog;
use Newelement\Shoppe\Events\SubscriptionChanged;
use Spatie\WebhookClient\Models\WebhookCall;

class SubscriptionUpdated
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
        $subscription = Subscription::where('stripe_id', $payload['data']['object']['id'])->first();
        event(new SubscriptionPaymentSucceeded($subscription, $payload));

    }
}
