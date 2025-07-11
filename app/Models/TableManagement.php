<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableManagement extends Model
{
    protected $fillable = [
        'table_name',
        'start_time',
        'order_id',
        'payment_amount',
        'x_position',
        'y_position',
        'status',
    ];

    protected $casts = [
        'payment_amount' => 'double',
        'x_position' => 'double',
        'y_position' => 'double',
    ];
}
