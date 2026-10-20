<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    protected $fillable = [
        'language_id',
        'country_id',
        'name',
        'slug'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function states()
    {
        return $this->hasMany(State::class, 'state_id');
    }
}
