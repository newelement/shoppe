<?php

namespace Newelement\Shoppe\Traits;

use Newelement\Shoppe\Models\Transaction;
use Auth, App\User;

trait Transactions
{

    public function createTransaction( $arr )
    {
         try {
               $transaction = new Transaction;
               $transaction->order_id = $arr['order_id'];
               $transaction->line_id = isset($arr['line_id'])? $arr['line_id'] : null ;
               $transaction->transaction_ref_id = $arr['transaction_id'];
               $transaction->amount = $arr['amount'];
               $transaction->transaction_type = $arr['type'];
               $transaction->notes = $arr['notes'];
               $transaction->created_by = $arr['user_id'];
               $transaction->save();
           } catch( \Exception $e ){
               $message = $e->getMessage();
           }
    }

}
