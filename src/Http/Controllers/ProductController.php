<?php
namespace Newelement\Shoppe\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Newelement\Neutrino\Facades\Neutrino;
use Newelement\Neutrino\Models\ObjectMedia;
use Newelement\Neutrino\Models\ObjectTerm;
use Newelement\Neutrino\Models\CfObjectData;
use Newelement\Neutrino\Traits\CustomFields;
use Newelement\Neutrino\Models\TaxonomyType;
use Newelement\Neutrino\Models\Taxonomy;
use Newelement\Neutrino\Models\Role;
use Newelement\Shoppe\Models\Product;
use Newelement\Shoppe\Models\ProductAttribute;
use Newelement\Shoppe\Models\ProductCategory;
use Newelement\Shoppe\Models\ProductVariation;
use Newelement\Shoppe\Models\ProductVariationAttribute;

class ProductController extends Controller
{
    use CustomFields;

    public function index(){

        // Get landing page setup option

        // Category view with no product children

        // Category view with product children

        // Show empty categories ???

        // Just products

        // ->sortable('title')

    }

    public function get(Request $request, $slug)
    {
        $product = Product::where([ 'slug' => $slug, 'status' => 'P' ])->first();

        if( !$product ){
            abort(404);
        }

        $data = $product;

        $CFields = getCustomFields('product_'.$data->id);
        $data->custom_fields = $CFields;

        if($request->ajax()){
            return response()->json(['data' => $data]);
        } else {
            view()->share('customFields', $CFields);
            view()->share('objectData', $data);
            return view()->first([ 'shoppe::product-'.$slug, 'shoppe::product'], ['data' => $data]);
        }
    }

}
