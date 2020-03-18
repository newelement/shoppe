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
        $eligibleShipping = false;
        $eligibleSubscription = false;
        $estimatedShippingWeight = 0.00;
        $totalShippingWeight = 0.00;
        $estimtedLength = 0.00;
        $estimatedWidth = 0.00;
        $estimtedHeight = 0.00;
        $flatRate = 0.00;
        $length = 0.00;
        $width = 0.00;
        $height = 0.00;
        $weights = 0.00;

        $estWeights = 0.00;
        $estLengths = [0];
        $estWidths = [0];
        $estHeights = [0];

        $lengths = [0];
        $widths = [0];
        $heights = [0];

        $sub_total = 0.00;
        $taxable_total = 0.00;
        $subscription_total = 0.00;

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

                $flatRate += $variation->shipping_rate_type === 'flat'? (float) $variation->shipping_rate : 0.00;

            } else {
                $price = $product->sale_price ? $product->sale_price : $product->price;
                $subscriptionPrice = $product->product_type === 'subscription'? $price : 0.00;

                if( $product->product_type === 'physical' ){
                    if( $product->weight > 0 ){
                        $eligibleShipping = true;
                    }
                }

                if( $product->product_type === 'subscription' ){
                    $eligibleSubscription = true;
                }

                $flatRate += $product->shipping_rate_type === 'flat'? (float) $product->shipping_rate : 0.00;
            }

            $weightDims = $this->calcWeightDimensions( $product, $variation );

            $weights += (float) $weightDims['weight'];
            $widths[] = (float) $weightDims['width'];
            $heights[] = (float) $weightDims['height'];
            $lengths[] = (float) $weightDims['depth'];

            $estWeights += (float) $weightDims['estweight'];
            $estWidths[] = (float) $weightDims['estwidth'];
            $estHeights[] = (float) $weightDims['estheight'];
            $estLengths[] = (float) $weightDims['estdepth'];

            $items[$i]->price = (float) $price;
            $items[$i]->subscription_price = (float) $subscriptionPrice;
            $items[$i]->line_total = (float) $price * (int) $item->qty;
            $items[$i]->product = $product;
            $items[$i]->taxable_total = $product->is_taxable && $product->product_type !== 'subscription' ? $items[$i]->line_total : 0.00;
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

        $totalShippingWeight = $weights + $estWeights;
        $estimatedShippingWeight = $estWeights;

        $estLength = max( $estLengths );
        $estWidth = max( $estWidths );
        $estHeight = max( $estHeights );

        $length = max( $lengths ) + $estLength;
        $width = max( $widths ) + $estWidth;
        $height = max( $heights ) + $estHeight;


        $cartItems['items'] = $items;
        $cartItems['sub_total'] = $sub_total;
        $cartItems['taxable_total'] = $taxable_total;
        $cartItems['subscription_total'] = $subscription_total;
        $cartItems['eligible_subscription'] = $eligibleSubscription;
        $cartItems['eligible_shipping'] = $eligibleShipping;
        $cartItems['estimated_weight'] = $estimatedShippingWeight;
        $cartItems['total_weight'] = $totalShippingWeight;
        $cartItems['flat_rate_total'] = $flatRate;
        $cartItems['dimensions'] = [
            'total' => [
                'width' => $width,
                'height' => $height,
                'length' => $length
            ],
            'estimated' => [
                'width' => $estWidth,
                'height' => $estHeight,
                'length' => $estLength
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
        if( Auth::check() ){
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

        $estweight = 0.00;
        $estwidth = 0.00;
        $estheight = 0.00;
        $estlength = 0.00;

        if(
            $product->shipping_rate_type === 'estimated' ||
                (
                    $product->shipping_rate_type === 'global'
                    && getShoppeSetting('shipping_rate_type') === 'estimated'
                )
        ){

            $estweight = $variation && $variation->weight > 0? $variation->weight : $product->weight;
            $estwidth = $variation && $variation->width > 0? $variation->width : $product->width;
            $estheight = $variation && $variation->height > 0? $variation->height : $product->height;
            $estlength = $variation && $variation->depth > 0? $variation->depth : $product->depth;

        } elseif(
            $product->shipping_rate_type === 'flat' ||
                (
                    $product->shipping_rate_type === 'global'
                    && getShoppeSetting('shipping_rate_type') === 'flat'
                )
        ){
            $weight = $variation && $variation->weight > 0? $variation->weight : $product->weight;
            $width = $variation && $variation->width > 0? $variation->width : $product->width;
            $height = $variation && $variation->height > 0? $variation->height : $product->height;
            $length = $variation && $variation->depth > 0? $variation->depth : $product->depth;
        }

        return [
                'estweight' => $estweight, 'estwidth' => $estwidth, 'estheight' => $estheight, 'estdepth' => $estlength,
                'weight' => $weight, 'width' => $width, 'height' => $height, 'depth' => $length
                ];

    }

    private function shippingExceptions()
    {

    }

}
