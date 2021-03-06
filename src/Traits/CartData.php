<?php

namespace Newelement\Shoppe\Traits;

use Newelement\Shoppe\Models\Cart;
use Newelement\Shoppe\Models\Product;
use Newelement\Shoppe\Models\ProductVariation;
use Newelement\Shoppe\Models\ShippingClass;
use Newelement\Shoppe\Models\ShippingMethod;
use Newelement\Shoppe\Models\ShippingMethodClass;

trait CartData
{
    public function getCartItems()
    {
        $cartUser = $this->getCartUser();
        $items = Cart::where('user_id', $cartUser)->orWhere('temp_user_id', $cartUser)->get();
        $eligibleShipping = false;
        $eligibleSubscription = false;
        $totalShippingWeight = 0.00;
        $shippingType = false;
        $minimumOrderAmount = 0.00;
        $hasFreeMethod = false;
        $hasEstimatedMethod = false;
        $hasFlatMethod = false;
        $shippingRates = [];
        $length = 0.00;
        $width = 0.00;
        $height = 0.00;
        $weights = 0.00;

        $lengths = [0];
        $widths = [0];
        $heights = [0];

        $sub_total = 0.00;
        $taxable_total = 0.00;
        $subscription_total = 0.00;

        $shoppeSettings = getShoppeSettings();

        $shippingClasses = [];
        $shippingMethods = ShippingMethod::orderBy('sort', 'asc')->get();

        if( $shippingMethods->contains('method_type', 'free') ){
            $hasFreeMethod = true;
            $shippingType = 'free';
            $plucked = $shippingMethods->pluck('minimum_order_amount');
            $minimumOrderAmount = (float) $plucked->first();

            $shippingRates = $shippingMethods->where('method_type', 'free')->first();
        }

        if( $shippingMethods->contains('method_type', 'estimated') ){
            $hasEstimatedMethod = true;
        } else if( $shippingMethods->contains('method_type', 'flat') ){
            $hasFlatMethod = true;
        }

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

            // VARIATION
            if( $variation ){

                $variationPrice = $variation->sale_price ? $variation->sale_price : $variation->price;
                $productPrice = $product->sale_price ? $product->sale_price : $product->price;
                $price = $variationPrice? $variationPrice : $productPrice;
                $subscriptionPrice = $product->product_type === 'subscription'? $price : 0.00;

                if( $product->product_type === 'physical' ){
                    $weight = $variation->weight > 0? $variation->weight : $product->weight;
                    if( $weight > 0 ){
                        $eligibleShipping = true;
                    }
                }

                if( $product->product_type === 'subscription' ){
                    $eligibleSubscription = true;
                }

                $shippingClassId = $variation->shipping_class_id && $variation->shipping_class_id !== '' ? $variation->shipping_class_id : $product->shipping_class_id;
                if( $shippingClassId ){
                    for ($c = 1; $c <= $item->qty; $c++ ) {
                        $shippingClasses[] = $shippingClassId;
                    }
                }

            // BASE PRODUCT
            } else {

                $price = $product->sale_price ? $product->sale_price : $product->price;
                $subscriptionPrice = $product->product_type === 'subscription'? $price : 0.00;

                if( $product->product_type === 'physical' ){
                    if( $product->weight > 0 ){
                        $eligibleShipping = true;
                    }
                }

                if( $product->product_type === 'subscription' && $product->subscription_id ){
                    $eligibleSubscription = true;
                }

                $shippingClassId = $product->shipping_class_id;

                if( $shippingClassId ){
                    for ($j = 1; $j <= $item->qty; $j++ ) {
                        $shippingClasses[] = $shippingClassId;
                    }
                }

            }

            $weightDims = $this->calcWeightDimensions( $product, $variation );

            if( $product->product_type === 'physical' ){
                $weights += (float) $weightDims['weight'];
                $widths[] = (float) $weightDims['width'];
                $heights[] = (float) $weightDims['height'];
                $lengths[] = (float) $weightDims['depth'];
            }

            $items[$i]->price = (float) $price;
            $items[$i]->subscription_price = (float) $subscriptionPrice;
            $items[$i]->line_total = (float) $price * (int) $item->qty;

            if( $product->product_type === 'subscription' && !$product->tax_inclusive ){
                $product->is_taxable = false;
            }

            $items[$i]->product = $product;
            $items[$i]->taxable_total = $product->is_taxable? $items[$i]->line_total : 0.00;
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

            if($product->featuredImage) {
                $items[$i]->image =  $variation && $variation->image ? $variation->image : $product->featuredImage->file_path;
            }

            $sub_total += (float) $items[$i]->line_total;
            $taxable_total += (float) $items[$i]->taxable_total;
            $subscription_total += (float) $items[$i]->subscription_price;

            $i++;
        }

        if( $shippingType !== 'free' ){
            if( $hasEstimatedMethod ){
                $shippingType = 'estimated';
            } else if( $hasFlatMethod ){
                $shippingType = 'flat';
                $shippingRates = $shippingMethods;
                $eligibleShipping = true;
            }
        }

        if( $shippingType === 'free' && $minimumOrderAmount > $sub_total ){
            if( $hasEstimatedMethod ){
                $shippingType = 'estimated';
            } else if( $hasFlatMethod){
                $shippingType = 'flat';
                $shippingRates = $shippingMethods;
                $eligibleShipping = true;
            }
        }

        $totalShippingWeight = $weights + (float) $shoppeSettings['shipping_weight_padding'];

        $length = max( $lengths ) + (float) $shoppeSettings['shipping_length_padding'];
        $width = max( $widths ) + (float) $shoppeSettings['shipping_width_padding'];
        $height = max( $heights ) + (float) $shoppeSettings['shipping_height_padding'];

        $cartItems['items'] = $items;
        $cartItems['sub_total'] = $sub_total;
        $cartItems['taxable_total'] = $taxable_total;
        $cartItems['subscription_total'] = $subscription_total;
        $cartItems['shipping_type'] = $shippingType;
        $cartItems['minimum_order_amount'] = $minimumOrderAmount;
        $cartItems['shipping_rates'] = $shippingRates;
        $cartItems['eligible_subscription'] = $eligibleSubscription;
        $cartItems['eligible_shipping'] = $eligibleShipping;
        $cartItems['total_weight'] = $totalShippingWeight;
        $cartItems['shipping_classes'] = $shippingClasses;
        $cartItems['dimensions'] = [
            'total' => [
                'width' => $width,
                'height' => $height,
                'length' => $length
            ]
        ];

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

        if( auth()->check() ){
            Cart::where('temp_user_id', $temp_cart_user)->update([
                'user_id' => auth()->user()->id
            ]);
            \Cookie::queue(\Cookie::forget('temp_cart_user'));
        }

        $cartUser = auth()->check()? auth()->user()->id : $temp_cart_user;
        return $cartUser;
    }


    public function deleteUserCart()
    {
        $cartUser = $this->getCartUser();
        if( auth()->check() ){
            Cart::where('user_id', $cartUser)->delete();
        } else {
            Cart::where('temp_user_id', $cartUser)->delete();
        }
    }

    private function calcWeightDimensions( $product, $variation = false )
    {
        $weight = 0.00;
        $width = 0.00;
        $height = 0.00;
        $length = 0.00;

        $weight = $variation && $variation->weight > 0? $variation->weight : $product->weight;
        $width = $variation && $variation->width > 0? $variation->width : $product->width;
        $height = $variation && $variation->height > 0? $variation->height : $product->height;
        $length = $variation && $variation->depth > 0? $variation->depth : $product->depth;

        return [ 'weight' => $weight, 'width' => $width, 'height' => $height, 'depth' => $length ];

    }

    private function shippingExceptions()
    {

    }

}
