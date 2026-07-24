<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['business_category_id', 'name', 'code'];

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
