<?php
namespace Newelement\Shoppe\Connectors;

use Newelement\Shoppe\Models\Product;
use Newelement\Shoppe\Models\ProductVariation;

class InventoryConnector
{

    public function checkCartStock($items)
    {
        $success = true;
        $message = 'Successful';

        foreach( $items as $item ){
            if( $item->product->product_type === 'physical' ){
                if( $item->variation_id ){
                    $variation = ProductVariation::find($item->variation_id);

                    if( !$variation ){
                        $success = false;
                        $message = 'The item, '.$variation->product->title.': '.$item->variationFormatted.', you have chosen no longer exists.';
                        break;
                    }

                    if( $item->qty > $variation->stock ){
                        $success = false;
                        $message = 'The item, '.$variation->product->title.': '.$item->variationFormatted.', is out of stock at the qty you requested. There are '.$variation->stock.' left in stock.';
                        break;
                    }

                } else {
                    $product = Product::find($item->product_id);

                    if( !$product ){

                        $success = false;
                        $message = 'The item, '.$product->title.', you have chosen no longer exists.';
                        break;
                    }

                    if( $item->qty > $product->stock ){
                        $success = false;
                        $message = 'The item, '.$product->title.', is out of stock at the qty you requested. There are '.$product->stock.' left in stock.';
                        break;
                    }
                }

            }

        }

        return ['success' => $success, 'message' => $message];
    }

    public function checkStock($productId, $qty = 0, $variationId = 0)
    {

    }

    public function removeStock( $items )
    {
        $success = true;
        $message = 'Successful';

        foreach( $items as $item ){
            if( $item->product->product_type === 'physical' ){
                if( $item->variation_id ){

                    $variation = ProductVariation::find($item->variation_id);

                    if( $variation ){
                        $variation->stock = $variation->stock - $item->qty;
                        $variation->save();
                    }

                } else {
                    $product = Product::find($item->product_id);

                    if( $product ){
                        $product->stock = $product->stock - $item->qty;
                        $product->save();
                    }
                }
            }
        }

        return ['success' => $success, 'message' => $message];
    }

    public function addStock( $type = 'physical', $qty = 0, $productId = 0, $variationId = 0)
    {
        $success = true;
        $message = 'Successful';

        if( $type === 'physical' ){
            if( $variationId ){
                $variation = ProductVariation::find($variationId);
                if( $variation ){
                    $variation->stock = $variation->stock + $qty;
                    $variation->save();
                }
            } else {
                $product = Product::find($productId);

                if( $product ){
                    $product->stock = $product->stock + $qty;
                    $product->save();
                }
            }
        }

        return ['success' => $success, 'message' => $message];
    }

}
