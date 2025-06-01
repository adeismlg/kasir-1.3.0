<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user && $user->role !== 'admin') {
            // User non-admin: filter menggunakan store_id milik user
            $storeId = $user->store_id;
            $paymentMethods = PaymentMethod::where('store_id', $storeId)->get();
        } else {
            // User admin
            if ($request->has('store_id')) {
                $storeId = $request->query('store_id');
                $paymentMethods = PaymentMethod::where('store_id', $storeId)->get();
            } else {
                $paymentMethods = PaymentMethod::all();
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $paymentMethods,
            'message' => 'Sukses menampilkan data'
        ]);
    }
}
