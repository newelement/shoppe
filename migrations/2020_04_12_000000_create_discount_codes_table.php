<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->string('code', 50);
            $table->string('type', 50);
            $table->string('amount_type', 20);
            $table->decimal('amount', 9, 2)->nullable();
            $table->smallInteger('percent')->nullable();
            $table->decimal('minimum_order_amount', 9, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('expires_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('code');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('discount_codes');
    }
}
