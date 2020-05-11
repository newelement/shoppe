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

        $startToday = Carbon::today(config('neutrino.timezone'));
        $endToday = Carbon::today(config('neutrino.timezone'))->endOfDay();

        $startYesterday = Carbon::today(config('neutrino.timezone'))->subDays(1);
        $endYesterday = Carbon::today(config('neutrino.timezone'))->subDays(1)->endOfDay();;

        $trans = Transaction::whereBetween( 'created_at',[ $startToday->timezone('UTC')->toDateTimeString(), $endToday->timezone('UTC')->toDateTimeString() ])->get();
        $trans2 = Transaction::whereBetween( 'created_at', [ $startYesterday->timezone('UTC')->toDateTimeString(), $endYesterday->timezone('UTC')->toDateTimeString() ] )->get();

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
