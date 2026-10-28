<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HotelGuest extends Model
{
    protected $fillable = [
        'billing_number',
        'room_number',
        'first_name',
        'last_name',
        'check_in',
        'check_out',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
        ];
    }

    /**
     * Tamu yang sedang menginap: sudah check-in dan belum lewat tanggal check-out.
     */
    public function scopeInHouse(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->whereDate('check_in', '<=', $today)->whereDate('check_out', '>=', $today);
    }
}
