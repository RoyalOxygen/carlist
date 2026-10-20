<?php

namespace App\Models\HomePage\Hero;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSlider extends Model
{
  use HasFactory;
  protected $guarded = [];

  public function language()
  {
    return $this->belongsTo(Language::class, 'language_id', 'id');
  }
}
