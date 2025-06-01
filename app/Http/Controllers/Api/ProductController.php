<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk.
     *
     * - Untuk user non-admin: Kembalikan produk berdasarkan store_id milik user (diambil dari Auth).
     * - Untuk user admin: Jika ada query parameter 'store_id', kembalikan produk dari toko tertentu; jika tidak, kembalikan semua produk.
     */
    public function index(Request $request)
    {
        // Mengambil user yang sedang login menggunakan Auth facade
        $user = Auth::user();

        if ($user && $user->role !== 'admin') {
            // User non-admin: gunakan store_id milik user
            $storeId = $user->store_id;
            $products = Product::where('store_id', $storeId)->get();
        } else {
            // User admin: jika diberikan query parameter store_id, filter berdasarkan itu
            if ($request->has('store_id')) {
                $storeId = $request->query('store_id');
                $products = Product::where('store_id', $storeId)->get();
            } else {
                // Jika tidak, kembalikan semua produk
                $products = Product::all();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sukses',
            'data'    => $products
        ]);
    }

    public function store(Request $request)
    {
        // Implementasi untuk menyimpan produk baru
    }

    public function show(string $id)
    {
        // Implementasi untuk menampilkan detail produk berdasarkan id
    }

    public function update(Request $request, string $id)
    {
        // Implementasi untuk meng-upate produk berdasarkan id
    }

    public function destroy(string $id)
    {
        // Implementasi untuk menghapus produk berdasarkan id
    }

    /**
     * Menampilkan produk berdasarkan barcode.
     *
     * - Untuk user non-admin: Cari produk berdasarkan barcode dan filter dengan store_id milik user.
     * - Untuk user admin: Jika parameter 'store_id' diberikan, filter dengan itu; jika tidak, cari produk berdasarkan barcode saja.
     */
    public function showByBarcode(Request $request, $barcode)
    {
        $query = Product::where('barcode', $barcode);

        $user = Auth::user();
        if ($user && $user->role !== 'admin') {
            // Non-admin: filter menggunakan store_id milik user
            $query->where('store_id', $user->store_id);
        } else {
            // Admin: jika query parameter store_id disertakan, tambahkan filter tersebut
            if ($request->has('store_id')) {
                $query->where('store_id', $request->query('store_id'));
            }
        }

        $product = $query->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => $product
        ]);
    }
}
