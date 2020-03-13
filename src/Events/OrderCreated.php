<?php
namespace Newelement\Shoppe\Events;
use Illuminate\Queue\SerializesModels;
use Newelement\Shoppe\Models\Order;

class OrderCreated
{
    use SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
