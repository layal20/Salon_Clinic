<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'product_id',
        'customer_id',
    ];
}
