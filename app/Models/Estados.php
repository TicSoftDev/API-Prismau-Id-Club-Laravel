<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estados extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_id',
        'Estado',
        'Motivo',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class);
    }
}
