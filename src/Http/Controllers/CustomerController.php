<?php
namespace Newelement\Shoppe\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Newelement\Neutrino\Facades\Neutrino;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\User;
use Newelement\Shoppe\Models\AddressBook;
use Newelement\Shoppe\Models\Customer;
use Newelement\Shoppe\Models\Subscription;

class CustomerController extends Controller
{

    private $Payment;

    public function __construct()
    {
        $this->Payment = app('Payment');
    }

    public function index()
    {
        $data = new \stdClass;
        $data->title = 'Customer Account';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';
        $user_id = auth()->user()->id;

        $orders = Order::where('user_id', $user_id )->orderBy('status', 'asc')->orderBy('created_at', 'asc')->paginate(20);
        $subscriptions = Subscription::where('user_id', $user_id)->get();
        $data->orders = $orders;
        $data->subscriptions = $subscriptions;

        return view( 'shoppe::customer.index' , [ 'data' => $data ]);
    }

    public function order($id)
    {
        $order = Order::where(['user_id' => auth()->user()->id, 'id' => $id ])->first();

        if( !$order ){
            abort(404);
        }

        $data = new \stdClass;
        $data->title = 'Customer Order';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';

        $data->order = $order;

        return view( 'shoppe::customer.order' , [ 'data' => $data ]);
    }

    public function security()
    {
        $data = new \stdClass;
        $data->title = 'Customer Security';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';

        return view( 'shoppe::customer.security' , [ 'data' => $data ]);
    }

    public function securityChangePassword(Request $request)
    {

        $validatedData = $request->validate(
            [
                'current_password' => 'required',
                'password' => 'required|confirmed',
            ]
        );

        if( Hash::check($request->current_password, auth()->user()->password) ){
            $request->user()->fill([
                'password' => Hash::make($request->password)
            ])->save();
        } else {
            return redirect()->back()->with('error', 'Your current password is incorrect.');
        }

        return redirect( '/'.config('shoppe.slugs.customer_account', 'customer-account').'/security' )->with('success', 'Password updated.');

    }

    public function addresses()
    {
        $data = new \stdClass;
        $data->title = 'Customer Addresses';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';

        $data->addresses = AddressBook::where(
                            ['user_id' => auth()->user()->id,
                            'address_type' => 'SHIPPING' ]
                            )
                            ->orderBy('default', 'desc')
                            ->get();

        return view( 'shoppe::customer.addresses' , [ 'data' => $data ]);
    }

    public function addressUpdate(Request $request, $id)
    {

        $validatedData = $request->validate(
            [
                'name' => 'required|max:255',
                'address' => 'required|max:400',
                'city' => 'required|max:255',
                'state' => 'required|max:3',
                'country' => 'required|max:3',
                'zipcode' => 'required|max:20',
            ]
        );

        $address = AddressBook::where([
            'id' => $id,
            'user_id' => auth()->user()->id
        ])->first();

        if( !$address ){
            abort(404);
        }

        $address->name = $request->name;
        $address->company_name = $request->company_name;
        $address->address = $request->address;
        $address->address2 = $request->address2;
        $address->city = $request->city;
        $address->state = $request->state;
        $address->zipcode = $request->zipcode;
        $address->country = $request->country;
        $address->save();

        return redirect( '/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses' )->with('success', 'Address updated.');

    }

    public function addressDelete($id)
    {
        $address = AddressBook::where([
            'id' => $id,
            'user_id' => auth()->user()->id
        ])->first();

        if( !$address ){
            abort(404);
        }

        AddressBook::where([
            'id' => $id,
            'user_id' => auth()->user()->id
        ])->delete();

        return redirect( '/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses' )->with('success', 'Address deleted.');

    }

    public function addressDefault($id)
    {
        $address = AddressBook::where([
            'id' => $id,
            'user_id' => auth()->user()->id
        ])->first();

        if( !$address ){
            abort(404);
        }

        AddressBook::where([
            'address_type' => 'SHIPPING',
            'user_id' => auth()->user()->id
        ])->update([
            'default' => 0
        ]);

        AddressBook::where([
            'id' => $id,
            'user_id' => auth()->user()->id
        ])->update([
            'default' => 1
        ]);

        return redirect( '/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses' )->with('success', 'Default address set.');

    }

    public function addressCreate(Request $request)
    {

        $validatedData = $request->validate(
            [
                'name' => 'required|max:255',
                'address' => 'required|max:400',
                'city' => 'required|max:255',
                'state' => 'required|max:3',
                'country' => 'required|max:3',
                'zipcode' => 'required|max:20',
            ]
        );

        $default = $request->default? 1 : 0;

        if( $default ){
            AddressBook::where([
                'address_type' => 'SHIPPING',
                'user_id' => auth()->user()->id
            ])->update([
                'default' => 0
            ]);
        }

        $address = new AddressBook;
        $address->user_id = auth()->user()->id;
        $address->address_type = 'SHIPPING';
        $address->name = $request->name;
        $address->company_name = $request->company_name;
        $address->address = $request->address;
        $address->address2 = $request->address2;
        $address->city = $request->city;
        $address->state = $request->state;
        $address->zipcode = $request->zipcode;
        $address->country = $request->country;
        $address->default = $default;
        $address->save();

        return redirect( '/'.config('shoppe.slugs.customer_account', 'customer-account').'/addresses' )->with('success', 'Address created.');

    }

    public function cards()
    {
        $data = new \stdClass;
        $data->title = 'Customer Saved Cards';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';

        $shippingConnector = app('Shipping');
        $taxesConnector = app('Taxes');

        $data->payment_connector = $this->Payment->connector_name;
        $data->tax_connector = $taxesConnector->connector_name;
        $data->shipping_connector = $shippingConnector->connector_name;

        $customer = Customer::where('user_id', auth()->user()->id )->first();

        if( !$customer ){
            return [
                'success' => false, 'message' => 'User does not have a customer account'
            ];
        }

        $paymentTypes = $this->Payment->getStoredPaymentTypes($customer->customer_id);
        $data->payment_types = $paymentTypes;

        return view( 'shoppe::customer.cards' , [ 'data' => $data ]);
    }

    public function cardsUpdate(Request $request, $id)
    {

        $validatedData = $request->validate(
            [
                'exp_month' => 'required|numeric',
                'exp_year' => 'required|numeric',
                'zipcode' => 'required|max:20',
            ]
        );

        $customer = Customer::where('user_id', auth()->user()->id )->first();

        if( !$customer ){
            return redirect()->back()->with('error', 'Customer not found.');
        }

        $fields = [
            'zipcode' => $request->zipcode,
            'exp_month' => $request->exp_month,
            'exp_year' => $request->exp_year
        ];

        $update = $this->Payment->updateStoredPaymentType($customer->customer_id, $id, $fields);

        if( $update['success'] ){
            return redirect()->back()->with('success', 'Payment type updated.' );
        } else {
            return redirect()->back()->with('error', $update['message'] );
        }
    }

    public function cardsCreate(Request $request)
    {

        $validatedData = $request->validate(
            [
                'token' => 'required',
            ]
        );

        $token = $request->token;
        $customer = Customer::where('user_id', auth()->user()->id )->first();

        if( !$customer ){
            return redirect()->back()->with('error', 'Customer not found. You must make a purchase first before you can add a payment type.');
        }

        $added = $this->Payment->createStoredPaymentType($customer->customer_id, $token);

        if( $added['success'] ){
            return redirect()->back()->with('success', 'Payment type added.' );
        } else {
            return redirect()->back()->with('error', $added['message'] );
        }
    }

    public function cardsDelete($id)
    {
        $customer = Customer::where('user_id', auth()->user()->id )->first();

        if( !$customer ){
            return redirect()->back()->with('error', 'Customer not found.');
        }

        $delete = $this->Payment->deleteStoredPaymentType($customer->customer_id, $id);

        if( $delete['success'] ){
            return redirect()->back()->with('success', 'Payment type deleted.' );
        } else {
            return redirect()->back()->with('error', $delete['message'] );
        }

    }

    public function cardsDefault($id)
    {
        $customer = Customer::where('user_id', auth()->user()->id )->first();

        if( !$customer ){
            return redirect()->back()->with('error', 'Customer not found.');
        }

        $default = $this->Payment->defaultStoredPaymentType($customer->customer_id, $id);

        if( $default['success'] ){
            return redirect()->back()->with('success', 'Payment type default set.' );
        } else {
            return redirect()->back()->with('error', $default['message'] );
        }

    }

    public function getSubscriptions()
    {
        $data = new \stdClass;
        $data->title = 'Subscriptions';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';

        $data->subscriptions = Subscription::where(
                                ['user_id' => auth()->user()->id]
                            )
                            ->orderBy('stripe_status', 'asc')
                            ->get();

        return view( 'shoppe::customer.subscriptions' , [ 'data' => $data ]);
    }

    public function cancelSubscription(Request $request, $id)
    {

        $sub = Subscription::where(
                                [
                                'user_id' => auth()->user()->id,
                                'stripe_id' => $id
                                ]
                            )
                            ->first();

        if( !$sub ){
            return redirect()->back()->with('error', 'There was an issue locating your subscription.');
        }

        $sub->stripe_status = 'canceled';
        $sub->save();

        $paymentConnector = app('Payment');
        $canceled = $paymentConnector->cancelSubscription($id);

        if( $canceled['success'] ){
            return redirect()->back()->with('success', 'Your subscription was canceled.');
        } else {
            return redirect()->back()->with('success', $canceled['message']);
        }

    }

}
