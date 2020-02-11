<?php

namespace Newelement\Shoppe\Traits;

use Newelement\Shoppe\Models\Cart;
use Newelement\Shoppe\Models\Product;
use Newelement\Shoppe\Models\ProductVariation;
use Auth;

trait CartData
{
    public function getCartItems()
    {
        $cartUser = $this->getCartUser();
        $items = Cart::where('user_id', $cartUser)->orWhere('temp_user_id', $cartUser)->get();

        $sub_total = 0.00;
        $i = 0;
        foreach($items as $item){

            $product = Product::find($item->product_id);
            $slug = $product->slug;
            $title = $product->title;
            $variation = false;

            $varSet = json_decode($item->variation_set);
            if( count($varSet) > 0 ){
                $variation = ProductVariation::find($item->variation_id);
                $shortDesc = $variation? $variation->desc : '';
            } else {
                $shortDesc = $product->content_short;
            }

            if( $variation ){
                $variationPrice = $variation->sale_price ? $variation->sale_price : $variation->price;
                $productPrice = $product->sale_price ? $product->sale_price : $product->price;
                $price = $variationPrice? $variationPrice : $productPrice;
            } else {
                $price = $product->sale_price ? $product->sale_price : $product->price;
            }

            $items[$i]->price = (float) $price;
            $items[$i]->line_total = (float) $price * (int) $item->qty;
            $items[$i]->product = $product;
            $items[$i]->variation = $variation;
            $items[$i]->variationFormatted = false;
            if( $items[$i]->variation_set ){
                $vSets = json_decode($items[$i]->variation_set, true);
                $formatted = [];
                foreach( $vSets as $set ){
                    foreach( $set as $key => $value ){
                        $formatted[] = $key.': '.$value;
                    }
                }
                $items[$i]->variationFormatted = implode(', ', $formatted);
            }
            $items[$i]->image =  $variation && $variation->image ? $variation->image : $product->featuredImage->file_path;
            $sub_total += (float) $items[$i]->line_total;

            $i++;
        }

        $cartItems['items'] = $items;
        $cartItems['sub_total'] = $sub_total;

        return $cartItems;
    }

    public function getCartUser()
    {
        $expire = 60*24*30*30;
        $temp_cart_user = '';

        if( !\Cookie::get('temp_cart_user') ){
            $temp_user_hash = sha1(microtime().rand(1, 1000000));
            \Cookie::queue(\Cookie::make('temp_cart_user', $temp_user_hash, $expire));
            $temp_cart_user = $temp_user_hash;
        } else {
            $temp_cart_user = \Cookie::get('temp_cart_user');
        }

        if( Auth::check() ){
            Cart::where('temp_user_id', $temp_cart_user)->update([
                'user_id' => Auth::user()->id
            ]);
            \Cookie::queue(\Cookie::forget('temp_cart_user'));
        }

        $cartUser = Auth::check()? Auth::user()->id : $temp_cart_user;

        return $cartUser;
    }


    public function deleteUserCart()
    {
        $cartUser = $this->getCartUser();
        Cart::where('user_id', $cartUser)->orWhere('temp_user_id', $cartUser)->delete();
    }

}
