<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\ActivityLog;
use Newelement\Shoppe\Models\DiscountCode;

class DiscountCodeController extends Controller
{

    public function index()
    {
        $edit = collect();
        $edit->edit = false;
        $edit->code = '';
        $edit->type = '';
        $edit->amount_type = 'DOLLAR';
        $edit->amount = '';
        $edit->percent = '';
        $edit->expires_on = '';
        $edit->minimum_order_amount = '';
        $edit->notes = '';
        $edit->internal_notes = '';

        $codes = DiscountCode::orderBy('code', 'asc')->get();

        return view('shoppe::admin.discount-codes.index', ['edit' => $edit, 'codes' => $codes]);
    }

    public function get($id)
    {
        $edit = DiscountCode::findOrFail($id);
        $edit->edit = true;

        $codes = DiscountCode::orderBy('code', 'asc')->get();

        return view('shoppe::admin.discount-codes.index', ['edit' => $edit, 'codes' => $codes]);
    }

    public function create(Request $request)
    {

        $validatedData = $request->validate([
            'amount_type' => 'required',
            'type' => 'required',
        ]);

        $code = strtoupper($request->code);
        $exists = DiscountCode::where('code', $code)->exists();

        if( $exists ){
            return redirect()->back()->with('error', 'Discount code already exists.');
        }

        if( !$code ){
            $code = $this->generateCode();
        }

        $code = $this->filterCode($code);

        $discountCode = new DiscountCode;
        $discountCode->code = $code;
        $discountCode->type = $request->type;
        $discountCode->amount_type = $request->amount_type;
        $discountCode->amount = $request->amount;
        $discountCode->percent = $request->percent;
        $discountCode->minimum_order_amount = $request->minimum_order_amount;
        $discountCode->expires_on = $request->expires_on? \Carbon\Carbon::create($request->expires_on) : null ;
        $discountCode->notes = htmlentities($request->notes);
        $discountCode->internal_notes = $request->internal_notes;
        $discountCode->save();

        return redirect()->back()->with('success', 'Discount code, '.$code.' , created.');
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'amount_type' => 'required',
            'type' => 'required',
        ]);

        $discountCode = DiscountCode::findOrFail($id);

        $code = $request->code;

        if( $discountCode->code !== strtoupper($code) ){
            $exists = DiscountCode::where('code', strtoupper($code) )->exists();
            if( $exists ){
                return redirect()->back()->with('error', 'Discount code already exists.');
            }
        }

        if( !$code ){
            $code = $this->generateCode();
        }

        $code = $this->filterCode($code);

        $discountCode = DiscountCode::findOrFail($id);
        $discountCode->code = $code;
        $discountCode->type = $request->type;
        $discountCode->amount_type = $request->amount_type;
        $discountCode->amount = $request->amount;
        $discountCode->percent = $request->percent;
        $discountCode->minimum_order_amount = $request->minimum_order_amount;
        $discountCode->expires_on = $request->expires_on? \Carbon\Carbon::create($request->expires_on) : null ;
        $discountCode->notes = htmlentities($request->notes);
        $discountCode->internal_notes = $request->internal_notes;
        $discountCode->save();

        return redirect()->back()->with('success', 'Discount code, '.$code.' , updated.');

    }

    public function delete($id)
    {
        DiscountCode::find($id)->delete();

        return redirect()->back()->with('success', 'Discount code deleted.');
    }

    private function generateCode()
    {
        $chars = "123456789ABCDEFGHIJKLMNPQRSTUVWXYZ";
        $res = "";
        for ($i = 0; $i < 8; $i++) {
            $res .= $chars[mt_rand(0, strlen($chars)-1)];
        }
        return $res;
    }

    private function filterCode($code)
    {
        return strtoupper( preg_replace('/[^a-zA-Z0-9-]/', '', $code) );
    }

}
