<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\OrderLine;
use Newelement\Shoppe\Models\Transaction;
use Carbon\Carbon;
use Auth;

class ShoppeReportController extends Controller
{

    public function index(Request $request)
    {

        $gross = [];
        $net = [];
        $refunds = [];
        $taxes = [];
        $shipping = [];

        $sales = [
            'gross' => 0.00,
            'net' => 0.00,
            'refunds' => 0.00,
            'taxes' => 0.00,
            'shipping' => 0.00,
            'profit' => 0.00,
            'margin' => 0.00,
            'orders' => []
        ];

        if( $request->ajax() ){
            return response()->json(['sales' => $sales]);
        } else {
            return view('shoppe::admin.reports.index', [ 'report_type' => '', 'sales' => $sales]);
        }
    }

    public function getSales(Request $request)
    {

        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $orders = $this->getOrders( $startDate, $endDate );
        $sales = $this->calcSummaryTotals($orders);

        return view('shoppe::admin.reports.index', ['report_type' => 'sales', 'sales' => $sales]);
    }

    public function getProfit(Request $request)
    {

        $validatedData = $request->validate([
            'profit_start_date' => 'required|date',
            'profit_end_date' => 'required|date',
        ]);

        $startDate = $request->profit_start_date;
        $endDate = $request->profit_end_date;

        $orders = $this->getOrders( $startDate, $endDate );

        $sales = $this->calcProfitTotals($orders);

        return view('shoppe::admin.reports.index', ['report_type' => 'profit', 'sales' => $sales]);
    }


    private function calcProfitTotals($orders)
    {
        $sales = [
            'gross' => 0.00,
            'net' => 0.00,
            'refunds' => 0.00,
            'taxes' => 0.00,
            'shipping' => 0.00,
            'orders' => []
        ];

        $credits = [];
        $debits = [];
        $taxes = [];
        $shipping = [];
        $taxRefunds = [];
        $shippingRefunds = [];
        $costs = [];

        foreach( $orders as $order ){

            foreach( $order->transactions as $trans){
                if( $trans->transaction_type === 'credit' ){
                    $credits[] = $trans->amount;
                    $taxRefunds[] = $trans->tax_amount;
                    $shippingRefunds[] = $trans->shipping_amount;
                }
                if( $trans->transaction_type === 'debit' ){
                    $debits[] = $trans->amount;
                }
            }

            $taxes[] = $order->tax_amount;
            $shipping[] = $order->shipping_amount;

            // COSTS
            foreach( $order->orderLines as $line ){
                $costs[] = (float) $line->product->cost;
            }

        }

        $sums = [
            'amount' => array_sum($debits),
            'taxes' => array_sum($taxes) - array_sum($shippingRefunds),
            'shipping' => array_sum($shipping) - array_sum($shippingRefunds),
            'tax_refunds' => array_sum($taxRefunds),
            'shipping_refunds' => array_sum($shippingRefunds),
            'cost' => array_sum($costs)
        ];

        $net = $sums['amount'] - $sums['taxes'] - $sums['shipping'];
        $profit = $net - $sums['cost'];
        $margin = round( $profit / $net, 4) * 100;

        $sales['net'] = $net;
        $sales['cost'] = $sums['cost'];
        $sales['profit'] = $profit;
        $sales['margin'] = $margin;

        $sales['orders'] = $orders;

        return $sales;
    }

    private function calcSummaryTotals($orders){

        $sales = [
            'gross' => 0.00,
            'net' => 0.00,
            'refunds' => 0.00,
            'taxes' => 0.00,
            'shipping' => 0.00,
            'orders' => []
        ];

        $amounts = [];
        $refunds = [];
        $taxes = [];
        $shipping = [];
        $taxRefunds = [];
        $shippingRefunds = [];

        foreach( $orders as $order ){
            $lines = [];
            $transactions = $order->transactions;

            if( $order->status === 4 ){
                $refunds[] = $order->itemsTotal;
            } else {

                foreach( $transactions as $trans ){
                    if( $trans->transaction_type === 'credit' ){
                        $refunds[] = $trans->amount;
                        $taxRefunds[] = $trans->tax_amount;
                        $shippingRefunds[] = $trans->shipping_amount;
                    }
                    if( $trans->transaction_type === 'debit' ){
                        $lines[] = $trans->amount;
                    }
                }

                $taxes[] = $order->tax_amount;
                $shipping[] = $order->shipping_amount;
                $amounts[] = array_sum($lines);

            }
        }

        $sums = [
            'amount' => array_sum($amounts),
            'taxes' => array_sum($taxes) - array_sum($shippingRefunds),
            'shipping' => array_sum($shipping) - array_sum($shippingRefunds),
            'refunds' => array_sum($refunds),
            'tax_refunds' => array_sum($taxRefunds),
            'shipping_refunds' => array_sum($shippingRefunds)
        ];

        $sales['net'] = $sums['amount'] - $sums['taxes'] - $sums['shipping'];
        $sales['gross'] = $sums['amount'];
        $sales['refunds'] = $sums['refunds'];
        $sales['taxes'] = $sums['taxes'] - $sums['tax_refunds'];
        $sales['shipping'] = $sums['shipping'] - $sums['shipping_refunds'];
        $sales['tax_refunds'] = $sums['tax_refunds'];
        $sales['shipping_refunds'] = $sums['shipping_refunds'];
        $sales['orders'] = $orders;

        return $sales;
    }

    private function getOrders($startDate, $endDate)
    {
        $dates = $this->formatDates($startDate, $endDate);

        $orders = Order::
                    whereBetween( 'created_at', [$dates['start'], $dates['end']])
                    ->orderBy('created_at', 'asc')
                    ->get();
        return $orders;
    }

    private function formatDates($startDate, $endDate)
    {
        $dateS = Carbon::parse($startDate, config('neutrino.timezone'));
        $dateS = $dateS->startOfDay();
        $dateS->setTimezone(config('neutrino.timezone'));
        $start = $dateS->setTimezone('UTC');
        $start = $start->startOfDay();

        $dateE = Carbon::parse($endDate, config('neutrino.timezone'));
        $dateE = $dateE->endOfDay();
        $dateE->setTimezone(config('neutrino.timezone'));
        $end = $dateE->setTimezone('UTC');
        $end = $end->endOfDay();

        return [
            'start' => $start,
            'end' => $end
        ];
    }
}
