<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->string('product_type', 100);
			$table->string('title', 300);
			$table->string('slug', 300)->unique();
			$table->text('content')->nullable();
            $table->text('short_content')->nullable();
            $table->text('specs')->nullable();

            $table->text('product_file')->nullable();
            $table->bigInteger('role_id')->nullable();

			$table->decimal('price', 10, 2)->nullable();
			$table->decimal('sale_price', 10 , 2)->nullable();
            $table->boolean('contact_price')->default(0);
            $table->boolean('contact_avail')->default(0);

            $table->string('sku', 300)->nullable();
            $table->string('mfg_part_number', 300)->nullable();

            $table->integer('stock')->nullable();
            $table->integer('min_stock')->nullable();
            $table->integer('monitor_stock')->default(0);

            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('depth', 10, 2)->nullable();

            $table->text('shipping_rate_type', 80);
            $table->decimal('shipping_rate', 10 , 2)->nullable();

			$table->text('keywords')->nullable();
			$table->text('meta_description')->nullable();
			$table->text('social_image')->nullable();
			$table->char('status', 1)->default('P');
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('slug');
			$table->index('product_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('products');
    }
}
