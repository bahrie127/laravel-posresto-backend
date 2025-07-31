<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DraftOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'total_item',
        'subtotal',
        'tax',
        'tax_percent',
        'discount',
        'discount_amount',
        'service_charge',
        'total',
        'transaction_time',
        'table_number',
        'draft_name',
        'room_id',
        'note',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(DraftOrderItem::class);
    }
}

