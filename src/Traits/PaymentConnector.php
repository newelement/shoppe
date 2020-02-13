<?php

namespace Newelement\Shoppe\Traits;

use Newelement\Shoppe\Models\Customer;
use Auth, App\User;

trait PaymentConnector
{

    public $email = '';

    public function getCustomerId()
    {
        $userExists = User::where( 'email', $this->email )->first();
        $user = Auth::check()? Auth::user() : $userExists;

        $customer = Customer::where(['user_id' => $user->id, 'payment_connector' => $this->payment_connector ])->first();

        return $customer ? $customer->customer_id : false;
    }

}
