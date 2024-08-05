<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Salon extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at', 'super_admin_id'];

    protected $fillable = [
        'super_admin_id',
        'name',
        'logo_image',
        'description',
        'status',
        'latitude',
        'longitude'
    ];

    public function scopeActive(Builder $builder)
    {
        $builder->where('status', '=', 'active');
    }

    /**
     * Get all of the appointments for the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'salon_booking_dates', 'salon_id', 'appointment_id', 'id', 'id');
    }


    /**
     * Get all of the reservations for the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'product_reservations', 'salon_id', 'reservation_id', 'id', 'id');
    }

    /**
     * Get all of the services for the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'salon_services', 'salon_id', 'service_id', 'id', 'id');
    }

    /**
     * Get all of the products for the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'salon_products', 'salon_id', 'product_id', 'id', 'id')->withPivot('quantity');
    }

    /**
     * Get all of the employees for the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'salon_id', 'id');
    }

    /**
     * Get the admin associated with the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class, 'salon_id', 'id');
    }


    /**
     * Get the super_admin that owns the Salon
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function super_admin(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'super_admin_id', 'other_key');
    }

    protected static function booted()
    {
        static::creating(
            function (Salon $salon) {
                $salon->super_admin_id = 1;
            }
        );
    }
}
