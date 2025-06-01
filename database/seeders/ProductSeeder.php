<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Asumsikan store_id = 1; sesuaikan jika diperlukan
        $storeId = 1;

        // Ambil semua kategori untuk store tersebut
        $categories = Category::where('store_id', $storeId)->get();

        // Jika tidak ada kategori, buat kategori default
        if ($categories->isEmpty()) {
            $category = Category::create([
                'store_id'    => $storeId,
                'name'        => 'Default Category',
                'slug'        => Str::slug('Default Category'),
                'description' => 'Default category description',
                'is_active'   => true,
            ]);
            $categories = collect([$category]);
        }

        // Misalnya kita buat 50 produk
        for ($i = 0; $i < 50; $i++) {
            // Pilih kategori secara acak untuk menetapkan product.category_id
            $randomCategory = $categories->random();

            $name = $faker->words(3, true);
            Product::create([
                'store_id'    => $storeId,
                'name'        => $name,
                'category_id' => $randomCategory->id,
                'slug'        => Str::slug($name) . '-' . $faker->unique()->numberBetween(1, 1000),
                'stock'       => $faker->numberBetween(1, 100),
                'price'       => $faker->numberBetween(10000, 1000000),
                'is_active'   => $faker->boolean(80), // 80% kemungkinan aktif
                'image'       => null, // Jika ada image, Anda bisa isi dengan path image dummy
                'barcode'     => $faker->ean13,
                'description' => $faker->paragraph,
            ]);
        }
    }
}
