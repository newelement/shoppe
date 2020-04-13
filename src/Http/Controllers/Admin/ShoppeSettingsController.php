<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\ShoppeSetting;
use Newelement\Shoppe\Models\ShippingClass;
use Newelement\Shoppe\Models\ShippingMethod;

class ShoppeSettingsController extends Controller
{

    public function index(Request $request)
    {

        $settings = collect();

        $shippingClasses = $this->getShippingClasses();
        $shippingMethods = $this->getShippingMethods();

        $settings->shipping_classes = $shippingClasses;
        $settings->shipping_methods = $shippingMethods;

        if( $request->ajax() ){
            return response()->json(['settings' => $settings]);
        } else {
            return view('shoppe::admin.settings.index', ['settings' => $settings]);
        }
    }

    private function getShippingClasses()
    {
        return ShippingClass::orderBy('title')->get();
    }

    private function getShippingMethods()
    {
        return ShippingMethod::orderBy('title')->get();
    }

    public function createShippingClass(Request $request)
    {

        $validatedData = $request->validate([
           'title' => 'required|max:100',
        ]);

        ShippingClass::insert([
            'title' => $request->title,
            'notes' => $request->notes
        ]);

        return redirect()->back()->with('success', 'Shipping class created.');
    }

    public function createShippingMethod(Request $request)
    {

        $validatedData = $request->validate([
           'title' => 'required|max:100',
           'method_type' => 'required',
        ]);

        ShippingMethod::insert([
            'title' => $request->title,
            'service_level' => $request->service_level,
            'amount' => $request->amount,
            'estimated_days' => $request->estimated_days,
            'notes' => $request->notes
        ]);

        return redirect()->back()->with('success', 'Shipping method created.');
    }

    public function updateShippingClasses(Request $request)
    {

        $validatedData = $request->validate([
           'shipping_classes.*.title' => 'required|max:100',
        ]);

        $shippingClasses = $request->shipping_classes;

        foreach( $shippingClasses as $shippingClass ){
            ShippingClass::where('id', $shippingClass['id'])
            ->update([
                'title' => $shippingClass['title'],
                'notes' => $shippingClass['notes']
            ]);
        }

        return redirect()->back()->with('success', 'Shipping classes updated.');
    }

    public function updateShippingMethods(Request $request)
    {

        $validatedData = $request->validate([
           'shipping_methods.*.title' => 'required|max:100',
           'shipping_methods.*.method_type' => 'required',
        ]);

        $shippingMethods = $request->shipping_methods;

        foreach( $shippingMethods as $shippingMethod ){
            ShippingMethod::where('id', $shippingMethod['id'])
            ->update([
                'title' => $shippingMethod['title'],
                'amount' => $shippingMethod['amount'],
                'service_level' => $shippingMethod['service_level'],
                'estimated_days' => $shippingMethod['estimated_days'],
                'notes' => $shippingMethod['notes']
            ]);
        }

        return redirect()->back()->with('success', 'Shipping methods updated.');
    }
}
