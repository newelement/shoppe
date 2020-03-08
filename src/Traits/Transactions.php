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
               $transaction->order_line_id = isset($arr['line_id'])? $arr['line_id'] : null ;
               $transaction->transaction_ref_id = $arr['transaction_id'];
               $transaction->amount = $arr['amount'];
               $transaction->tax_amount = isset($arr['tax_amount'])? $arr['tax_amount'] : 0.00 ;
               $transaction->qty = isset($arr['qty'])? $arr['qty'] : null ;
               $transaction->shipping_amount = isset($arr['shipping_amount'])? $arr['shipping_amount'] : 0.00 ;
               $transaction->transaction_type = $arr['type'];
               $transaction->notes = $arr['notes'];
               $transaction->created_by = $arr['user_id'];
               $transaction->save();
           } catch( \Exception $e ){
               $message = $e->getMessage();
           }
    }

}
