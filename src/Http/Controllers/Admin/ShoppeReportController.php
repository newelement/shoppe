<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\OrderLine;
use Newelement\Shoppe\Traits\Transactions;
use Auth;

class ShoppeReportController extends Controller
{

    public function index(Request $request)
    {

        $reports = [];

        if( $request->ajax() ){
            return response()->json(['reports' => $reports]);
        } else {
            return view('shoppe::admin.reports.index', ['reports' => $reports]);
        }
    }
}
