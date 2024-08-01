<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Laravel\Passport\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, HasRoles, HasPermissions, HasApiTokens;

    protected $hidden = ['created_at', 'updated_at', 'password'];


    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'phone_number'
    ];
    protected $guard_name = "customer";
    /**
     * Get all of the salon_booking_date for the customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salon_booking_date(): HasMany
    {
        return $this->hasMany(SalonBookingDate::class, 'salon_booking_date_id', 'id');
    }

    /**
     * Get all of the productreservation for the customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_reservation(): HasMany
    {
        return $this->hasMany(ProductReservation::class, 'product_reservation_id', 'id');
    }
}
