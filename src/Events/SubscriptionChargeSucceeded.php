<?php
namespace Newelement\Shoppe\Events;
use Illuminate\Queue\SerializesModels;
use Newelement\Shoppe\Models\Subscription;

class SubscriptionChargeSucceeded
{
    use SerializesModels;

    public $charge;

    public function __construct($charge)
    {
        $this->charge = $charge;
    }
}
