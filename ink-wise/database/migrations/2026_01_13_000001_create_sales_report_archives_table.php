<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_report_archives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('period', 32)->index(); // daily, weekly, monthly, yearly
            $table->timestamp('start_date')->nullable()->index();
            $table->timestamp('end_date')->nullable()->index();
            $table->longText('payload')->nullable(); // JSON snapshot
            $table->unsignedBigInteger('archived_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_report_archives');
    }
};
