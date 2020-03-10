<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentType extends Model
{
    use SoftDeletes;

    public static function savePayment( $checkout, $user )
    {
        $insert = self::insert([
            'user_id' => $user->id,
            'billing_id' => $checkout['billing_id'],
            'payment_connector' => $checkout['payment_connector'],
            'last_four' => isset($checkout['last_four'])? $checkout['last_four'] : null,
            'card_brand' => isset($checkout['card_brand'])? $checkout['card_brand'] : null,
            'payment_type' => isset($checkout['payment_type'])? $checkout['payment_type'] : null,
            'default' => 0,
            'created_at' => now()
        ]);

        return $insert;
    }

}
