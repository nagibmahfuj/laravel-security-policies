<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'password_hash',
    ];
}
