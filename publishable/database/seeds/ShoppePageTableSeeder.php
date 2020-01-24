<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Newelement\Neutrino\Models\Page;

class ShoppePageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Page::updateOrCreate([
            'title' => 'Cart',
            'slug' => 'cart',
			'content' => 'DO NOT DELETE',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        Page::updateOrCreate([
            'title' => 'Checkout',
            'slug' => 'checkout',
            'content' => 'DO NOT DELETE',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        Page::updateOrCreate([
            'title' => 'Checkout',
            'slug' => 'checkout',
            'content' => 'DO NOT DELETE',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        Page::updateOrCreate([
            'title' => 'Thank You',
            'slug' => 'thank-you',
            'content' => 'DO NOT DELETE',
            'created_by' => 1,
            'updated_by' => 1
        ]);

        Page::updateOrCreate([
            'title' => 'Customer Account',
            'slug' => 'customer-account',
            'content' => 'DO NOT DELETE',
            'created_by' => 1,
            'updated_by' => 1
        ]);

    }
}
