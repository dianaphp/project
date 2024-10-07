<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $connection = 'default';
    protected $fillable = ['name', 'email'];
}