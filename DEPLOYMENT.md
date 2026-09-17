# ASRTech — Production Go-Live Checklist

Copy-paste runbook for deploying the platform. Assumes Ubuntu 24.04, PHP 8.3+,
Nginx + PHP-FPM, MySQL 8, Redis, and a domain with TLS (e.g. `asrtech.example.com`).

---

## 1. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Required `.env` values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://asrtech.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=asrtech
DB_USERNAME=asrtech
DB_PASSWORD=********

QUEUE_CONNECTION=database        # required: mail + webhooks are queued
SESSION_DRIVER=database
CACHE_STORE=redis

MAIL_MAILER=smtp                 # transactional mail (invoices, OTP, dunning)
MAIL_HOST=smtp.provider.com
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=billing@asrtech.example.com
MAIL_FROM_NAME="${APP_NAME}"

SUPPORT_INBOUND_TOKEN=<random-64-char-string>   # ticket email piping
AFFILIATE_COMMISSION_RATE=10
AFFILIATE_COOKIE_DAYS=30
```

## 2. Deploy steps

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=LegalPageSeeder        # first deploy only
php artisan db:seed --class=SupportDepartmentSeeder # first deploy only
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache 2>/dev/null || true
```

Create the first administrator:

```bash
php artisan admin:create
```

## 3. Scheduler (cron)

One entry per server user:

```cron
* * * * * cd /var/www/asrtech && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs this drives:

| Job | Cadence | Purpose |
|---|---|---|
| `subscriptions:end-due` | hourly | end canceled subs at period end, expire grace-period licenses |
| `subscriptions:process-dunning` | hourly | escalating past-due reminder emails (config: `asrtech.dunning.reminder_days`) |
| `subscriptions:send-renewal-reminders` | daily 08:00 | upcoming renewal notices |
| `invoices:send-reminders` | daily 09:00 | unpaid invoice reminders (enable in General Configuration) |
| `products:send-release-notifications` | every 5 min | release announcement emails |

## 4. Queue worker (Supervisor)

Mail, outbound webhooks, and release notifications run on the queue.

`/etc/supervisor/conf.d/asrtech-worker.conf`:

```ini
[program:asrtech-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/asrtech/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/asrtech-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start asrtech-worker:*
```

On every deploy: `php artisan queue:restart` (graceful worker reload).

## 5. Payment gateway webhooks

Point each provider at these URLs (HTTPS required in production):

| Gateway | Webhook URL | Notes |
|---|---|---|
| Stripe | `https://asrtech.example.com/gateways/callback/stripe` | events: `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated/deleted` |
| PayPal | `https://asrtech.example.com/gateways/callback/paypal` | configure in PayPal developer dashboard |
| Paddle | `https://asrtech.example.com/gateways/callback/paddle` | notification destination |
| FastSpring | `https://asrtech.example.com/gateways/callback/fastspring` | webhook endpoint in FastSpring console |
| bKash | `https://asrtech.example.com/gateways/callback/bkash` | tokenized checkout callbacks |
| SSLCommerz | `https://asrtech.example.com/gateways/callback/sslcommerz` | IPN URL in merchant panel |

Return URLs (already handled, no provider config needed beyond whitelisting the domain):
`https://asrtech.example.com/gateways/return/{gateway}`

Failed/duplicated webhooks are inspectable and replayable at
**Admin → Payment Reliability**.

## 6. Ticket email piping (optional but recommended)

1. Set `SUPPORT_INBOUND_TOKEN` to a long random string.
2. In your inbound provider (Mailgun Routes / Postmark Inbound / SES),
   forward support mailbox mail to:

   ```
   POST https://asrtech.example.com/api/inbound-email
   ```

   Map fields: `token` (the secret), `from`, `subject`, `text` (plain body).
3. A client reply containing `#123456` in the subject becomes a ticket reply
   and flips the ticket to *Customer Reply*.

## 7. Outbound webhooks & REST API (for integrations)

- Create endpoints at **Admin → Configuration → Webhooks**; verify deliveries
  with the `X-ASRTech-Signature` HMAC-SHA256 header (secret shown per endpoint).
- Issue integration tokens at **Admin → Configuration → API Tokens**.
  Base URL: `https://asrtech.example.com/api/v1/`
  (`products`, `orders`, `orders/{number}`, `licenses/{key}`,
  `invoices/{number}`, `subscriptions`).

## 8. DNS / mail deliverability

- SPF: `v=spf1 include:smtp.provider.com ~all`
- DKIM: enable at your mail provider.
- DMARC: `v=DMARC1; p=quarantine; rua=mailto:postmaster@asrtech.example.com`

## 9. Security checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Admin 2FA enabled for every admin (**Admin → Security**)
- [ ] Client 2FA encouraged (clients: **Client Area → Security (2FA)**)
- [ ] Gateway callback secrets/keys set in **Admin → Configuration → Payment Gateways**
- [ ] `php artisan about` shows cached config/routes
- [ ] Storage symlink for public files: `php artisan storage:link`
- [ ] S3 or persistent disk configured in **Admin → Configuration → Storage Settings** for release downloads

## 10. Smoke test after deploy

1. Register a client, verify email OTP, enable 2FA.
2. Buy a product with the sandbox gateway → license issued, invoice PDF downloads.
3. Trigger a subscription renewal failure (sandbox) → dunning mail within an hour.
4. Reply to a ticket by email → reply appears in the thread.
5. `GET /api/v1/products` with a Bearer token → 200.
6. `curl -X POST` a test event to an outbound webhook endpoint → delivery logged as success.
