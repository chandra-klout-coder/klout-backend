<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryData extends Model
{
    use HasFactory;

    protected $fillable = ['name','parent_id'];

    public function parent()
    {
        return $this->belongsTo(IndustryData::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(IndustryData::class, 'parent_id');
    }
}
