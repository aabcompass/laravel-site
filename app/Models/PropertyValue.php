<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PropertyValue extends Model
{
    protected $table = 'Property_Values';
    public $timestamps = false;
    protected $fillable = ['substance_id', 'property_id', 'value', 'notes'];
}