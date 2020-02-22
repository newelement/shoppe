<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\ShoppeSetting;
use Auth;

class ShoppeSettingsController extends Controller
{

    public function index(Request $request)
    {

        $settings = [];

        if( $request->ajax() ){
            return response()->json(['settings' => $settings]);
        } else {
            return view('shoppe::admin.settings.index', ['settings' => $settings]);
        }
    }
}
