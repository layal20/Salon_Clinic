<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
    ];

    /**
     * Get all of the salons for the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'salon_products', 'product_id', 'salon_id')->withPivot('quantity');;
    }

    /**
     * Get the admin that owns the product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_products', 'product_id', 'admin_id', 'id');
    }
}
