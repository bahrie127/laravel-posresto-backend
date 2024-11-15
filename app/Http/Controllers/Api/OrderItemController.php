<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    public function index(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $userCabangId = Auth::user()->id;

        $query = OrderItem::query()
            ->select('order_items.*', 'products.name as product_name') // Mengambil kolom order_items dan nama produk
            ->join('products', 'products.id', '=', 'order_items.product_id') // Join dengan tabel products
            ->where('products.cabang_id', $userCabangId); // Filter berdasarkan cabang_id pengguna

        // Filter berdasarkan tanggal jika diberikan
        if ($start_date && $end_date) {
            $query->whereBetween('order_items.created_at', [$start_date, $end_date]);
        }

        $orderItems = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $orderItems
        ], 200);
    }
    public function orderSales(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $userCabangId = Auth::user()->id;

    $query = OrderItem::select(
            'order_items.product_id',
            DB::raw('(SELECT name FROM products WHERE products.id = order_items.product_id) AS product_name'),
            DB::raw('SUM(order_items.quantity) as total_quantity')
        )
        ->groupBy('order_items.product_id');

    // Tambahkan filter tanggal jika tersedia
    if ($startDate && $endDate) {
        $query->whereBetween(DB::raw('DATE(order_items.created_at)'), [$startDate, $endDate]);
    }

    // Ambil semua data dari query awal
    $totalProductSold = $query->orderBy('total_quantity', 'desc')->get();

    // Filter data berdasarkan cabang_id yang sesuai dengan Auth::user()->id
    $filteredProducts = $totalProductSold->filter(function ($item) use ($userCabangId) {
        $product = Product::find($item->product_id);
        return $product && $product->cabang_id == $userCabangId;
    });

    return response()->json([
        'status' => 'success',
        'data' => $filteredProducts->values() // Menyusun ulang indeks array
    ], 200);

}
}
