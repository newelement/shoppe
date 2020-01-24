<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Cart;
use Newelement\Shoppe\Models\Product;
use Newelement\Neutrino\Models\Page;
use Newelement\Shoppe\Models\ProductVariation;
use Newelement\Shoppe\Traits\CartData;
use Auth;

class CartController extends Controller
{
    use CartData;

    public function index(Request $request)
    {
        $cartItems = $this->getCartItems();
        $data = Page::where('slug', 'cart')->first();
        $data->data_type = 'page';
        $data->items = $cartItems['items'];
        $data->sub_total = $cartItems['sub_total'];
        if( $request->ajax() ){
            return response()->json($data);
        } else {
            return view('shoppe::cart', ['data' => $data]);
        }
    }

    public function create(Request $request)
    {
        $productId = $request->product_id;
        $attributes = $request->product_attributes;
        $variations = $request->variations;
        $qty = (int) $request->qty;
        $variation_id = (int) $request->variation_id;
        $variationSets = [];
        $attributeSets = [];
        $cartUser = $this->getCartUser();
        $exists = false;

        if($attributes){
            $attributeIds = array_keys($attributes);
        }

        foreach( $attributes as $key => $value ){
            if( isset($variations[$key]) && !$variations[$key] ){

                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' => 'Please choose all product options.'], 400);
                } else {
                    return response()->back()->with('error', 'Please choose all product options.');
                }
            }
            $variationSets[] = [ $value => $variations[$key] ];
        }

        // Does this item already exist in the cart?
        $product = Cart::where([ 'user_id' => $cartUser, 'product_id' => $productId ])
                    ->orWhere(['temp_user_id' => $cartUser, 'product_id' => $productId ])
                    ->first();

        if( $product ){
            $currVariationSet = serialize( json_decode($product->variation_set, true) );
            $same =  $currVariationSet === serialize( $variationSets) ;
            if( $same ){
                $exists = $product;
            }
        }

        if( $exists ){
            $updatedQty = $qty + $exists->qty;
            $cart = Cart::find($exists->id);
            $cart->qty = $updatedQty;
            $cart->save();
        } else {
            $cart = new Cart;
            if( Auth::check() ){
                $cart->user_id = Auth::user()->id;
            } else {
                $cart->temp_user_id = $cartUser;
            }
            $cart->product_id = $productId;
            $cart->variation_set = json_encode($variationSets);
            $cart->attribute_ids = json_encode($attributeIds);
            $cart->variation_id = $variation_id ? $variation_id : 0 ;
            $cart->qty = $qty? $qty : 1;
            $cart->save();

        }

        if( $request->ajax() ){
            return response()->json(['success' => true]);
        } else {
            return redirect('/cart')->with('success', 'Item added to cart.');
        }
    }

    public function update(Request $request)
    {
        $id = $request->id;
        $qty = (int) $request->qty;

        $cartUser = $this->getCartUser();
        $where = [ 'user_id' => $cartUser, 'id' => $id ];
        $orWhere = ['temp_user_id' => $cartUser, 'id' => $id ];

        if( !$qty || $qty === 0 ){
            $update = Cart::where($where)->orWhere($orWhere)->delete();
        } else {
            $update = Cart::where($where)->orWhere($orWhere)->update([
                'qty' => $qty
            ]);
        }

        $alert = $update ? 'success' : 'error';
        $success = $update ? true : false;
        $code = $update ? 200 : 404;
        $message = $update ? 'Item qty updated.' : 'Item qty was not updated.';

        if( $request->ajax() ){
            return response()->json(['success' => $success, 'message' => $message], $code);
        } else {
            return redirect('/cart')->with($alert, $message);
        }
    }

    public function delete(Request $request)
    {
        $id = $request->id;
        $cartUser = $this->getCartUser();
        $delete = Cart::where([ 'user_id' => $cartUser, 'id' => $id ])->orWhere(['temp_user_id' => $cartUser, 'id' => $id ])->delete();
        $alert = $delete ? 'success' : 'error';
        $success = $delete ? true : false;
        $code = $delete ? 200 : 404;
        $message = $delete ? 'Item removed from cart.' : 'Item was not removed from cart.';

        if( $request->ajax() ){
            return response()->json(['success' => $success, 'message' => $message], $code);
        } else {
            return redirect('/cart')->with($alert, $message);
        }
    }

}
