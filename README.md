# Laravel Security Policies

A Laravel package that enforces organization-grade security policies:

- Strong session policy
- Password policy (complexity, expiry, history reuse prevention)
- Email-based multi-factor authentication (MFA) with trusted devices

Supports Laravel 8/9/10/11/12 and PHP 8.0+.

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

For local development inside an existing app (unconventional but supported when the package lives under `vendor/`), add a PSR-4 mapping in the host app `composer.json` so the provider can be autoloaded:

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

Clear caches if needed:

```bash
php artisan config:clear && php artisan route:clear
```

## Publish config

Publish config:

```bash
php artisan vendor:publish --provider="NagibMahfuj\LaravelSecurityPolicies\LaravelSecurityPoliciesServiceProvider" --tag=security-policies-config
```
This will create a `config/security-policies.php` file with default values. You can modify these values as per your requirements. Check below for the configuration options.

## Configuration

`config/security-policies.php` options (grouped):

### Session

| Key                            | Type                | Default   | Description                                                     |
| ------------------------------ | ------------------- | --------- | --------------------------------------------------------------- |
| `session.idle_timeout_minutes` | integer             | `30`      | Minutes of inactivity before forcing logout.                    |
| `session.redirect_on_idle_to`  | string (route name) | `login`   | Route to redirect to after idle timeout.                        |
| `session.last_activity_store`  | string              | `session` | Where to store last activity timestamp: 'session' or 'database' |

### MFA

| Key                            | Type    | Default              | Description                                                                                    |
| ------------------------------ | ------- | -------------------- | ---------------------------------------------------------------------------------------------- |
| `mfa.enabled`                  | bool    | `true`               | Enable/disable MFA enforcement.                                                                |
| `mfa.mode`                     | string  | `trusted_only`       | `'trusted_only'` or `'grace_or_trusted'` (see below).                                          |
| `mfa.redirect_when_not_needed` | string  | `'/'`                | URL or route name to redirect to when MFA is not required (user already verified).             |
| `mfa.grace_days_after_login`   | integer | `30`                 | Require MFA again if last verification is older than X days.                                   |
| `mfa.otp_length`               | integer | `6`                  | Length of the OTP code.                                                                        |
| `mfa.otp_ttl_minutes`          | integer | `10`                 | OTP validity window in minutes.                                                                |
| `mfa.max_attempts`             | integer | `5`                  | Max verify attempts before requiring a new OTP.                                                |
| `mfa.throttle_per_minute`      | integer | `5`                  | Intended per-minute throttle (implement rate limiting as needed).                              |
| `mfa.device_remember_days`     | integer | `60`                 | Days to trust a device when “remember this device” is selected.                                |
| `mfa.remember_device_cookie`   | string  | `mfa_trusted_device` | Cookie name for trusted device fingerprint.                                                    |
| `mfa.device_session_control`   | string  | `multiple`           | Control device access: `'single'` or `'multiple'`.                                             |
| `mfa.single_device_action`     | string  | `logout_previous`    | Action when single device mode and new login detected: `'logout_previous'` or `'prevent_new'`. |

#### MFA Modes

- **trusted_only** (default)
  - Bypass MFA only if the request contains a trusted device cookie that matches a verified TrustedDevice record within `mfa.device_remember_days`.
  - If no trusted match exists, user is redirected to MFA verification on every login.
- **grace_or_trusted**
  - First, the middleware checks for a trusted device as above; if found, bypass MFA.
  - If not trusted, it allows access if the user's `user_columns.last_mfa_at` is within `mfa.grace_days_after_login`.
  - Otherwise, redirects to MFA verification.

#### Device Session Control

When `mfa.device_session_control` is set to `'single'`, users can only be logged in on one device at a time. This feature leverages the existing `trusted_devices` table to track active sessions.

- **single_device_action: logout_previous** (default)
  - When a user logs in from a new device, all previously trusted devices are automatically invalidated by setting their `verified_at` timestamp to null.
  - The new device becomes the only active trusted device.

- **single_device_action: prevent_new**
  - When a user tries to log in from a new device while already logged in elsewhere, the new login attempt is blocked.
  - The user is logged out and redirected to the login page with an error message explaining that single device access is enabled.

- **device_session_control: multiple** (default)
  - No device restrictions - users can be logged in on multiple devices simultaneously.

Device fingerprinting uses IP address, User-Agent, Accept-Language, and Accept headers to uniquely identify devices. The system automatically tracks device activity and updates last seen timestamps.

### Password

| Key                               | Type                | Default                            | Description                                                                                       |
| --------------------------------- | ------------------- | ---------------------------------- | ------------------------------------------------------------------------------------------------- |
| `password.min_length`             | integer             | `12`                               | Minimum password length.                                                                          |
| `password.min_digits`             | integer             | `1`                                | Minimum digits required.                                                                          |
| `password.min_symbols`            | integer             | `1`                                | Minimum symbols required.                                                                         |
| `password.min_lowercase`          | integer             | `1`                                | Minimum lowercase letters required.                                                               |
| `password.min_uppercase`          | integer             | `1`                                | Minimum uppercase letters required.                                                               |
| `password.allowed_symbols`        | string              | `!@#$%^&*()_+-={}[]:;'"<>,.?/\\|~` | Allowed symbol set counted by StrongPassword and used to reject disallowed characters.            |
| `password.expire_days`            | integer             | `90`                               | Force password change after X days.                                                               |
| `password.history`                | integer             | `5`                                | Disallow reuse of last X passwords.                                                               |
| `password.require_history`        | bool                | `false`                            | If true, user must have at least one password history entry; otherwise redirected to change page. |
| `password.redirect_on_expired_to` | string (route name) | `password.request`                 | Route to redirect to when password is expired or when history is required but missing.            |

### User Columns

| Key                                | Type   | Default               | Description                                                                            |
| ---------------------------------- | ------ | --------------------- | -------------------------------------------------------------------------------------- |
| `user_columns.last_mfa_at`         | string | `last_mfa_at`         | User model column that stores when MFA was last completed.                             |
| `user_columns.password_changed_at` | string | `password_changed_at` | User model column that stores when password was last changed.                          |
| `user_columns.last_activity_at`    | string | `last_active_at`      | User model column that stores the last activity timestamp when using database storage. |

Publish migrations:

```bash
php artisan vendor:publish --provider="NagibMahfuj\LaravelSecurityPolicies\LaravelSecurityPoliciesServiceProvider" --tag=security-policies-migrations
php artisan migrate
```

## Register middleware aliases in `app/Http/Kernel.php`

If aliases are not already present, add these to `$middlewareAliases`:

```php
use NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\IdleTimeoutMiddleware;
use NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\RequireRecentMfaMiddleware;
use NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\PasswordExpiredMiddleware;

protected $middlewareAliases = [
    // ... existing aliases ...
    'security.idle'             => IdleTimeoutMiddleware::class,
    'security.mfa'              => RequireRecentMfaMiddleware::class,
    'security.password_expired' => PasswordExpiredMiddleware::class,
];
```

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

In Laravel 9+, Apply these rules where users set or change passwords:

```php
use NagibMahfuj\LaravelSecurityPolicies\Rules\StrongPassword;
use NagibMahfuj\LaravelSecurityPolicies\Rules\NotInRecentPasswords;

$request->validate([
    'password' => ['required', 'confirmed', new StrongPassword, new NotInRecentPasswords],
]);
```

For Laravel 8 and below, Apply these rules where users set or change passwords:

```php
use NagibMahfuj\LaravelSecurityPolicies\Rules\StrongPasswordRule;
use NagibMahfuj\LaravelSecurityPolicies\Rules\NotInRecentPasswordsRule;

$request->validate([
    'password' => ['required', 'confirmed', new StrongPasswordRule, new NotInRecentPasswordsRule],
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
- `trusted_devices`: user_id, device_fingerprint, user_agent, ip_address, verified_at, last_seen_at, timestamps
- Alters `users` table (defaults): `last_mfa_at`, `password_changed_at`, `last_active_at`
  - You may rename these columns in your own migrations and set the names via `user_columns.*` in the config.

### Enabling Database Storage for Last Activity

To use database storage for last activity tracking:

1. Ensure your `users` table has a timestamp column for last activity (default: `last_active_at`). The published migration will add this if needed.
2. Set the following in `config/security-policies.php`:
   ```php
   'session' => [
       'last_activity_store' => 'database', // or 'session' for the default behavior
   ],
   'user_columns' => [
       'last_activity_at' => 'last_active_at', // customize column name if needed
   ],
   ```
3. The middleware will now track last activity in the database instead of the session.

## Events & Listeners

- Listens to `Illuminate\Auth\Events\PasswordReset`
  - Stores the updated hashed password into `password_histories`
  - Sets the configured `user_columns.password_changed_at = now()`

If you are using a custom password change flow, you can trigger the event manually after updating the password:

```php
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;

// Update password
$user->update([
    'password' => Hash::make($request->password),
]);

// Trigger event
event(new PasswordReset($user));
```
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
