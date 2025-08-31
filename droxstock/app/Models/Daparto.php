<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Daparto extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'dapartos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tiltle',
        'teilemarke_teilenummer',
        'preis',
        'interne_artikelnummer',
        'zustand',
        'pfand',
        'versandklasse',
        'lieferzeit',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'preis' => 'decimal:2',
        'zustand' => 'integer',
        'pfand' => 'integer',
        'versandklasse' => 'integer',
        'lieferzeit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Validation rules for the model
     */
    public static function validationRules($id = null): array
    {
        return [
            'tiltle' => 'nullable|string|max:255',
            'teilemarke_teilenummer' => 'required|string|max:255',
            'preis' => 'required|numeric|min:0|max:999999.99',
            'interne_artikelnummer' => 'required|string|max:100|unique:dapartos,interne_artikelnummer' . ($id ? ',' . $id : ''),
            'zustand' => 'required|integer|min:1|max:5',
            'pfand' => 'required|integer|min:0|max:1000',
            'versandklasse' => 'required|integer|min:1|max:10',
            'lieferzeit' => 'required|integer|min:1|max:365',
        ];
    }

    /**
     * Scope for filtering by brand
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('teilemarke_teilenummer', 'LIKE', $brand . '%');
    }

    /**
     * Scope for filtering by price range
     */
    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('preis', [$minPrice, $maxPrice]);
    }

    /**
     * Scope for searching by part number, brand, or title
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('teilemarke_teilenummer', 'LIKE', "%{$search}%")
                ->orWhere('interne_artikelnummer', 'LIKE', "%{$search}%")
                ->orWhere('tiltle', 'LIKE', "%{$search}%");
        });
    }
}
