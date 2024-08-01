<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'time',
        'quantity'
    ];

    /**
     * Get all of the salons for the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'salon_booking_dates', 'appointment_id', 'salon_id', 'id', 'id');
    }
}
