<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PhysicalProperty extends Model
{
    protected $table = 'Physical_Properties';
    public $timestamps = false;
    protected $fillable = ['name', 'symbol', 'units'];
}