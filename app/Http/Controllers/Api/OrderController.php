<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    //save order
    public function saveOrder(Request $request)
    {
        //validate request
        $request->validate([
            'payment_amount' => 'required',
            'sub_total' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'discount_amount' => 'required',
            'service_charge' => 'required',
            'total' => 'required',
            'payment_method' => 'required',
            'total_item' => 'required',
            'id_kasir' => 'required',
            'nama_kasir' => 'required',
            'transaction_time' => 'required',
            'room_id' => 'required',
            // 'order_items' => 'required'
        ]);

        //create order
        $order = Order::create([
            'payment_amount' => $request->payment_amount,
            'sub_total' => $request->sub_total,
            'tax' => $request->tax,
            'discount' => $request->discount,
            'discount_amount' => $request->discount_amount,
            'service_charge' => $request->service_charge,
            'total' => $request->total,
            'payment_method' => $request->payment_method,
            'total_item' => $request->total_item,
            'id_kasir' => $request->id_kasir,
            'nama_kasir' => $request->nama_kasir,
            'transaction_time' => $request->transaction_time,
            'room_id' => $request->room_id,
            'note' => $request->note ?? null,
            'status' => 'success',
        ]);

        //create order items
        foreach ($request->order_items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id_product'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'note' => $item['note'] ?? null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 200);
    }

    public function index(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        if ($start_date && $end_date) {
            $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();
        } else {
            $orders = Order::all();
        }
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

    public function summary(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $query = Order::query();
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalRevenue = $query->sum('payment_amount');
        $totalDiscount = $query->sum('discount_amount');
        $totalTax = $query->sum('tax');
        $totalServiceCharge = $query->sum('service_charge');
        $totalSubtotal = $query->sum('sub_total');
        $total = $totalSubtotal - $totalDiscount - $totalTax + $totalServiceCharge;
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'total_subtotal' => $totalSubtotal,
                'total_service_charge' => $totalServiceCharge,
                'total' => $total,
            ]
        ], 200);
    }

    public function cancelOrder($id, Request $request)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order tidak ditemukan.'
            ], 404);
        }

        $request->validate([
            'pin' => 'required',
        ]);

        if ($request->pin !== '8888') {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN tidak valid.'
            ], 401);
        }


        if ($order->is_canceled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order sudah dibatalkan sebelumnya.'
            ], 400);
        }

        $order->update([
            'is_canceled' => true,
            'status' => 'cancelled',
            'canceled_by' => auth()->id(), // pastikan auth() tersedia
            'canceled_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order berhasil dibatalkan.',
            'data' => $order
        ]);
    }

    public function updateOrder(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,success,cancelled',
            'is_canceled' => 'required|boolean',
            'canceled_by' => 'nullable|integer|exists:users,id',
            'canceled_at' => 'nullable|date',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order tidak ditemukan.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $order->update([
                'status' => $request->status,
                'is_canceled' => $request->is_canceled,
                'canceled_by' => $request->is_canceled ? ($request->canceled_by ?? auth()->id()) : null,
                'canceled_at' => $request->is_canceled ? ($request->canceled_at ?? now()) : null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order berhasil diperbarui.',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllOrder()
    {
        $orders = Order::with('orderItems')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
    }

}
