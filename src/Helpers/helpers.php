<?php
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\ObjectMedia;
use Newelement\Neutrino\Models\Taxonomy;
use Newelement\Neutrino\Models\TaxonomyType;
use Newelement\Neutrino\Models\User;
use Newelement\Neutrino\Models\CfObjectData;
use Newelement\Neutrino\Models\CfFields;
use Newelement\Neutrino\Models\CfRule;
use Newelement\Neutrino\Models\Setting;
use Newelement\Neutrino\Models\Form;
use Newelement\Neutrino\Models\FormField;
use Newelement\Neutrino\Http\Controllers\ContentController;
use Newelement\Shoppe\Models\Product;
use Newelement\Shoppe\Models\Order;
use Newelement\Shoppe\Models\ShoppeSetting;
use Newelement\Shoppe\Models\ProductAttribute;
use Newelement\Shoppe\Models\ProductVariation;
use Newelement\Shoppe\Models\Cart;

function getProductAttribute( $id ){
    return ProductAttribute::find($id);
}

function getShortContent($args = [], $content = false){
    if( !$content ){
        $data = view()->shared('objectData');
        $content = $data->short_content;
    }
    if( isset($args['strip_shortcodes']) &&  $args['strip_shortcodes'] ){
        $content = stripShortcodes($content);
    } else {
        $content = ContentController::doShortCodes($content);
    }
    $content = html_entity_decode($content);
    return $content;
}

function getSpecsContent($content = false){
    if( !$content ){
        $data = view()->shared('objectData');
        $content = $data->specs;
    }
    $content = html_entity_decode($content);
    return $content;
}

function getProductAttributes($productId = false){
    $attributes = [];
    if( !$productId ){
        $data = view()->shared('objectData');
        $attributes = $data->variationAttributes;
    }

    return $attributes;
}


function cartCount(){
    $count = 0;
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

    $cartItems = Cart::where('user_id', $cartUser)->orWhere('temp_user_id', $cartUser)->get();

    $itemCount = 0;
    foreach($cartItems as $item){
        $itemCount += $item->qty;
    }

    return $itemCount;
}


function formatCurrency($price){
    $currency = config('shoppe.currency', 'USD');
    $locale = app()->getLocale();
    $formatter = new \NumberFormatter($locale, NumberFormatter::DECIMAL);
    return $formatter->formatCurrency($price, $currency);
}

function currencySymbol(){
    $currency = config('shoppe.currency', 'USD');
    $locale = app()->getLocale();
    $formatter = new \NumberFormatter($locale."@currency=$currency", NumberFormatter::CURRENCY);
    return $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
}

function getPrice($productId){

    //Check price variations
    $product = Product::find($productId);

    return $product->sale_price ? $product->sale_price : $product->price;
}

function getOrderTotal( $order ){

    $credits = [];
    $debits = [];
    foreach( $order->transactions as $trans ){
        if( $trans->transaction_on === 'order' ){
            if( $trans->transaction_type === 'credit' ){
                $credits[] = (float) $trans->amount;
            }
            if( $trans->transaction_type === 'debit' ){
                $debits[] = (float) $trans->amount;
            }
        }

        if( $trans->transaction_on === 'subscription' ){
            $debits[] = (float) $trans->amount;
        }
    }

    $credit = array_sum($credits);
    $debit = array_sum($debits);

    return $debit  - $credit;

}

function getNewOrderCount(){
    return Order::where('status', 1)->count();
}

function hasProductFilter(){
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'? 'https' : 'http';
    $x = $protocol ."://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $parsed = parse_url($x);
    if( isset($parsed['query']) ){
        $query = $parsed['query'];
        parse_str($query, $params);
        if( isset($params['brand']) || isset($params['category']) ){
            return true;
        }
    }
    return false;
}

function getStates(){

    $states = [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'Washington DC',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'PR' => 'Puerto Rico',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VI' => 'Virgin Islands',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ];

    $canadian_states = [
        "BC" => "British Columbia",
        "ON" => "Ontario",
        "NL" => "Newfoundland and Labrador",
        "NS" => "Nova Scotia",
        "PE" => "Prince Edward Island",
        "NB" => "New Brunswick",
        "QC" => "Quebec",
        "MB" => "Manitoba",
        "SK" => "Saskatchewan",
        "AB" => "Alberta",
        "NT" => "Northwest Territories",
        "NU" => "Nunavut",
        "YT" => "Yukon Territory"
    ];

    $merged = array_merge( $states, $canadian_states );
    return $merged;

}

function getProductFilters(){
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'? 'https' : 'http';
    $x = $protocol ."://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $parsed = parse_url($x);
    $query = $parsed['query'];
    parse_str($query, $params);
    unset($params['page']);
    return $params;
}

function clearProductFilter($filter){
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'? 'https' : 'http';
    $x = $protocol ."://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $parsed = parse_url($x);
    $query = $parsed['query'];
    parse_str($query, $params);
    unset($params[$filter]);
    $newQuery = http_build_query($params);
    return $newQuery;
}

function strip_all_tags($string, $remove_breaks = false){
    $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
    $string = strip_tags( $string );
    if ( $remove_breaks ) {
        $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
    }
    return trim( $string );
}

/*
function checkShippingCartCategories(){
    $invalid = false;
    $message = '';
    $cart = new CartController();
    $cartItems = $cart->getCartItems();
    foreach( $cartItems['items'] as $item){
        if( in_array($item->category, self::$invalidCategories) ){
            $invalid = true;
            $message = 'Bicycles are in-store pickup only. Please contact us for details.';
        }
    }
    return ['invalid' => $invalid, 'message' => $message];
}

function getInvalidShippingCategories(){
    return $invalidCategories;
}
*/

function productPrice($productId, $sign = true){
    $prices = [];
    $mainPrice = Product::find($productId);
    $prices[] = (float) $mainPrice->price;
    $varPrices = ProductVariation::where('product_id', $productId)->get();

    foreach( $varPrices as $price ){
        $prices[] = (float) $price->price;
    }

    $min = min($prices);
    $max = max($prices);

    $leadingSign = $sign? '$' : '';

    if( $min === $max ){
        return $leadingSign.number_format($min, 2, '.', ',');
    }

    if( $min < $max ){
        return $leadingSign.number_format($min, 2, '.', ',').' - $'.number_format($max, 2, '.', ',');
    }
}

function productStock($productId){
    $stocks = [];
    $mainPrice = Product::find($productId);
    $stocks[] = (int) $mainPrice->stock;
    $varStocks = ProductVariation::where('product_id', $productId)->get();

    foreach( $varStocks as $stock ){
        $stocks[] = (int) $stock->stock;
    }

    $min = min($stocks);
    $max = max($stocks);

    if( $min === $max ){
        return $min;
    }

    if( $min < $max ){
        return $min.' - '.$max;
    }
}

function getShoppeSetting($key){
    $setting = ShoppeSetting::where('name', $key)->first();
    $value = _parseSettingValue($setting);
    return $value;
}

function getShoppeSettings(){
    $arr = [];
    $settings = ShoppeSetting::all();
    foreach( $settings as $setting ){
        $arr[ $setting->name ] = _parseSettingValue($setting);
    }
    return $arr;
}

function isEmptyProductTerm($term){
    if( !$term['product_count'] > 0 ){
        if( count($term['children']) > 0 ){
            foreach( $term['children'] as $child ){
                return isEmptyProductTerm($child);
            }
        }
        return true;
    }
    return false;
}

function isInRouteSegment($slug){
    $segments = request()->segments();
    foreach( $segments as $segment ){
        if( $slug === $segment ){
            return true;
        }
    }
    return false;
}

function isLastRouteSegment($slug){
    $segments = request()->segments();
    if( $slug === end($segments) ){
        return true;
    }
    return false;
}

function _parseSettingValue($setting){
    $value = false;
    if( !$setting ){
        return $value;
    }
    switch( $setting->type ){
        case 'string' :
            $value = $setting->string_value;
        break;
        case 'select' :
            $value = json_decode($setting->options);
        break;
        case 'json' :
            $value = json_decode($setting->options);
        break;
        case 'radio' :
            $value = $setting->string_value;
        break;
        case 'checkbox' :
            $value = $setting->string_value;
        break;
        case 'float' :
            $value = $setting->float_value;
        break;
        case 'number' :
            $value = $setting->integer_value;
        break;
        case 'bool' :
            $value = $setting->bool_value;
        break;
        case 'text' :
            $value = $setting->text_value;
        break;
        case 'decimal' :
            $value = $setting->decimal_value;
        break;
        case 'date' :
            $value = $setting->date_value;
        break;
    }

    return $value;
}
