<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotelGuest;
use Illuminate\Http\JsonResponse;

class HotelGuestController extends Controller
{
    /**
     * Tamu yang sedang menginap, dalam bentuk yang dibaca app kasir (RoomModel):
     * `id` dipakai sebagai `room_id` pesanan yang ditagih ke kamar.
     */
    public function index(): JsonResponse
    {
        $guests = HotelGuest::query()
            ->inHouse()
            ->orderBy('room_number')
            ->get()
            ->map(fn (HotelGuest $guest) => [
                'id' => $guest->id,
                'billing_number' => $guest->billing_number,
                'first_name' => $guest->first_name,
                'last_name' => $guest->last_name,
                'room_number' => $guest->room_number,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Tamu menginap',
            'data' => $guests,
        ]);
    }
}
