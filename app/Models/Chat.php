<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'file_name', 'response', 'search_results'];

    public function getResponseArrayAttribute()
    {
        return json_decode($this->response, true);
    }
}
