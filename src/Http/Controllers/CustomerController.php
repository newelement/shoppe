<?php
namespace Newelement\Shoppe\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Newelement\Neutrino\Facades\Neutrino;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\AddressBook;

class CustomerController extends Controller
{

    public function index()
    {
        $data = new \stdClass;
        $data->title = 'Customer Account';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';

        $orders = Order::where('user_id', auth()->user()->id )->orderBy('status', 'asc')->orderBy('created_at', 'asc')->paginate(20);

        $data->orders = $orders;

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

    public function cards()
    {
        $data = new \stdClass;
        $data->title = 'Customer Saved Cards';
        $data->data_type = 'page';
        $data->keywords = '';
        $data->meta_description = '';



        return view( 'shoppe::customer.cards' , [ 'data' => $data ]);
    }

}
