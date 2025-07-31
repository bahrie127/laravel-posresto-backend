<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\DraftOrder;
use App\Models\DraftOrderItem;
use App\Http\Requests\StoreDraftOrderRequest;

class DraftOrderController extends Controller
{
    /**
     * Get all draft orders with their items
     */
    public function index(): JsonResponse
    {
        $orders = DraftOrder::with('items.product')->orderBy('id')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Draft orders retrieved successfully',
            'data' => $orders
        ], 200);
    }

    /**
     * Get a single draft order by ID
     */
    public function show($id): JsonResponse
    {
        $order = DraftOrder::with('items.product')->find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Draft order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Draft order retrieved successfully',
            'data' => $order
        ], 200);
    }

    /**
     * Store a new draft order
     */
    public function store(StoreDraftOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $ordersData = $data['orders'];
        unset($data['orders']);

        $draftOrder = DraftOrder::create($data);

        foreach ($ordersData as $item) {
            $item['draft_order_id'] = $draftOrder->id;
            DraftOrderItem::create($item);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Draft order created successfully',
            'data' => [
                'id' => $draftOrder->id
            ]
        ], 201);
    }

    /**
     * Update a draft order (e.g. add new items or update existing items)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $draftOrder = DraftOrder::with('items')->find($id);

        if (!$draftOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Draft order not found'
            ], 404);
        }

        // Validasi data (tetap hanya menerima draft_name, note dan orders)
        $validatedData = $request->validate([
            'draft_name' => 'nullable|string',
            'note' => 'nullable|string',
            'orders' => 'required|array',
            'orders.*.product_id' => 'required|exists:products,id',
            'orders.*.quantity' => 'required|integer|min:1',
            'orders.*.price' => 'required|numeric|min:0',
            'orders.*.note' => 'nullable|string',
        ]);

        // Update data utama DraftOrder jika ada
        $draftOrder->update([
            'draft_name' => $validatedData['draft_name'] ?? $draftOrder->draft_name,
            'note' => $validatedData['note'] ?? $draftOrder->note,
        ]);

        // Update atau tambah items
        foreach ($validatedData['orders'] as $item) {
            // cek apakah product_id sudah ada di draft_order_item
            $existingItem = $draftOrder->items()->where('product_id', $item['product_id'])->first();

            if ($existingItem) {
                // update quantity dan price jika sudah ada
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $item['quantity'],
                    'price' => $item['price'],
                    'note' => $item['note'] ?? $existingItem->note,
                ]);
            } else {
                // tambah item baru jika belum ada
                DraftOrderItem::create([
                    'draft_order_id' => $draftOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'note' => $item['note'] ?? null,
                ]);
            }
        }

        // Ambil kembali data terbaru
        $draftOrder->refresh();

        // Kalkulasi ulang data total
        $totalItem = 0;
        $subtotal = 0;

        foreach ($draftOrder->items as $item) {
            $totalItem += $item->quantity;
            $subtotal += $item->quantity * $item->price;
        }

        // Ambil tax_percent, discount, service_charge dari data existing table
        $taxPercent = $draftOrder->tax_percent ?? 0;
        $discount = $draftOrder->discount ?? 0;
        $serviceChargePercent = $draftOrder->service_charge ?? 0;

        // Kalkulasi tax
        $tax = ($taxPercent / 100) * $subtotal;

        // Kalkulasi discount_amount
        if ($discount < 1000) { // berarti persen
            $discountAmount = ($discount / 100) * $subtotal;
        } else { // berarti nominal
            $discountAmount = $discount;
        }

        // Kalkulasi service_charge (berdasarkan persen)
        $serviceCharge = ($serviceChargePercent / 100) * $subtotal;

        // Kalkulasi total akhir
        $total = $subtotal + $tax + $serviceCharge - $discountAmount;

        // Update draft order dengan nilai terbaru
        $draftOrder->update([
            'total_item' => $totalItem,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount_amount' => $discountAmount,
            'service_charge' => $serviceChargePercent,
            'total' => $total,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Draft order updated successfully',
            'data' => DraftOrder::with('items.product')->find($draftOrder->id)
        ], 200);
    }



    /**
     * Delete a draft order by ID
     */
    public function destroy($id): JsonResponse
    {
        $draftOrder = DraftOrder::find($id);

        if (!$draftOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Draft order not found'
            ], 404);
        }

        $draftOrder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Draft order deleted successfully'
        ], 200);
    }
}
