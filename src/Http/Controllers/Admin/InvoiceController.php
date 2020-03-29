<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Customer;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Traits\Transactions;
use Newelement\Neutrino\Models\ActivityLog;

class InvoiceController extends Controller
{
    use Transactions;

    public function __construct()
    {}

    public function create(Request $request)
    {


        /*
        $transArr = [
            'type' => 'debit',
            'amount' => '',
            'order_id' => '',
            'invoice_id' => '',
            'transaction_id' => '',
            'notes' => '',
            'transaction_on' => 'invoice',
            'user_id' => ''
        ];
        $this->createTransaction( $transArr );

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'invoice.payment',
            'content' => '',
            'log_level' => 1,
            'created_by' => '',
            'created_at' => now()
        ]);
        */

    }

}
