<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\ShoppeSetting;
use Newelement\Shoppe\Models\ShippingClass;
use Newelement\Shoppe\Models\ShippingMethod;
use Newelement\Shoppe\Models\ShippingMethodClass;

class ShoppeSettingsController extends Controller
{

    public function index(Request $request)
    {

        $settings = collect();

        $shippingConnector = app('Shipping');

        $shippingClasses = $this->getShippingClasses();
        $shippingMethods = $this->getShippingMethods();

        $settings->shipping_classes = $shippingClasses;
        $settings->shipping_methods = $shippingMethods;
        $settings->service_levels = $shippingConnector->getServiceLevels();
        $settings->edit_method = false;

        if( $request->ajax() ){
            return response()->json(['settings' => $settings]);
        } else {
            return view('shoppe::admin.settings.index', ['settings' => $settings]);
        }
    }

    public function getShippingMethod(Request $request, $id)
    {
        $settings = collect();

        $shippingConnector = app('Shipping');

        $shippingClasses = $this->getShippingClasses();
        $shippingMethods = $this->getShippingMethods();

        $settings->shipping_classes = $shippingClasses;
        $settings->shipping_methods = $shippingMethods;
        $settings->service_levels = $shippingConnector->getServiceLevels();
        $settings->edit_method = true;
        $shipping_method = ShippingMethod::find($id);

        if( $request->ajax() ){
            return response()->json(['settings' => $settings]);
        } else {
            return view('shoppe::admin.settings.index', ['settings' => $settings, 'shipping_method' => $shipping_method]);
        }
    }

    private function getShippingClasses()
    {
        return ShippingClass::orderBy('title')->get();
    }

    private function getShippingMethods()
    {
        return ShippingMethod::orderBy('sort', 'asc')->orderBy('title')->get();
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

        $estimatedExists = ShippingMethod::where('method_type', 'estimated')->first();

        if( $estimatedExists && $request->method_type === 'estimated' ){
            return redirect()->back()->with('error', 'You can only have one estimated shipping method type.');
        }

        ShippingMethod::insert([
            'title' => $request->title,
            'service_level' => $request->service_level,
            'method_type' => $request->method_type,
            'amount' => $request->amount,
            'minimum_order_amount' => $request->minimum_order_amount,
            'estimated_days' => $request->estimated_days,
            'free_estimated_days' => $request->free_estimated_days,
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

    public function updateShippingMethod(Request $request, $id)
    {

        $validatedData = $request->validate([
           'title' => 'required|max:100',
           'method_type' => 'required',
        ]);

        ShippingMethod::where('id', $id)
            ->update([
                'title' => $request->title,
                'amount' => $request->amount,
                'method_type' => $request->method_type,
                'service_level' => $request->service_level,
                'minimum_order_amount' => $request->minimum_order_amount,
                'estimated_days' => $request->estimated_days,
                'free_estimated_days' => $request->free_estimated_days,
                'notes' => $request->notes
        ]);

        return redirect('/admin/shoppe-settings?tab=shipping&section=shipping_methods')->with('success', 'Shipping method updated.');
    }

    public function updateShippingMethodClasses(Request $request, $id)
    {
        $classes = $request->classes;

        foreach( $classes as $key => $value ){
            ShippingMethodClass::updateOrCreate(
                ['shipping_method_id' => $id, 'shipping_class_id' => $key],
                ['amount' => $value['amount'], 'calc_type' => $request->calc_type]
            );
        }

        return redirect('/admin/shoppe-settings?tab=shipping&section=shipping_methods')->with('success', 'Shipping method classes updated.');

    }

    public function updateShippingMethodsSort(Request $request)
    {
        $items = $request->items;

        $updates = [];
        $i = 0;
        foreach( $items as $id ){
            if( is_numeric($id) ){
                $updated = ShippingMethod::where('id', $id)->update(
                    [ 'sort' => $i ]
                );
                $i++;
            }
        }

        return response()->json(['sorted' => true]);
    }

    public function deleteShippingMethod($id)
    {
        ShippingMethod::where('id', $id)->delete();

        return redirect('/admin/shoppe-settings?tab=shipping&section=shipping_methods')->with('success', 'Shipping method deleted.');
    }

    public function deleteShippingClass($id)
    {
        ShippingClass::where('id', $id)->delete();

        return redirect('/admin/shoppe-settings?tab=shipping&section=shipping_classes')->with('success', 'Shipping class deleted.');
    }
}
