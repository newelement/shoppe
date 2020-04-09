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

    public function index(Request $request){

        $data = collect();
        $data->title = 'Products';
        $data->keywords = '';
        $data->meta_description = '';
        $data->data_type = 'taxonomy';
        $data->products = false;
        $data->categories = false;
        $data->menu_categories = [];
        $data->product = false;
        $isTerm = false;
        $isProduct = false;

        $where = [];
        $order = 'title';
        $dir = 'asc';

        $targetRoute = '';
        $parentId = 0;

        $segments = $request->segments();
        $segmentCount = count($segments);
        $targetSegment = $segments[ $segmentCount - 1 ];

        if( $segmentCount > 1 ){

            /*
            * IS IT A PRODUCT CATEGORY ?
            *
            */
            $parentTerm = Taxonomy::where('slug', $targetSegment)->first();
            if( $parentTerm ){

                $isTerm = true;
                $parentId = $parentTerm->id;
                $productTaxonomy = TaxonomyType::where('slug', 'product-category')->first();

                $data->categories = Taxonomy::where([
                    'taxonomy_type_id' => $productTaxonomy->id,
                    'parent_id' => $parentId
                ])->get();

                $data->menu_categories[$productTaxonomy->title] = $this->getMenuCategoriesArr($productTaxonomy);

                $where = [
                    'ot.taxonomy_type_id' => $productTaxonomy->id,
                    'ot.taxonomy_id' => $parentId,
                    'ot.object_type' => 'product',
                    'products.status' => 'P'
                ];

                $products = Product::
                                join('object_terms AS ot', 'ot.object_id', '=', 'products.id')
                                ->where($where)
                                ->select('products.*', 'products.id as id')
                                ->orderBy($order, $dir)
                                ->orderBy('title', 'asc')
                                ->paginate(20);

                $data->products = $products;

            } else {

                /*
                * IS IT A SINGLE PRODUCT ?
                *
                */
                $product = $this->getProduct($targetSegment);

                if( !$product ){
                    abort(404);
                }

                $isProduct = true;
                $data = $product;

                $CFields = getCustomFields('product_'.$product->id);
                $data->custom_fields = $CFields;

                if($request->ajax()){
                    return response()->json(['data' => $data]);
                } else {
                    view()->share('customFields', $CFields);
                    view()->share('objectData', $data);
                    return view()->first([ 'shoppe::product-'.$targetSegment, 'shoppe::product'], ['data' => $data]);
                }

            }
        } else {

            // IT'S THE ROOT PRODUCT PAGE

            $productTaxonomy = TaxonomyType::where('slug', 'product-category')->first();

            $data->menu_categories[$productTaxonomy->title] = $this->getMenuCategoriesArr($productTaxonomy);

            $data->categories = Taxonomy::where([
                'taxonomy_type_id' => $productTaxonomy->id,
                'parent_id' => $parentId
            ])->get();
        }

        // Get landing page setup option

        // Show prices ???

        // Just products ???

        $brands = '';
        $models = '';

        if($request->ajax()){
            return response()->json(['data' => $data]);
        } else {
            return view( 'shoppe::'.config('shoppe.slugs.store_landing') , [ 'data' => $data ]);
        }

    }


    public function get(Request $request, $slug)
    {
        $product = $this->getProduct($slug);

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

    private function getProduct($slug){
        return Product::where([ 'slug' => $slug, 'status' => 'P' ])->first();
    }

    private function getMenuCategoriesArr($productTaxonomy)
    {

        $taxId = $productTaxonomy->id;

        if( getSetting('cache') ){
            $menuCategories = Cache::rememberForever('menu_product_categories', function () use ($taxId) {
                return Taxonomy::where([
                    'parent_id' => 0,
                    'taxonomy_type_id' => $taxId
                ])->orderBy('sort', 'asc')->orderBy('title', 'asc')->get();
            });

            $menuCategoriesArr = Cache::rememberForever('menu_product_categories_arr', function () use ($menuCategories) {
                return $this->categoriesArr($menuCategories);
            });

        } else {

            $menuCategories = Taxonomy::where([
                'parent_id' => 0,
                'taxonomy_type_id' => $taxId
            ])->orderBy('sort', 'asc')->orderBy('title', 'asc')->get();

            $menuCategoriesArr = $this->categoriesArr($menuCategories);

        }

        return $menuCategoriesArr;
    }

    private function categoriesArr($categories)
    {
        $data = [];

        $order = 'title';
        $dir = 'asc';

        foreach($categories as $category){

            $data[] = [
                'title' => $category->title,
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'taxonomy_id' => $category->taxonomyType->id,
                'slug' => $category->slug,
                'url' => $category->url(),
                'product_count' => Product::join('object_terms AS ot', 'ot.object_id', '=', 'products.id')
                                    ->join('taxonomies AS t', 't.id', '=', 'ot.taxonomy_id')
                                    ->where(['ot.taxonomy_id' => $category->id, 'status' => 'P'])
                                    ->orderBy($order, $dir)
                                    ->count(),
                'children' => $this->categoriesArr($category->children),
            ];
        }

        return $data;
    }

    private function isEmptyProductTerm($term){
        if( !$term['product_count'] > 0 ){
            if( count($term['children']) > 0 ){
                foreach( $term['children'] as $child ){
                    return $this->isEmptyProductTerm($child);
                }
            }
            return true;
        }
        return false;
    }

}
