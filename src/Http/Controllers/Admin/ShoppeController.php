<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Http\Controllers\Admin\ShoppeAnalyticsController;
use Auth;

class ShoppeController extends Controller
{

    public function index(Request $request)
    {
        $data = [];
        $shoppeController = new ShoppeAnalyticsController;
        $shoppeData = $shoppeController->getDasboardWidgetData();
        $data['orderCount'] = getNewOrderCount();
        $data['sales_today'] = $shoppeData['sales_today'];
        $data['sales_yesterday'] = $shoppeData['sales_yesterday'];
        $data['active_carts'] = $shoppeData['active_carts'];

        if( $request->ajax() ){
            return response()->json($data);
        } else {
            return view('shoppe::admin.dashboard', $data);
        }
    }
}
