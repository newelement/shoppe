<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\ActivityLog;

class SubscriptionController extends Controller
{
    public function index()
    {
        $payment = app('Payment');
        $plans = $payment->getSubscriptionPlans();

        return view('shoppe::admin.subscriptions.index', ['subs' => $plans['plans']]);
    }

    public function showCreate()
    {
        return view('shoppe::admin.subscriptions.create');
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
           'plan_name' => 'required|max:100',
           'amount' => 'required',
           'interval' => 'required',
        ]);

        $name = $request->plan_name;
        $amount = (float) $request->amount;
        $interval = $request->interval;
        $interval_count = (int) $request->interval_count? $request->interval_count : 1 ;
        $trial = (int) $request->trial? $request->trial : 0;

        $arr = [
            'name' => $name,
            'amount' => $amount,
            'interval' => $interval,
            'interval_count' => $interval_count,
            'trial' => $trial
        ];

        $payment = app('Payment');
        $created = $payment->createSubscriptionPlan($arr);

        if( !$created['success'] ){
            return redirect()->back()->with('error', $created['message']);
        }

        return redirect('/admin/subscriptions/'.$created['id'])->with('success', 'Subscription created.');
    }

    public function get($id)
    {
        $payment = app('Payment');
        $plan = $payment->getSubscriptionPlan($id);

        if( !$plan['success'] ){
            return redirect()->back()->with('error', $plan['message']);
        }

        return view('shoppe::admin.subscriptions.edit', ['plan' => $plan['plan']]);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
           'plan_name' => 'required|max:100',
           'amount' => 'required',
           'interval' => 'required',
        ]);

        $id = $request->id;
        $name = $request->plan_name;
        $amount = (float) $request->amount;
        $interval = $request->interval;
        $interval_count = (int) $request->interval_count? $request->interval_count : 1 ;
        $trial = (int) $request->trial? $request->trial : 0;

        $arr = [
            'id' => $id,
            'name' => $name,
            'amount' => $amount,
            'interval' => $interval,
            'interval_count' => $interval_count,
            'trial' => $trial
        ];

        $payment = app('Payment');
        $updated = $payment->updateSubscriptionPlan($arr);

        if( !$updated['success'] ){
            return redirect()->back()->with('error', $updated['message']);
        }

        return redirect('/admin/subscriptions/'.$id)->with('success', 'Subscription updated.');
    }

    public function delete(Request $request, $id)
    {
        $id = $request->id;

        $payment = app('Payment');
        $delete = $payment->deleteSubscriptionPlan($id);

        if( !$delete['success'] ){
            return redirect()->back()->with('error', $delete['message']);
        }

        return redirect('/admin/subscriptions')->with('success', 'Subscription deleted.');
    }

    public function taxRates()
    {
        $payment = app('Payment');
        $rates = $payment->getTaxRates();

        return view('shoppe::admin.taxrates.index', ['rates' => $rates ]);
    }

    public function showTaxRate()
    {
        return view('shoppe::admin.taxrates.create');
    }

    public function getTaxRate($id)
    {
        $payment = app('Payment');
        $rate = $payment->getTaxRate($id);

        if( !$rate['success'] ){
            return redirect()->back()->with('error', $rate['message']);
        }

        return view('shoppe::admin.taxrates.edit', ['rate' => $rate['rate']]);
    }

    public function createTaxRate(Request $request)
    {
        $validatedData = $request->validate([
           'display_name' => 'required|max:100',
           'percentage' => 'required',
        ]);

        $name = $request->display_name;
        $description = $request->description;
        $jurisdiction = $request->jurisdiction;
        $inclusive = $request->inclusive? true : false ;
        $percentage = $request->percentage;

        $arr = [
            'display_name' => $name,
            'description' => $description,
            'jurisdiction' => $jurisdiction,
            'inclusive' => $inclusive,
            'percentage' => $percentage
        ];

        $payment = app('Payment');
        $created = $payment->createTaxRate($arr);

        if( !$created['success'] ){
            return redirect()->back()->with('error', $created['message']);
        }

        return redirect('/admin/stripe/tax-rates/'.$created['id'])->with('success', 'Tax rate created.');
    }

    public function updateTaxRate(Request $request, $id)
    {
        $validatedData = $request->validate([
            'id' => 'required',
           'display_name' => 'required|max:100',
        ]);

        $name = $request->display_name;
        $description = $request->description;
        $jurisdiction = $request->jurisdiction;
        $active = $request->active? true : false ;

        $arr = [
            'id' => $request->id,
            'display_name' => $name,
            'description' => $description,
            'jurisdiction' => $jurisdiction,
            'active' => $active
        ];

        $payment = app('Payment');
        $updated = $payment->updateTaxRate($arr);

        if( !$updated['success'] ){
            return redirect()->back()->with('error', $updated['message']);
        }

        return redirect('/admin/stripe/tax-rates/'.$id)->with('success', 'Tax rate updated.');
    }
}
