<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DraftOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_order_id',
        'product_id',
        'quantity',
        'price',
        'note',
    ];

    public function draftOrder()
    {
        return $this->belongsTo(DraftOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
