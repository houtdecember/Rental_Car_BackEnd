<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;
    protected $fillable = [
        'brand', 'model', 'image', 'year', 'category', 
        'seating_capacity', 'fuel_type', 'transmission', 
        'price_per_day', 'location', 'description', 'is_available'
    ];

    public function bookings(){
        return $this->hasMany(Bookings::class);
        
    }
}