<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete(); // mengaitkan laporan dengan toko
            $table->enum('period_type', ['hari', 'bulan', 'tahun']); // tipe periode laporan
            $table->date('report_date'); // tanggal laporan; bisa diinterpretasikan sebagai tanggal tertentu (jika harian), tanggal awal bulan (untuk bulanan), atau tanggal awal tahun
            $table->integer('total_revenue')->default(0); // total pendapatan
            $table->integer('total_expense')->default(0); // total pengeluaran
            $table->integer('net_profit')->default(0); // selisih pendapatan dan pengeluaran
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
