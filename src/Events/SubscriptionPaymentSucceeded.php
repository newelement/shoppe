<?php
namespace Newelement\Shoppe\Events;
use Illuminate\Queue\SerializesModels;
use Newelement\Shoppe\Models\Subscription;

class SubscriptionPaymentSucceeded
{
    use SerializesModels;

    public $subscription;
    public $payload;

    public function __construct(Subscription $subscription, $payload)
    {
        $this->subscription = $subscription;
        $this->payload = $payload;
    }
}
