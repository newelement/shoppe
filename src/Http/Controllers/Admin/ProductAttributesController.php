<?php
namespace Newelement\Shoppe\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Newelement\Shoppe\Models\ProductAttribute;

class ProductAttributesController extends Controller
{
    public function index()
    {
        $attributes = ProductAttribute::orderBy('name')->get();
        $edit = new \stdClass;
        $edit->name = '';
        $edit->values = '';
        $edit->id = false;
        return view('shoppe::admin.products.attributes', ['attributes' => $attributes, 'edit' => $edit]);
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:product_attributes|max:255',
            'values' => 'required|max:300',
        ]);

        $name = $request->name;
        $values = $request->values;
        $slug = toSlug($name, 'attribute');
        $jsonValues = json_encode( array_map('trim', explode('|', $values)) );

        ProductAttribute::create([
            'name' => $name,
            'slug' => $slug,
            'values' => $jsonValues
        ]);

        return redirect('/admin/product-attributes')->with('success', 'Attribute created.');
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:product_attributes,name,'.$id.'|max:255',
            'values' => 'required|max:300',
        ]);

        $name = $request->name;
        $values = $request->values;
        $jsonValues = json_encode( array_map('trim', explode('|', $values)) );

        $attr = ProductAttribute::find($id);
        $attr->name = $name;
        $attr->values = $jsonValues;
        $attr->save();

        return redirect('/admin/product-attributes')->with('success', 'Attribute updated.');
    }

    public function get($id)
    {
        $edit = ProductAttribute::find($id);
        $attributes = ProductAttribute::orderBy('name')->get();

        return view('shoppe::admin.products.attributes', ['attributes' => $attributes, 'edit' => $edit]);
    }

    public function delete($id)
    {
        ProductAttribute::find($id)->delete();
        return redirect('/admin/product-attributes')->with('success', 'Attribute deleted.');
    }
}
