# Email Configuration Guide

This document provides recommended `.env` settings for sending emails in your Laravel 12 e-commerce application.

## Email Use Cases

Your application sends emails for:
1. **Email Verification** - When users register
2. **Order Confirmation** - When orders are placed
3. **Order Status Updates** - OrderItemShipped, OrderItemDelivered, OrderItemCancelled, OrderShipped, OrderDelivered, OrderCancelled

## Recommended Configuration

### Option 1: SMTP (Recommended for Production)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**For Gmail:**
- Use an App Password (not your regular password)
- Go to Google Account → Security → 2-Step Verification → App Passwords
- Generate password for "Mail"

**For Other SMTP Providers:**
- **Outlook/Hotmail**: `smtp-mail.outlook.com`, Port: 587
- **Yahoo**: `smtp.mail.yahoo.com`, Port: 587
- **SendGrid**: `smtp.sendgrid.net`, Port: 587, Username: `apikey`, Password: your API key
- **Mailgun**: Check Mailgun dashboard for SMTP credentials

### Option 2: Mailtrap (Recommended for Development/Testing)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Benefits:**
- Perfect for development/testing
- All emails are captured in Mailtrap inbox
- No emails sent to real recipients
- Sign up at: https://mailtrap.io

### Option 3: SendGrid (Production-Ready)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key-here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Setup:**
1. Sign up at https://sendgrid.com
2. Create API key in Settings → API Keys
3. Verify sender email/domain

### Option 4: Mailgun (Production-Ready)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@yourdomain.mailgun.org
MAIL_PASSWORD=your-mailgun-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Setup:**
1. Sign up at https://www.mailgun.com
2. Verify your domain
3. Get SMTP credentials from dashboard

### Option 5: Amazon SES (Scalable Production)

```env
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-aws-smtp-username
MAIL_PASSWORD=your-aws-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Setup:**
1. Verify your domain/email in AWS SES
2. Move out of sandbox mode for production
3. Get SMTP credentials from IAM

## Additional Configuration

### Queue Configuration (For Better Performance)

Since email notifications use queues, ensure your queue is properly configured:

```env
QUEUE_CONNECTION=database
# OR for production:
# QUEUE_CONNECTION=redis
# QUEUE_CONNECTION=sqs
```

Run migrations to create jobs table:
```bash
php artisan queue:table
php artisan migrate
```

### Queue Worker

Run queue worker to process emails:
```bash
php artisan queue:work
```

Or use supervisor for production:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

## Testing Email Configuration

Test your email setup:

```bash
php artisan tinker
```

Then in tinker:
```php
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')->subject('Test');
});
```

Or use Laravel's built-in test route (add to `routes/web.php`):
```php
Route::get('/test-email', function () {
    Mail::raw('Test email', function ($message) {
        $message->to('your-email@example.com')->subject('Test Email');
    });
    return 'Email sent!';
});
```

## Security Best Practices

1. **Never commit `.env` file** to version control
2. **Use environment-specific credentials** (dev/staging/production)
3. **Rotate passwords/API keys** regularly
4. **Use App Passwords** for Gmail (not your main password)
5. **Verify sender domain** in production
6. **Set up SPF/DKIM records** for better deliverability

## Recommended Production Setup

For a production e-commerce site handling all three email types, I recommend:

1. **Primary**: SendGrid or Mailgun (reliable, scalable, good deliverability)
2. **Backup**: Configure fallback SMTP server
3. **Queue**: Use Redis or database queues
4. **Monitoring**: Set up email delivery tracking
5. **Rate Limiting**: Configure to avoid being marked as spam

### Example Production Configuration (SendGrid)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your Store Name"
QUEUE_CONNECTION=redis
```

This configuration will work for:
- ✅ Email verification
- ✅ Order confirmation emails
- ✅ Order status update emails (all events)
- ✅ Queued processing for better performance

