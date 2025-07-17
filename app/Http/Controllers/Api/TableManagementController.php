<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TableManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TableManagementController extends Controller
{
    public function index()
    {
        try {
            $tables = TableManagement::all();
            return response()->json([
                'status' => true,
                'data' => $tables,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Get Tables Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'table_name' => 'required|string',
                'x_position' => 'required|numeric',
                'y_position' => 'required|numeric',
            ]);

            $table = TableManagement::create([
                'table_name' => $validated['table_name'],
                'x_position' => $validated['x_position'],
                'y_position' => $validated['y_position'],
                'start_time' => now(),
                'status' => 'available',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Table created successfully',
                'data' => $table,
            ], 201);
        } catch (\Exception $e) {
            Log::error("Create Table Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $table = TableManagement::findOrFail($id);

            $validated = $request->validate([
                'table_name' => 'sometimes|string',
                'x_position' => 'sometimes|numeric',
                'y_position' => 'sometimes|numeric',
                'status' => 'sometimes|string',
                'order_id' => 'sometimes|nullable|integer',
                'payment_amount' => 'sometimes|nullable|numeric',
                'start_time' => 'sometimes|nullable|date',
            ]);

            $table->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Table updated successfully',
                'data' => $table,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Update Table Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function changePosition(Request $request, $id)
    {
        try {
            $table = TableManagement::findOrFail($id);

            $validated = $request->validate([
                'x_position' => 'required|numeric',
                'y_position' => 'required|numeric',
            ]);

            $table->update([
                'x_position' => $validated['x_position'],
                'y_position' => $validated['y_position'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Table position updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Change Position Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $table = TableManagement::findOrFail($id);
            $table->delete();

            return response()->json([
                'status' => true,
                'message' => 'Table deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Delete Table Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function getByStatus($status)
    {
        try {
            $tables = TableManagement::where('status', $status)->get();

            return response()->json([
                'status' => true,
                'data' => $tables,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Get By Status Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $table = TableManagement::findOrFail($id);

            $validated = $request->validate([
                'status' => 'required|string|in:available,occupied,reserved,disabled,cleaning',
                'order_id' => 'nullable|integer',
            ]);

            $table->update([
                'status' => $validated['status'],
                'order_id' => $validated['order_id']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Table status updated successfully',
                'data' => $table
            ], 200);
        } catch (\Exception $e) {
            \Log::error("Update Table Status Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server Error'], 500);
        }
    }

}
