<?php
use Illuminate\Database\Seeder;
use Newelement\Shoppe\Traits\Seedable;
class ShoppeDatabaseSeeder extends Seeder
{
    use Seedable;
    protected $seedersPath = __DIR__.'/';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seed('ShoppeTaxonomyTableSeeder');
        $this->seed('ShoppePageTableSeeder');
        $this->seed('ShoppeRoleTableSeeder');
    }
}
