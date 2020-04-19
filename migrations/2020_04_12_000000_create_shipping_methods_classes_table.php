<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingMethodsClassesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_methods_classes', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->bigInteger('shipping_method_id');
            $table->bigInteger('shipping_class_id');
            $table->decimal('amount', 9, 2)->nullable();
            $table->string('calc_type', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shipping_methods_classes');
    }
}
