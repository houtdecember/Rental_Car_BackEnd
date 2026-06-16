<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookings extends Model
{
    use HasFactory;
        protected $fillable = [
        'car_id', 'user_id', 'pickup_date', 'return_date', 'status', 'price'
    ];

    public function car(){
        return $this->belongsTo(Car::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
}
