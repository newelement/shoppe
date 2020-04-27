<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
use Newelement\Shoppe\Models\ShippingClass;
use Newelement\Neutrino\Models\ActivityLog;

class ProductController extends Controller
{
	use CustomFields;

	public function index(Request $request)
	{
    	if( $request->s && strlen($request->s) ){
			$products = Product::search($request->s)->sortable('title')->paginate(30);
		} else {
			$products = Product::sortable('title')->paginate(30);
		}

		$trashed = Product::onlyTrashed()->get();
    	return view('shoppe::admin.products.index', ['products' => $products, 'trashed' => count($trashed)]);
	}

	public function getCreate()
	{
    	$attributes = ProductAttribute::orderBy('name', 'asc')->get();
    	$fieldGroups = $this->getFieldGroups('products');
        $payment = app('Payment');
        $taxConnector = app('Taxes');
        $subscriptions = $payment->getSubscriptionPlans();
        $taxCodes = method_exists($taxConnector, 'getTaxCodes')? $taxConnector->getTaxCodes() : ['tax_codes' => false];
        $roles = Role::all();
        $shipping_classes = ShippingClass::orderBy('title')->get();
    	return view('shoppe::admin.products.create', [
    	                                            'attributes' => $attributes,
    	                                            'field_groups' => $fieldGroups,
                                                    'roles' => $roles,
                                                    'tax_codes' => $taxCodes['tax_codes'],
                                                    'subscriptions' => $subscriptions,
                                                    'shipping_classes' => $shipping_classes
    	                                            ]);
	}

    public function create(Request $request)
    {
        $validatedData = $request->validate([
           'title' => 'required|max:255',
        ]);

        try{
            $product = new Product();
            $product->title = $request->title;
            $product->slug = toSlug($request->slug, 'product');
            $product->product_type = $request->product_type;
            $product->product_file = $request->product_file;
            $product->subscription_id = $request->subscription_id;
            $product->role_id = $request->role_id;
            $product->content = htmlentities($request->content);
            $product->short_content = htmlentities($request->short_content);
            $product->specs = htmlentities($request->specs);
            $product->cost = $request->cost;
            $product->price = $request->price;
            $product->contact_price = $request->contact_price ? 1 : 0;
            $product->contact_avail = $request->contact_avail ? 1 : 0;
            $product->sale_price = $request->sale_price;
            $product->is_taxable = $request->is_taxable ? 1 : 0;
            $product->tax_code = $request->tax_code;
            $product->tax_inclusive = $request->tax_inclusive ? 1 : 0;
            $product->sku = $request->sku;
            $product->mfg_part_number = $request->mfg_part_number;
            $product->stock = $request->stock;
            $product->min_stock = $request->min_stock;
            $product->monitor_stock = $request->monitor_stock ? 1 : 0;
            $product->weight = $request->weight;
            $product->width = $request->width;
            $product->height = $request->height;
            $product->depth = $request->depth;
            $product->shipping_class_id = $request->shipping_class_id;;
            $product->keywords = $request->keywords;
            $product->meta_description = $request->meta_description;
            $product->status = $request->status;
            $product->social_image = $request->social_image;
            $product->save();
        } catch ( \Exception $e ) {

            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'product.create',
                //'object_type' => 'product',
                //'object_id' => $product->id,
                'content' => 'Error creating product. '.$e->getMessage(),
                'log_level' => 5,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

            return redirect()->back()->with('error', 'There was an problem entering a new product.');
        }

        // Featured Image
        if( $request->featured_image ){
            $path = $request->featured_image;
            $media = new ObjectMedia;
            $media->object_id = $product->id;
            $media->object_type = 'product';
            $media->featured = 1;
            $media->file_path = $path;
            $media->save();
        }

        // Custom Fields
        $customFields = $request->cfs;
        if( $customFields ){
            $this->parseCustomFields($customFields, $product->id, 'product');
        }

        // Taxonomies
        if($request->tax_new){
            foreach( $request->tax_new as $key => $value ){
                $tax_type_id = $key;
                if( strlen($request->tax_new[$tax_type_id]) ){

                    $taxonomy = new Taxonomy;
                    $taxonomy->title = $value;
                    $taxonomy->slug = toSlug($value, 'taxonomy');
                    $taxonomy->taxonomy_type_id = $tax_type_id;
                    $taxonomy->save();

                    $objTerm = new ObjectTerm;
                    $objTerm->object_id = $product->id;
                    $objTerm->object_type = 'product';
                    $objTerm->taxonomy_type_id = $tax_type_id;
                    $objTerm->taxonomy_id = $taxonomy->id;
                    $objTerm->save();
                }
            }
        }

        if( $request->taxes ){
            foreach($request->taxes as $typeId => $terms){
                foreach($terms as $term){
                    ObjectTerm::updateOrCreate(
                        ['object_id' => $product->id, 'object_type' => 'product'],
                        ['taxonomy_type_id' => $typeId, 'taxonomy_id' => $term]
                    );
                }
            }
        }

        // Attributes - arrays
        $attributeIds = $request->attribute_ids;
        $attributeValues = $request->attribute_values;

        if( $attributeIds ){
            foreach( $attributeIds as $attrId => $value ){

                $values = $attributeValues[$attrId];
                $values = array_keys($values);

                $productVariationAttribute = new ProductVariationAttribute();
                $productVariationAttribute->product_id = $product->id;
                $productVariationAttribute->attribute_id = $attrId;
                $productVariationAttribute->values = json_encode($values);
                $productVariationAttribute->save();
            }
        }

        // Variations - arrays
        $variationAttributes = $request->variation_attributes;
        $variations = $request->variations;

        if( $variationAttributes ){
            foreach( $variationAttributes as $varAttrId => $attrSetArr ){

                $variationData = $variations[$varAttrId];

                try{
                    $variation = new ProductVariation();
                    $variation->product_id = $product->id;
                    $variation->attribute_set = json_encode( $attrSetArr );
                    $variation->attribute_values = '{}';
                    $variation->image = $variationData['image'];
                    $variation->desc = $variationData['desc'];
                    $variation->cost = $variationData['cost'];
                    $variation->price = $variationData['price'];
                    $variation->sale_price = $variationData['sale_price'];
                    $variation->sku = $variationData['sku'];
                    $variation->mfg_part_number = $variationData['mfg_part_number'];
                    $variation->stock = $variationData['stock'];
                    $variation->weight = $variationData['weight'];
                    $variation->width = $variationData['width'];
                    $variation->height = $variationData['height'];
                    $variation->depth = $variationData['depth'];
                    $variation->shipping_class_id = $variationData['shipping_class_id'] === 'same_as_parent'? $request->shipping_class_id : $variationData['shipping_class_id'];
                    $variation->save();
                } catch( \Exception $e ) {
                    ActivityLog::insert([
                        'activity_package' => 'shoppe',
                        'activity_group' => 'product.variation',
                        'object_type' => 'product',
                        'object_id' => $product->id,
                        'content' => 'Product variation create failed. '.$e->getMessage(),
                        'log_level' => 5,
                        'created_by' => auth()->user()->id,
                        'created_at' => now()
                    ]);
                }

            }
        }

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'product.create',
            'object_type' => 'product',
            'object_id' => $product->id,
            'content' => 'Product created',
            'log_level' => 0,
            'created_by' => auth()->user()->id,
            'created_at' => now()
        ]);

        return redirect('/admin/product/'.$product->id)->with('success', 'Product created.');

    }


    public function get($id)
    {
        $product = Product::find($id);
        if( !$product ){
            abort(404);
        }

        $fieldGroups = $this->getFieldGroups('products', false, $id);
        $attributes = ProductAttribute::orderBy('name', 'asc')->get();
        $payment = app('Payment');
        $taxConnector = app('Taxes');
        $subscriptions = $payment->getSubscriptionPlans();
        $taxCodes = method_exists($taxConnector, 'getTaxCodes')? $taxConnector->getTaxCodes() : ['tax_codes' => false];
        $terms = ObjectTerm::where('object_id', $id)->where('object_type', 'product')->get();
        $roles = Role::all();
        $shipping_classes = ShippingClass::orderBy('title')->get();

        return view('shoppe::admin.products.edit', [
            'product' => $product,
            'field_groups' => $fieldGroups,
            'terms' => $terms,
            'subscriptions' => $subscriptions,
            'tax_codes' => $taxCodes['tax_codes'],
            'attributes' => $attributes,
            'roles' => $roles,
            'shipping_classes' => $shipping_classes
        ]);
    }


    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if( !$product ){
            abort(404);
        }

        try{
            $product->title = $request->title;
            $product->slug = $product->slug === $request->slug? $request->slug : toSlug($request->slug, 'product');
            $product->product_type = $request->product_type;
            $product->product_file = $request->product_file;
            $product->subscription_id = $request->subscription_id;
            $product->role_id = $request->role_id;
            $product->content = htmlentities($request->content);
            $product->short_content = htmlentities($request->short_content);
            $product->specs = htmlentities($request->specs);
            $product->cost = $request->cost;
            $product->price = $request->price;
            $product->contact_price = $request->contact_price ? 1 : 0;
            $product->contact_avail = $request->contact_avail ? 1 : 0;
            $product->sale_price = $request->sale_price;
            $product->is_taxable = $request->is_taxable ? 1 : 0;
            $product->tax_code = $request->tax_code;
            $product->tax_inclusive = $request->tax_inclusive ? 1 : 0;
            $product->sku = $request->sku;
            $product->mfg_part_number = $request->mfg_part_number;
            $product->stock = $request->stock;
            $product->min_stock = $request->min_stock;
            $product->monitor_stock = $request->monitor_stock ? 1 : 0;
            $product->weight = $request->weight;
            $product->width = $request->width;
            $product->height = $request->height;
            $product->depth = $request->depth;
            $product->shipping_class_id = $request->shipping_class_id;
            $product->keywords = $request->keywords;
            $product->meta_description = $request->meta_description;
            $product->status = $request->status;
            $product->social_image = $request->social_image;
            $product->save();
        } catch ( \Exception $e ){
            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'product.update',
                'object_type' => 'product',
                'object_id' => $product->id,
                'content' => 'Product update failed',
                'log_level' => 5,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);

            return redirect()->back()->with('error', 'Product update failed. '.$e->getMessage() );
        }

        // Featured Image
        if( $request->featured_image ){
            $path = $request->featured_image;
            ObjectMedia::updateOrCreate([
                'object_id' => $product->id,
                'object_type' => 'product',
                'featured' => 1
            ], [ 'file_path' => $path ]);
        } else {
            ObjectMedia::where([
                'object_id' => $product->id,
                'object_type' => 'product',
                'featured' => 1
                ])->delete();
        }

        $customFields = $request->cfs;
        if( $customFields ){
            $this->parseCustomFields($customFields, $product->id, 'product');
        }

        if($request->tax_new){
            foreach( $request->tax_new as $key => $value ){
                $tax_type_id = $key;
                if( strlen($request->tax_new[$tax_type_id]) ){

                    $taxonomy = new Taxonomy;
                    $taxonomy->title = $value;
                    $taxonomy->slug = toSlug($value, 'taxonomy');
                    $taxonomy->taxonomy_type_id = $tax_type_id;
                    $taxonomy->save();

                    $objTerm = new ObjectTerm;
                    $objTerm->object_id = $product->id;
                    $objTerm->object_type = 'product';
                    $objTerm->taxonomy_type_id = $tax_type_id;
                    $objTerm->taxonomy_id = $taxonomy->id;
                    $objTerm->save();
                }
            }
        }

        if( $request->taxes ){
            foreach($request->taxes as $typeId => $terms){
                foreach($terms as $term){
                    $update = ['object_id' => $product->id, 'object_type' => 'product', 'taxonomy_type_id' => $typeId, 'taxonomy_id' => $term];
                    $updated = ObjectTerm::updateOrCreate(
                        $update
                    );
                }
            }
        }

        // Attributes - arrays
        $attributeIds = $request->attribute_ids;
        $attributeValues = $request->attribute_values;

        ProductVariationAttribute::where('product_id', $product->id)->delete();

        if( $attributeIds ){
            foreach( $attributeIds as $attrId => $value ){

                $values = $attributeValues[$attrId];
                $values = array_keys($values);

                $productVariationAttribute = new ProductVariationAttribute();
                $productVariationAttribute->product_id = $product->id;
                $productVariationAttribute->attribute_id = $attrId;
                $productVariationAttribute->values = json_encode($values);
                $productVariationAttribute->save();
            }
        }

        // Variations - arrays
        $variationAttributes = $request->variation_attributes;
        $variations = $request->variations;

        if( $variationAttributes ){
            foreach( $variationAttributes as $varAttrId => $attrSetArr ){

                $variationData = $variations[$varAttrId];

                try{

                    $variation = ProductVariation::find($varAttrId);
                    if( !$variation ){
                        $variation = new ProductVariation;
                    }
                    $variation->product_id = $product->id;
                    $variation->attribute_set = json_encode( $attrSetArr );

                    $attrValues = [];
                    foreach( $attrSetArr as $attrValue ){
                        $attrValues[] = $attrValue['value'];
                    }
                    $variation->attribute_values = $attrValues;
                    $variation->image = $variationData['image'];
                    $variation->desc = $variationData['desc'];
                    $variation->cost = $variationData['cost'];
                    $variation->price = $variationData['price'];
                    $variation->sale_price = $variationData['sale_price'];
                    $variation->sku = $variationData['sku'];
                    $variation->mfg_part_number = $variationData['mfg_part_number'];
                    $variation->stock = $variationData['stock'];
                    $variation->weight = $variationData['weight'];
                    $variation->width = $variationData['width'];
                    $variation->height = $variationData['height'];
                    $variation->depth = $variationData['depth'];
                    $variation->shipping_class_id = $variationData['shipping_class_id'] === 'same_as_parent'? $request->shipping_class_id : $variationData['shipping_class_id'] ;
                    $variation->save();
                } catch( \Exception $e ){
                    ActivityLog::insert([
                        'activity_package' => 'shoppe',
                        'activity_group' => 'product.variation',
                        'object_type' => 'product',
                        'object_id' => $product->id,
                        'content' => 'Product variation create failed. '.$e->getMessage(),
                        'log_level' => 5,
                        'created_by' => auth()->user()->id,
                        'created_at' => now()
                    ]);
                }

            }
        }

        if($request->ajax()){
            return response()->json(['success' => true]);
        }

        return redirect('/admin/product/'.$product->id)->with('success', 'Product updated.');
    }


    public function delete($id)
    {
        $product = Product::find($id);
        $product->delete();

        ActivityLog::insert([
            'activity_package' => 'shoppe',
            'activity_group' => 'product.delete',
            'object_type' => 'product',
            'object_id' => $id,
            'content' => 'Product deleted',
            'log_level' => 1,
            'created_by' => auth()->user()->id,
            'created_at' => now()
        ]);

        return redirect('/admin/products')->with('success', 'Product deleted.');
    }

    public function getTrash(Request $request)
    {
        $products = Product::onlyTrashed()->orderBy('title', 'asc')->paginate(30);
        return view('shoppe::admin.products.trash', [ 'products' => $products ]);
    }

    public function recover(Request $request, $id)
    {
        Product::onlyTrashed()->where('id', $id)->restore();
        return redirect('/admin/product/'.$id)->with('success', 'Product recovered.');
    }

    public function destroy(Request $request, $id)
    {
        $destroyed = Product::onlyTrashed()->where('id', $id)->forceDelete();
        if($destroyed){
            ActivityLog::insert([
                'activity_package' => 'shoppe',
                'activity_group' => 'product.destroyed',
                'object_type' => 'product',
                'object_id' => $id,
                'content' => 'Product destroyed',
                'log_level' => 1,
                'created_by' => auth()->user()->id,
                'created_at' => now()
            ]);
        }
        return redirect('/admin/products-trash')->with('success', 'Product destroyed.');
    }

    public function deleteVariation(Request $request)
    {
        $id = $request->id;
        if( $id ){
            $pv = ProductVariation::find($id);
            if( $pv ){
                $pv->delete();

                ActivityLog::insert([
                    'activity_package' => 'shoppe',
                    'activity_group' => 'product.variation.delete',
                    'object_type' => 'product',
                    'object_id' => $id,
                    'content' => 'Product variation deleted',
                    'log_level' => 1,
                    'created_by' => auth()->user()->id,
                    'created_at' => now()
                ]);

            }
        }

        return response()->json(['success' => true]);
    }

}
