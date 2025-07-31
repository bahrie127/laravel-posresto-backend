<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_amount',
        'sub_total',
        'tax',
        'discount',
        'discount_amount',
        'service_charge',
        'total',
        'payment_method',
        'total_item',
        'id_kasir',
        'nama_kasir',
        'customer_name',
        'table_number',
        'transaction_time',
        'room_id',
        'note',
        'status',
        'is_canceled',
        'canceled_by',
        'canceled_at',
    ];

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    // app/Models/Order.php

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

}
