<?php

return [
	'session' => [
		'idle_timeout_minutes' => 30,
		'redirect_on_idle_to'  => 'login',
	],
	'mfa' => [
		'enabled'                => true,
		'grace_days_after_login' => 30,
		'otp_length'             => 6,
		'otp_ttl_minutes'        => 10,
		'max_attempts'           => 5,
		'throttle_per_minute'    => 5,
		'device_remember_days'   => 60,
		'remember_device_cookie' => 'mfa_trusted_device',
	],
	'password' => [
		'min_length'             => 12,
		'min_digits'             => 1,
		'min_symbols'            => 1,
		'min_lowercase'          => 1,
		'min_uppercase'          => 1,
		'expire_days'            => 90,
		'history'                => 5,
		'redirect_on_expired_to' => 'password.request',
	],
	'user_columns' => [
		'last_mfa_at'         => 'last_mfa_at',
		'password_changed_at' => 'password_changed_at',
	],
];
