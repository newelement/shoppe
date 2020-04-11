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

    private $data;
    private $parentId = 0;
    private $isTerm = false;
    private $productsPerPage = 20;
    private $filters = [];

    function __construct()
    {
        $this->data = collect();
        $this->filters = $this->getFilters();
    }

    public function index(Request $request){

        $this->data->title = 'Products';
        $this->data->keywords = '';
        $this->data->meta_description = '';
        $this->data->data_type = 'taxonomy';
        $this->data->products = false;
        $this->data->categories = false;
        $this->data->menu_categories = [];
        $this->data->product = false;

        $segments = $request->segments();
        $segmentCount = count($segments);
        $targetSegment = $segments[ $segmentCount - 1 ];

        $this->data->settings = getShoppeSettings();

        if( $segmentCount > 1 ){

            /*
            * IS IT A PRODUCT CATEGORY ?
            *
            */
            $parentTerm = Taxonomy::where('slug', $targetSegment)->first();

            if( $parentTerm ){

                $this->isTerm = true;
                $this->parentId = $parentTerm->id;

                $this->getMenuCategories();
                $this->buildTags();

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
                $this->data->custom_fields = $CFields;

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
            $this->getMenuCategories();
            $this->buildTags();

        }

        // Show prices ???
        // Just products ???

        view()->share('shoppeSettings', $this->data->settings);

        if($request->ajax()){
            return response()->json(['data' => $this->data]);
        } else {
            return view( 'shoppe::'.config('shoppe.slugs.store_landing') , [ 'data' => $this->data ]);
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

    private function getMenuCategories()
    {

        $productTaxonomy = TaxonomyType::where('slug', 'product-category')->first();

        $this->data->categories = $this->buildCategories($productTaxonomy->id);

        $this->data->menu_categories[$productTaxonomy->title] = ['type' => 'hierarchical', 'items' => $this->getMenuCategoriesArr($productTaxonomy)];

        if( $this->isTerm ){
            $this->getTermProducts($productTaxonomy->id);
        }
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
            ])->orderBy('sort', 'asc')
            ->orderBy('title', 'asc')
            ->get();

            $menuCategoriesArr = $this->categoriesArr($menuCategories);

        }

        return $menuCategoriesArr;
    }

    private function buildCategories($taxonomyId)
    {
        $order = 'taxonomies.title';
        $dir = 'asc';

        $where = [
            'taxonomies.taxonomy_type_id' => $taxonomyId,
            'taxonomies.parent_id' => $this->parentId
        ];

        $query = Taxonomy::query();

        /*
        $i = 0;
        foreach( $this->filters as $taxonomySlug => $termSlug ){
            $i++;
            $query = $query->join('object_terms AS ot'.$i, 'ot'.$i.'.taxonomy_id', '=', 'taxonomies.id');
            $query = $query->join('products AS p', 'p.id', '=', 'ot'.$i.'.object_id');
        }

        $c = 0;
        foreach( $this->filters as $taxonomySlug => $termSlug ){
            $c++;
            $taxonomy = TaxonomyType::where('slug', $taxonomySlug)->first();
            $term = Taxonomy::where('slug', $termSlug)->first();
            $query = $query->where([
                'ot'.$c.'.taxonomy_type_id' => $taxonomy->id,
                'ot'.$c.'.taxonomy_id' => $term->id,
                'ot'.$c.'.object_type' => 'product'
            ]);
        }*/

        $query = $query->where($where);

        $query = $query->orderBy($order, $dir);
        $categories = $query->get();

        return $categories;
    }

    private function buildTags()
    {
        $order = 'title';
        $dir = 'asc';

        $productTags = getShoppeSetting('product_menu_tags');

        foreach( $productTags as $productTag ){
            $tag = TaxonomyType::where('slug', $productTag )->first();

            $tags = Taxonomy::where([
                'taxonomy_type_id' => $tag->id
            ])->orderBy($order, $dir)->get();

            //$this->data->tags[] = $tags;

            $this->data->menu_categories[$tag->title] = ['type' => 'tag', 'items' => $this->tagsArr($tags)];
        }
    }

    private function categoriesArr($categories)
    {
        $data = [];

        foreach($categories as $category){

            $data[] = [
                'title' => $category->title,
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'taxonomy_id' => $category->taxonomyType->id,
                'slug' => $category->slug,
                'url' => $category->url(),
                'product_count' => $this->getTermProductCount($category->id),

                'children' => $this->categoriesArr($category->children),
            ];
        }

        return $data;
    }

    private function tagsArr($tags)
    {
        $data = [];

        foreach($tags as $tag){

            $data[] = [
                'title' => $tag->title,
                'id' => $tag->id,
                'taxonomy_id' => $tag->taxonomyType->id,
                'slug' => $tag->slug,
                'taxonomy_slug' => $tag->taxonomyType->slug,
                'product_count' => $this->getTermProductCount($tag->id)
            ];
        }
        return $data;
    }

    private function getTermProductCount($termId)
    {
        $order = 'products.title';
        $dir = 'asc';

        $where = [
            'ot.taxonomy_id' => $termId,
            'products.status' => 'P',
            'ot.object_type' => 'product'
        ];

        $count = Product::query();
        $count = $count->join('object_terms AS ot', 'ot.object_id', '=', 'products.id');
        $i = 0;
        foreach( $this->filters as $slug => $value ){
            $i++;
            $count = $count->join('object_terms AS ot'.$i, 'ot'.$i.'.object_id', '=', 'products.id');
        }
        $count = $count->join('taxonomies AS t', 't.id', '=', 'ot.taxonomy_id');
        $count = $count->where($where);

        $c = 0;
        foreach( $this->filters as $slug => $value ){
            $c++;
            $taxonomy = TaxonomyType::where('slug', $slug)->first();
            $term = Taxonomy::where('slug', $value)->first();
            $count = $count->where([
                'ot'.$c.'.taxonomy_type_id' => $taxonomy->id,
                'ot'.$c.'.taxonomy_id' => $term->id,
                'ot'.$c.'.object_type' => 'product'
            ]);
        }
        $count = $count->orderBy($order, $dir);
        $results = $count->count();

        return $results;
    }

    private function getTermProducts($taxonomyId)
    {

        $order = 'title';
        $dir = 'asc';

        $where = [
            'ot.taxonomy_type_id' => $taxonomyId,
            'ot.taxonomy_id' => $this->parentId,
            'ot.object_type' => 'product'
        ];

        $query = Product::query();
        $query = $query->join('object_terms AS ot', 'ot.object_id', '=', 'products.id');

        $i = 0;
        foreach( $this->filters as $slug => $value ){
            $i++;
            $query = $query->join('object_terms AS ot'.$i, 'ot'.$i.'.object_id', '=', 'products.id');
        }

        $query = $query->where($where);
        $c = 0;
        foreach( $this->filters as $slug => $value ){
            $c++;
            $taxonomy = TaxonomyType::where('slug', $slug)->first();
            $term = Taxonomy::where('slug', $value)->first();
            $query = $query->where([
                'ot'.$c.'.taxonomy_type_id' => $taxonomy->id,
                'ot'.$c.'.taxonomy_id' => $term->id,
                'ot'.$c.'.object_type' => 'product'
            ]);
        }
        $query = $query->where(['products.status' => 'P']);
        $query = $query->select('products.*', 'products.id as id');
        $query = $query->orderBy($order, $dir);
        $query = $query->orderBy('title', 'asc');
        $products = $query->paginate($this->productsPerPage);
        //$products = $query->dd();

        $this->data->products = $products;
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

    private function getFilters()
    {
        $filters = [];
        $currentQueries = request()->query();
        if( !isset($currentQueries['filters']) ){
            return $filters;
        }
        $filtersArr = $currentQueries['filters'];
        foreach( $filtersArr as $name => $value ){
            if( is_array($value)  ){
                foreach( $value as $v ){
                    $filters[$name][] = $v;
                }
            } else {
                $filters[$name][] = $value;
            }
        }
        return $filters;
    }

    public function delFilter(Request $request, $name, $value)
    {
        $uri = $request->session()->get('_previous');
        $parsed = parse_url($uri['url']);

        $params = request()->query();
        $filters = isset($params['filters'])? $params['filters'] : [];

        foreach( $filters as $filterName => $filterArr ){
            foreach( $filterArr as $key => $filterValue ){
                if( $filterName === $name && $filterValue === $value ){
                    //dd($params, $filterName, $key);
                    //dd($params[$filterName][$key]);
                    unset($params['filters'][$filterName][$key]);
                }
            }
        }

        $newQuery = http_build_query($params);
        $newUrl = strlen($newQuery)? $parsed['path'].'?'.$newQuery : $parsed['path'] ;
        return redirect($newUrl);
    }

}
