# Laravel Security Policies

A Laravel package that enforces organization-grade security policies:

- Strong session policy
- Password policy (complexity, expiry, history reuse prevention)
- Email-based multi-factor authentication (MFA) with trusted devices

Supports Laravel 10/11 and PHP 8.2+.

## Features

- Strong session policy
  - Idle timeout: force logout after X minutes of inactivity
  - Require MFA after X days since last verification
- Password policy
  - Configurable complexity: min length, digits, symbols, lowercase, uppercase
  - Password expiry: require change after X days
  - Password history: restrict reuse of last X passwords
- MFA via email OTP
  - OTP generation, TTL, max attempts, resend
  - Remember/trust device with cookie + DB record

## Installation

Install via Composer once published on Packagist:

```bash
composer require nagibmahfuj/laravel-security-policies
```

For local development inside an app (unconventional but supported), add PSR-4 mapping in the host app `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "NagibMahfuj\\\\LaravelSecurityPolicies\\\\": "vendor/nagibmahfuj/laravel-security-policies/src/"
    }
  }
}
```

Then dump autoload:

```bash
composer dump-autoload -o
```

If auto-discovery is not active, register the service provider in `config/app.php`:

```php
NagibMahfuj\LaravelSecurityPolicies\LaravelSecurityPoliciesServiceProvider::class,
```

## Publish and Migrate

Publish config and migrations:

```bash
php artisan vendor:publish --provider="NagibMahfuj\LaravelSecurityPolicies\LaravelSecurityPoliciesServiceProvider" --tag=security-policies-config
php artisan vendor:publish --provider="NagibMahfuj\LaravelSecurityPolicies\LaravelSecurityPoliciesServiceProvider" --tag=security-policies-migrations
php artisan migrate
```

## Configuration

`config/security-policies.php` options:

- `session.idle_timeout_minutes`: integer
- `session.redirect_on_idle_to`: route name
- `mfa.enabled`: bool
- `mfa.grace_days_after_login`: integer
- `mfa.otp_length`: integer
- `mfa.otp_ttl_minutes`: integer
- `mfa.max_attempts`: integer
- `mfa.throttle_per_minute`: integer
- `mfa.device_remember_days`: integer
- `mfa.remember_device_cookie`: string
- `password.min_length`, `min_digits`, `min_symbols`, `min_lowercase`, `min_uppercase`: integers
- `password.expire_days`: integer
- `password.history`: integer (how many recent passwords are disallowed)
- `password.redirect_on_expired_to`: route name to redirect when expired

## Add Middlewares

The package registers aliases for convenience. In your route groups, add:

```php
Route::middleware(['web', 'auth', 'security.mfa', 'security.password_expired'])->group(function () {
    // Protected routes...
});
```

The idle timeout is typically applied to the `web` group:

```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\IdleTimeoutMiddleware::class,
        // ...
    ],
];
```

Alternatively, use the alias `security.idle` in specific groups.

## Use the Validation Rules

Apply these rules where users set or change passwords:

```php
use NagibMahfuj\LaravelSecurityPolicies\Rules\StrongPassword;
use NagibMahfuj\LaravelSecurityPolicies\Rules\NotInRecentPasswords;

$request->validate([
    'password' => ['required', 'confirmed', new StrongPassword, new NotInRecentPasswords],
]);
```

## MFA Routes and Views

The package provides routes:

- `GET /mfa/verify`: show OTP form
- `POST /mfa/verify`: verify OTP
- `POST /mfa/resend`: resend OTP

Views are loaded from the package and can be published/overridden:

```bash
php artisan vendor:publish --provider="NagibMahfuj\LaravelSecurityPolicies\LaravelSecurityPoliciesServiceProvider" --tag=security-policies-views
```

## Database Schema

- `password_histories`: user_id, password_hash, timestamps
- `mfa_challenges`: user_id, code, expires_at, consumed_at, attempts, timestamps
- `trusted_devices`: user_id, device_fingerprint, user_agent, ip_hash, verified_at, last_seen_at, timestamps
- Alters `users` table: `last_mfa_at`, `password_changed_at`

## Events & Listeners

- Listens to `Illuminate\Auth\Events\PasswordReset`
  - Stores the updated hashed password into `password_histories`
  - Sets `password_changed_at = now()`

If you have a custom password change flow, ensure you also update `password_changed_at` and optionally record history.

## Security Considerations

- Ensure mail is properly configured for OTP delivery.
- Consider enabling rate-limiting for the MFA verify/resend endpoints.
- Trusted device cookie is set with a fingerprint; adjust `device_remember_days` to your risk tolerance.
- Always keep Laravel and dependencies updated.

## Testing

- Feature tests can assert middleware redirects when conditions are not met (MFA required, password expired, idle timeout).
- Unit tests for validation rules (StrongPassword, NotInRecentPasswords).

## License

MIT License. See [LICENSE](LICENSE).
