<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_packages', function (Blueprint $table) {
            $table->enum('hotel_class', ['5_star', '4_star', '3_star', '2_star', 'budget'])
                  ->default('3_star')
                  ->after('category');
            
            // Add index for filtering performance
            $table->index('hotel_class');
        });
    }

    public function down(): void
    {
        Schema::table('travel_packages', function (Blueprint $table) {
            $table->dropIndex(['hotel_class']);
            $table->dropColumn('hotel_class');
        });
    }
};