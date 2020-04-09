<?php

namespace Newelement\Shoppe\Composers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Newelement\Neutrino\Models\TaxonomyType;
use Newelement\Neutrino\Models\Taxonomy;

class ShoppeBreadcrumbComposer
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Initialize a new composer instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $segments = $request->segments();
        $this->segments = $segments;
    }

    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('breadcrumbs', $this->parseSegments());
    }

    /**
     * Parse the request route segments.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function parseSegments()
    {
        $productTaxonomy = TaxonomyType::where('slug', 'product-category')->first();
        return collect($this->segments)->mapWithKeys(function ($segment, $key) use ($productTaxonomy) {
            $term = Taxonomy::where([ 'slug' => $segment, 'taxonomy_type_id' =>  $productTaxonomy->id ])->first();
            $title = $term? $term->title : ucwords($segment);
            return [
                $title => implode('/', array_slice($this->request->segments(), 0, $key + 1))
            ];
        });
    }
}
