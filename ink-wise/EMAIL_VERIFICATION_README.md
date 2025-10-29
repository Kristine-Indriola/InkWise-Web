# InkWise Email Verification System for Password Changes

## Overview

This document describes the complete email verification system implemented for secure password changes in the InkWise application. The system provides an additional layer of security by requiring email verification before allowing password changes.

## Features

- **Email Verification Flow**: Users must verify their email before changing passwords
- **Token-Based Security**: Uses secure tokens with expiration (15 minutes)
- **Session Management**: Tracks verification state throughout the process
- **Attempt Tracking**: Records all password change attempts with device/location info
- **Auto-Redirect**: Seamless user experience with automatic redirects
- **InkWise Branding**: Custom email templates with company branding
- **Security Warnings**: Email includes security information and warnings

## Architecture

### Database Schema

#### `password_change_attempts` Table
```sql
CREATE TABLE password_change_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempt_details JSON,
    expires_at TIMESTAMP NOT NULL,
    confirmed_at TIMESTAMP NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### Models

#### PasswordChangeAttempt Model
- Belongs to User
- Tracks token, expiration, and usage status
- Includes attempt details (IP, device, location)
- Methods: `isExpired()`, `isUsed()`, `markAsUsed()`

### Controllers

#### CustomerProfileController
Key methods for email verification:

- `showEmailVerification()`: Displays email verification form
- `sendVerificationEmail()`: Creates attempt and sends verification email
- `confirmEmail($token)`: Validates token and marks attempt as confirmed
- `showPasswordChangeConfirm()`: Shows password change form after verification
- `changePassword()`: Updates password after verification

### Mail System

#### PasswordChangeVerification Mailable
- Custom envelope with security-focused subject
- Loads user and customer relationships
- Uses branded HTML template
- Includes attempt details and verification link

### Views

#### Email Template: `emails.password-change-verification.blade.php`
- InkWise branded design (red #e85a4f, blue #1565c0)
- Security warnings and attempt details
- Clear verification instructions
- Responsive design

#### Verification Pages:
- `email-verification.blade.php`: Initial verification request form
- `attempt-approved.blade.php`: Success page with auto-redirect (3 seconds)
- `password-change-confirm.blade.php`: Final password change form

## Security Features

1. **Token Expiration**: Verification links expire after 15 minutes
2. **Single Use Tokens**: Each token can only be used once
3. **Session Tracking**: Verification state stored in session
4. **Attempt Logging**: All attempts recorded with device/location info
5. **IP/Device Tracking**: Records attempt source information
6. **Rate Limiting**: Prevents abuse (implemented via middleware)

## User Flow

1. **Initiate Password Change**
   - User clicks "Change Password" from profile
   - Redirected to email verification page

2. **Request Verification Email**
   - User clicks "Send Verification Email"
   - AJAX request creates PasswordChangeAttempt record
   - Email sent with verification link

3. **Email Verification**
   - User clicks link in email
   - Token validated and attempt marked as confirmed
   - Redirected to success page with auto-redirect

4. **Password Change**
   - Auto-redirect to password change form
   - Session verification checked
   - Password updated after validation

5. **Cleanup**
   - Session data cleared after successful password change
   - Attempt marked as used

## Configuration

### Mail Configuration (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=InkwiseSystem@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=InkwiseSystem@gmail.com
MAIL_FROM_NAME="InkWise System"
```

### Routes (web.php)
```php
// Email verification routes
Route::middleware('auth')->prefix('customerprofile')->name('customerprofile.')->group(function () {
    Route::get('/email-verification', [CustomerProfileController::class, 'showEmailVerification'])->name('email-verification');
    Route::post('/email-verification/send', [CustomerProfileController::class, 'sendVerificationEmail'])->name('email-verification.send');
    Route::get('/email-confirm/{token}', [CustomerProfileController::class, 'confirmEmail'])->name('email-confirm');
    Route::get('/password-change-confirm', [CustomerProfileController::class, 'showPasswordChangeConfirm'])->name('password-change-confirm');
    Route::put('/change-password', [CustomerProfileController::class, 'changePassword'])->name('change-password.update');
});
```

## Testing

The system has been thoroughly tested with:

- ✅ Email sending functionality
- ✅ Token validation and expiration
- ✅ Session state management
- ✅ Auto-redirect functionality
- ✅ Password validation
- ✅ Attempt status tracking
- ✅ Database relationship integrity

## Future Enhancements

1. **Rate Limiting**: Add middleware to limit verification email requests
2. **SMS Verification**: Option for SMS-based verification
3. **Admin Dashboard**: View and manage password change attempts
4. **Audit Logging**: Enhanced logging for security monitoring
5. **Multi-Factor Authentication**: Integration with 2FA systems

## Troubleshooting

### Common Issues

1. **Email Not Received**
   - Check spam/junk folder
   - Verify MAIL_* configuration in .env
   - Check Laravel logs for mail errors

2. **Token Expired**
   - Request new verification email
   - Tokens expire after 15 minutes

3. **Session Issues**
   - Clear browser cookies/session
   - Ensure cookies are enabled

### Debug Commands

```bash
# Check mail configuration
php artisan config:show mail

# Test email sending
php artisan tinker
Mail::to('test@example.com')->send(new App\Mail\PasswordChangeVerification($attempt));

# Check migration status
php artisan migrate:status

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## Conclusion

The email verification system provides a secure, user-friendly way to handle password changes in the InkWise application. It balances security requirements with a smooth user experience through proper session management, clear visual feedback, and automatic redirects.