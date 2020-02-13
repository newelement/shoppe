<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
			$table->string('ref_id', 100);
            $table->string('status', 30)->nullable();
            $table->string('carrier', 50)->nullable();
            $table->string('shipping_service', 50)->nullable();
            $table->string('shipping_id', 50)->nullable();
            $table->string('shipping_object_id', 50)->nullable();
            $table->decimal('shipping_amount', 9, 2)->nullable();
            $table->string('tracking_number', 200)->nullable();
            $table->dateTime('shipped_on', 0)->nullable();
            $table->decimal('tax_amount', 9, 2)->nullable();
            $table->string('discount_code', 50)->nullable();
            $table->decimal('discount_amount', 9, 2)->nullable();
            $table->text('notes')->nullable();
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
			$table->index('user_id');
            $table->index('ref_id');
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
        Schema::drop('orders');
    }
}
