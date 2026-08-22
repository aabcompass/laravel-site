<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReferenceCategory extends Model
{
    protected $table = 'Reference_Categories';
    public $timestamps = false;

    public function data()
    {
        return $this->hasMany(ReferenceData::class, 'category_id')->orderBy('sorting_num');
    }
}