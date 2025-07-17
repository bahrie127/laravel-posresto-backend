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
