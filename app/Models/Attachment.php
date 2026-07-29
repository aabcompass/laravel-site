<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'Attachments';
    
    // В старой таблице у вас created_at создавался автоматом через CURRENT_TIMESTAMP, 
    // но нет updated_at. Говорим Ларавелю не трогать даты.
    public $timestamps = false; 

    protected $fillable = [
        'attachable_id', 
        'attachable_type', 
        'uploader_id', 
        'file_path', 
        'original_filename', 
        'mime_type', 
        'file_size_bytes', 
        'scale'
    ];

    // Указываем, что это полиморфная связь
    public function attachable()
    {
        return $this->morphTo();
    }
}