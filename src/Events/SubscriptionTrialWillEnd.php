<?php
namespace Newelement\Shoppe\Events;
use Illuminate\Queue\SerializesModels;
use Newelement\Shoppe\Models\Subscription;

class SubscriptionTrialWillEnd
{
    use SerializesModels;

    public $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
