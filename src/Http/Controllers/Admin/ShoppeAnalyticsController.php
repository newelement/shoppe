<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\Transaction;
use Newelement\Shoppe\Models\Cart;
use Carbon\Carbon;

class ShoppeAnalyticsController extends Controller
{

    public function index()
    {
        $smallWidgets = $this->getDasboardWidgetData();
    }

    public function getDasboardWidgetData()
    {
        $data = [];

        $date = Carbon::parse(date('Y-m-d'))->startOfDay();
        //$date->setTimezone(config('neutrino.timezone'));
        $date2 = Carbon::parse(date('Y-m-d'))->startOfDay();
        //$date2->setTimezone(config('neutrino.timezone'));
        $today = $date;
        $yesterday = $date2->subDays(1);

        $trans = Transaction::whereDate( 'created_at', $today)->get();
        $trans2 = Transaction::whereDate( 'created_at', $yesterday )->get();

        $debits = $trans->where('transaction_type', 'debit')->sum('amount');
        $credits = $trans->where('transaction_type', 'credit')->sum('amount');
        $salesToday = $debits - $credits;

        $debits2 = $trans2->where('transaction_type', 'debit')->sum('amount');
        $credits2 = $trans2->where('transaction_type', 'credit')->sum('amount');
        $salesYesterday = $debits2 - $credits2;

        $activeCarts = Cart::whereDate('updated_at', '>', now()->subDays(14)->startOfDay()->toDateTimeString() )
                        ->select('user_id', 'temp_user_id')
                        ->groupBy('user_id', 'temp_user_id')
                        ->get();

        $data['sales_today'] = $salesToday;
        $data['sales_yesterday'] = $salesYesterday;
        $data['active_carts'] = $activeCarts;

        return $data;
    }

    private function formatDates($startDate, $endDate = false)
    {
        $end = false;

        $dateS = Carbon::parse($startDate, config('neutrino.timezone'));
        $dateS = $dateS->startOfDay();
        $dateS->setTimezone(config('neutrino.timezone'));
        $start = $dateS->setTimezone('UTC');
        $start = $start->startOfDay();

        if( $endDate ){
            $dateE = Carbon::parse($endDate, config('neutrino.timezone'));
            $dateE = $dateE->endOfDay();
            $dateE->setTimezone(config('neutrino.timezone'));
            $end = $dateE->setTimezone('UTC');
            $end = $end->endOfDay();
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }
}
