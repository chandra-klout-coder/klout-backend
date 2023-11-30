<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillsData extends Model
{
    use HasFactory;

    protected $fillable = ['name','parent_id'];

    public function parent()
    {
        return $this->belongsTo(SkillsData::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SkillsData::class, 'parent_id');
    }
}
