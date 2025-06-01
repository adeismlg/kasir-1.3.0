<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Asumsikan store_id = 1; sesuaikan jika diperlukan
        $storeId = 1;

        // Misalnya kita buat 10 kategori
        for ($i = 0; $i < 10; $i++) {
            $name = $faker->words(2, true);

            Category::create([
                'store_id'    => $storeId,
                'name'        => $name,
                'slug'        => Str::slug($name) . '-' . $faker->unique()->numberBetween(1, 1000),
                'description' => $faker->sentence,
                'is_active'   => true,
            ]);
        }
    }
}
