<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureValueVariant extends Model
{
    use HasFactory;

    protected $table = "feature_value_variant";

    protected $fillable = ['variant_id','feature_value_id'];
}
