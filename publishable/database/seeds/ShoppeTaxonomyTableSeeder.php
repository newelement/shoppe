<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Newelement\Neutrino\Models\TaxonomyType;

class ShoppeTaxonomyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TaxonomyType::updateOrCreate([
            'title' => 'Product Category',
            'slug' => 'product-category',
			'show_on' => 'products'
        ],['sort' => 0]);

        TaxonomyType::updateOrCreate([
            'title' => 'Brand',
            'slug' => 'brand',
            'hierarchical' => 0,
			'show_on' => 'products'
        ],['sort' => 1]);

        TaxonomyType::updateOrCreate([
            'title' => 'Model',
            'slug' => 'model',
            'hierarchical' => 0,
			'show_on' => 'products'
        ],['sort' => 2]);
    }
}
