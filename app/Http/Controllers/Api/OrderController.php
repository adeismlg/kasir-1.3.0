<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('orderProducts', 'paymentMethod')->get();

        $orders->transform(function ($order) {
            $order->payment_method = $order->paymentMethod->name ?? '-';
            $order->orderProducts->transform(function ($item) {
                return [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product->name ?? '-',
                    'quantity'     => $item->quantity ?? 0,
                    'unit_price'   => $item->unit_price ?? 0,
                ];
            });

            return $order;
        });

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string',
            'email'              => 'nullable|string',
            'gender'             => 'nullable|string',
            'birthday'           => 'nullable|date',
            'phone'              => 'nullable|string',
            'total_price'        => 'required|numeric',
            'notes'              => 'nullable|string',
            'payment_method_id'  => 'required|exists:payment_methods,id',
            'items'              => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada kesalahan validasi',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Ambil user yang sedang login
        $user    = Auth::user();
        $isAdmin = $user && $user->role === 'admin';

        // Periksa setiap item order
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan',
                ], 422);
            }

            // Untuk user non-admin, produk harus berasal dari toko yang sesuai
            if (!$isAdmin && $product->store_id != $user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk "' . $product->name . '" tidak berasal dari toko Anda',
                ], 422);
            }

            // Periksa ketersediaan stok
            if ($product->stock < $item['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok produk tidak mencukupi: ' . $product->name,
                ], 422);
            }
        }

        // Siapkan data order
        $orderData = $request->only([
            'name',
            'email',
            'gender',
            'phone',
            'total_price',
            'notes',
            'payment_method_id',
            'paid_amount',
            'change_amount'
        ]);

        // Untuk user non-admin, tetapkan store_id berdasarkan user
        if (!$isAdmin) {
            $orderData['store_id'] = $user->store_id;
        } else {
            // Untuk admin: jika store_id dikirimkan, gunakan itu
            if ($request->has('store_id')) {
                $orderData['store_id'] = $request->input('store_id');
            }
        }

        // Buat order baru
        $order = Order::create($orderData);

        // Simpan setiap item order
        foreach ($request->items as $item) {
            $order->orderProducts()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sukses melakukan order',
            'data'    => $order
        ], 200);
    }
}
