<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $table = 'Rewards';

    protected $fillable = [
        'key', 'name', 'symbol_latex', 'image_path',
        'physical_desc', 'public_desc', 'private_desc', 'perks',
        'is_for_answer', 'carrier_type', 'requires_registration',
        'z_number', 'a_number'
    ];

    protected $casts = [
        'is_for_answer' => 'boolean',
        'requires_registration' => 'boolean',
    ];
}