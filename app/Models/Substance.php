<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Substance extends Model
{
    protected $table = 'Substances';
    public $timestamps = false;
    protected $fillable = ['name', 'state'];

    public function propertyValues()
    {
        return $this->hasMany(PropertyValue::class, 'substance_id');
    }
}