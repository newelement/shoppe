<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\ShoppeSetting;

class ShoppeSettingsController extends Controller
{

    public function index(Request $request)
    {
        $groups = [];
        $settings = [];
        $sets = ShoppeSetting::where('parent_id', 0)->orderBy('group_ordinal', 'asc')->get();

        foreach( $sets as $set ){
            $groups[$set->group]['name'] = $set->group;
        }

        $groups = array_values($groups);

        foreach( $groups as $key => $group ){
            $fields = ShoppeSetting::where(['parent_id' => 0, 'group' => $group ])->orderBy('ordinal', 'asc')->get();
            $groups[$key]['fields'] = $fields;
        }

        $settings['groups'] = $groups;

        if( $request->ajax() ){
            return response()->json(['settings' => $settings]);
        } else {
            return view('shoppe::admin.settings.index', ['settings' => $settings]);
        }
    }
}
