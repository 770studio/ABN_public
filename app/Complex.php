<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Complex extends Model
{
    protected $table = 'complexes';
    protected $fillable = ['complex','sort'];
}
