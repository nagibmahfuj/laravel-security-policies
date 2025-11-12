<?php

namespace NagibMahfuj\LaravelSecurityPolicies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrustedDevice extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id', 'device_fingerprint', 'user_agent', 'ip_address', 'verified_at', 'last_seen_at',
	];

	protected $casts = [
		'verified_at'  => 'datetime',
		'last_seen_at' => 'datetime',
	];
}
