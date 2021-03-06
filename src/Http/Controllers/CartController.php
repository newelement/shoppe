<?php
namespace Newelement\Shoppe\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\Cart;
use Newelement\Shoppe\Models\Product;
use Newelement\Neutrino\Models\Page;
use Newelement\Shoppe\Models\ProductVariation;
use Newelement\Shoppe\Traits\CartData;
use Newelement\Shoppe\Events\ItemAddedToCart;
use Newelement\Shoppe\Events\ItemDeletedFromCart;
use Newelement\Neutrino\Models\ActivityLog;
use Auth;

class CartController extends Controller
{
    use CartData;

    public function index(Request $request)
    {
        $cartItems = $this->getCartItems();
        $inventoryConnector = app('Inventory');
        $data = Page::where('slug', 'cart')->first();
        $data->data_type = 'page';
        $data->items = $cartItems['items'];
        $data->sub_total = $cartItems['sub_total'];
        // Check stock
        if( getShoppeSetting('manage_stock') ){
            $checkStock = $inventoryConnector->checkCartStock($cartItems['items']);
            if( !$checkStock['success'] ){
                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' =>  $checkStock['message'] ], 500);
                } else {
                    session()->flash('error', $checkStock['message']);
                }
            }
        }

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
        $attributeIds = [];
        $cartUser = $this->getCartUser();
        $exists = false;
        $cartProduct = false;

        if($attributes){
            $attributeIds = array_keys($attributes);
        }

        foreach( (array) $attributes as $key => $value ){
            if( isset($variations[$key]) && !$variations[$key] ){

                if( $request->ajax() ){
                    return response()->json(['success' => false, 'message' => 'Please choose all product options.'], 400);
                } else {
                    return response()->back()->with('error', 'Please choose all product options.');
                }
            }
            $variationSets[] = [ $value => $variations[$key] ];
        }

        $product = Product::find($productId);

        if( $product->product_type !== 'subscription' ){

            // Does this item already exist in the cart?
            $sql = "SELECT * FROM carts
                    WHERE ( (user_id = ? and product_id = ?) OR ( temp_user_id = ? and product_id = ? ) )
                    AND deleted_at IS NULL ";
            $params = [
                $cartUser,
                $productId,
                $cartUser,
                $productId
            ];

            $cartProduct = collect(\DB::select($sql, $params))->first();

        }

        if( $cartProduct ){
            $currVariationSet = serialize( json_decode($cartProduct->variation_set, true) );
            $same = $currVariationSet === serialize( $variationSets) ;
            if( $same ){
                $exists = $product;
            }
        }

        if( $exists ){
            $cart = Cart::where(function ($query) use ($exists, $cartUser) {
                        $query->where(['product_id' => $exists->id, 'temp_user_id' => $cartUser ]);
                    })->orWhere(function ($query) use ($exists, $cartUser) {
                        $query->where(['product_id' => $exists->id, 'user_id' => $cartUser ]);
                    })->first();

            $cart->qty = $qty + $cart->qty;
            $saved = $cart->save();

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

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'cart',
            'object_type' => 'cart',
            'object_id' => $cart->id,
            'content' => $product->title.' added to cart. '.json_encode($variationSets),
            'log_level' => 0,
            'created_by_string' => $cartUser,
            'created_at' => now()
        ]);

        event(new ItemAddedToCart($cart));

        if( $request->ajax() ){
            return response()->json(['success' => true]);
        } else {
            $skipCart = getShoppeSetting('skip_cart');
            if( $skipCart ){
                return redirect( '/checkout' );
            }
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

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'cart.update',
            'object_type' => 'cart',
            'object_id' => $id,
            'content' => $message,
            'log_level' => 0,
            'created_by_string' => $cartUser,
            'created_at' => now()
        ]);

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
        $cart = Cart::where([ 'user_id' => $cartUser, 'id' => $id ])->orWhere(['temp_user_id' => $cartUser, 'id' => $id ]);
        $fullcart = Cart::find($id);
        event(new ItemDeletedFromCart($fullcart));
        $delete = $cart->delete();
        $alert = $delete ? 'success' : 'error';
        $success = $delete ? true : false;
        $code = $delete ? 200 : 404;
        $message = $delete ? 'Item removed from cart.' : 'Item was not removed from cart.';

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'cart.delete',
            'object_type' => 'cart',
            'object_id' => $id,
            'content' => $message,
            'log_level' => 0,
            'created_by_string' => $cartUser,
            'created_at' => now()
        ]);

        if( $request->ajax() ){
            return response()->json(['success' => $success, 'message' => $message], $code);
        } else {
            return redirect('/cart')->with($alert, $message);
        }
    }

}
