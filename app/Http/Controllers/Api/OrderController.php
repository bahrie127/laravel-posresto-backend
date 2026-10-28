<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // save order
    public function saveOrder(Request $request)
    {
        // validate request
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
            // Pembeli walk-in tanpa nama/meja: dulu ditolak 422, sementara app sudah
            // menampilkan "sukses", sehingga pesanan itu tidak pernah sampai ke server.
            'customer_name' => 'nullable|string',
            'table_number' => 'nullable|integer',
            'transaction_time' => 'required',
            // 0/null = bukan tagihan kamar (kolom NOT NULL, dulu wajib dikirim).
            'room_id' => 'nullable|integer',
            // Dulu tidak divalidasi: order tanpa item tersimpan, item rusak = 500 setelah
            // order terlanjur dibuat.
            'order_items' => 'required|array|min:1',
            'order_items.*.id_product' => 'required|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.price' => 'required|numeric|min:0',
        ]);

        // App mengirim ulang order yang belum tersinkron (offline/timeout). Waktu transaksi
        // (ISO, sampai mikrodetik) + kasir unik per order, jadi kiriman ulang tidak
        // menggandakan pesanan dan laporan.
        $order = Order::query()
            ->where('id_kasir', $request->id_kasir)
            ->where('transaction_time', $request->transaction_time)
            ->first()
            ?? DB::transaction(fn () => $this->createOrder($request));

        return response()->json([
            'status' => 'success',
            'data' => $order,
        ], 200);
    }

    private function createOrder(Request $request): Order
    {
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
            'customer_name' => $request->customer_name,
            'table_number' => $request->table_number,
            'transaction_time' => $request->transaction_time,
            'room_id' => $request->room_id ?? 0,
            'note' => $request->note ?? null,
            'status' => 'success',
        ]);

        foreach ($request->order_items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id_product'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'note' => $item['note'] ?? null,
            ]);
        }

        return $order;
    }

    public function index(Request $request)
    {
        $orders = $this->inDateRange(Order::query(), $request, 'created_at')->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ], 200);
    }

    /**
     * Ringkasan pendapatan. Pesanan batal tidak dihitung; pendapatan = total yang ditagih
     * (bukan uang yang diterima, yang ikut menghitung kembalian); pajak menambah total.
     */
    public function summary(Request $request)
    {
        $totals = $this->inDateRange(Order::query(), $request, 'created_at')
            ->where('is_canceled', false)
            ->selectRaw('COALESCE(SUM(total), 0) AS revenue, COALESCE(SUM(discount_amount), 0) AS discount,
                COALESCE(SUM(tax), 0) AS tax, COALESCE(SUM(service_charge), 0) AS service, COALESCE(SUM(sub_total), 0) AS subtotal')
            ->first();

        $subtotal = (int) $totals->subtotal;
        $discount = (int) $totals->discount;
        $tax = (int) $totals->tax;
        $service = (int) $totals->service;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_revenue' => (int) $totals->revenue,
                'total_discount' => $discount,
                'total_tax' => $tax,
                'total_subtotal' => $subtotal,
                'total_service_charge' => $service,
                'total' => $subtotal - $discount + $tax + $service,
            ],
        ], 200);
    }

    /**
     * App mengirim tanggal `Y-m-d`. Dulu dipakai langsung di whereBetween sehingga tanggal
     * akhir berhenti di 00:00:00 dan laporan "hari ini" selalu kosong.
     */
    private function inDateRange(Builder $query, Request $request, string $column): Builder
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        if ($start && $end) {
            $query->whereDate($column, '>=', $start)->whereDate($column, '<=', $end);
        }

        return $query;
    }

    public function cancelOrder($id, Request $request)
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $request->validate([
            'pin' => 'required',
        ]);

        if (! hash_equals((string) config('posresto.cancel_pin'), (string) $request->pin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN tidak valid.',
            ], 401);
        }

        if ($order->is_canceled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order sudah dibatalkan sebelumnya.',
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
            'data' => $order,
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

        if (! $order) {
            return response()->json([
                'status' => false,
                'message' => 'Order tidak ditemukan.',
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
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllOrder()
    {
        $orders = Order::with('orderItems')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ], 200);
    }
}
