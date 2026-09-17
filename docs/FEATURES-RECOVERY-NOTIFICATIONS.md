# Password recovery and inquiry email

Implemented locally on 17 September 2026. Production delivery has not been enabled or tested, and no customer communications were sent.

## Customer password recovery

- The sign-in page links to `/forgot-password`. A valid request always produces the same response for a known or unknown account. Account lookup and token delivery run in an encrypted queue job, with request rate limiting and the existing broker's account-level cooldown.
- `/reset-password/{token}?email=...` uses Laravel's native customer password broker, hashed tokens, the configured one-hour expiry, single-use consumption, confirmation, and the existing password policy. Concurrent token resets are serialized in the database.
- Recovery rotates the remember token, revokes matching customer sessions when using the database session driver, and returns to sign-in. Database payloads are checked against the web guard before revocation so an administrator with the same numeric ID is not logged out; dual sessions retain their admin identity. It does not sign the customer in or disable two-factor authentication. Protected client routes use `auth.session` to reject stale password sessions with other session drivers. A discovered Login listener stamps the native password fingerprint immediately after password, OAuth, two-factor and impersonation sign-in, including initially null-password OAuth accounts, before their first protected request.
- Pending two-factor sign-ins record a credential fingerprint; changing or resetting the password invalidates a challenge that began with the old password.
- Recovery pages are excluded from search indexing. The reset response also sets a no-referrer policy and no-store cache policy.
- The original WorkOS-to-native-auth migration did not create `password_reset_tokens`; migration `2026_09_17_194100_create_customer_password_reset_tokens_table.php` adds the required table.
- Existing mixed-case customer email addresses are preserved during recovery instead of forcibly lowercasing the broker lookup.
- Existing logged-in change-password route name `password.update` is preserved. The recovery POST is named `password.reset.store`.

## Inquiry notifications

`/admin/settings/inquiry-notifications` is restricted to administrators with `settings.manage`. The page includes setup, paginated delivery history, visible failures/holds, and retry actions. Navigation is integrated with the existing admin settings menu.

Notifications are **off by default**. No address is seeded. To enable them, the owner must configure a real mail transport and sender in General Configuration, enter an inbox they control, and confirm sender verification with their email provider. Placeholder addresses and log/array transports cannot be enabled. This is an explicit owner attestation; the application does not claim to independently verify provider ownership.

- New inquiries remain saved and visible even if notification storage, queuing, or delivery fails.
- Enabled inquiries create a unique durable outbox row for the admin email and, optionally, a customer acknowledgement. Previously submitted inquiries are not automatically emailed when the feature is enabled.
- The customer receipt contains fixed copy and an inquiry reference, without echoing user-supplied names, messages, or links, or promising response times. The admin email escapes inquiry content.
- Delivery jobs use overlap locks, persistent sent state, five attempts with backoff, stable message IDs, and a protected manual retry action. Sent rows cannot be resent through the retry action.
- If the owner changes the sender/recipient, disables notifications, or disables acknowledgements, affected queued deliveries are held. Review and retry them explicitly after restoring verified settings.
- The scheduler runs `inquiry-notifications:dispatch` every five minutes. It recovers never-queued deliveries and stale work older than two hours; exhausted and held deliveries require admin review.
- SMTP cannot guarantee exactly-once delivery if a process dies after the provider accepts a message but before the sent timestamp commits. Normal retries and duplicate jobs are idempotent; stable message IDs assist downstream deduplication.

## Local setup and operation

```sh
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Both features use a durable `database` queue by default. `ACCOUNT_RECOVERY_QUEUE` and `INQUIRY_NOTIFICATION_QUEUE` can select a configured asynchronous queue connection. After configuring a verified mail provider in an environment where sending is intended:

```sh
php artisan queue:work database --tries=5 --timeout=60
php artisan schedule:work
```

Do not run a delivery worker against live credentials merely to inspect the UI. Use Laravel mail/notification fakes or a local mail capture service for delivery tests. Restart queue workers after changing mail transport configuration so long-lived processes pick it up. Match queue `retry_after` to a value greater than the job timeout and use a shared cache store for worker overlap locks.

Outstanding owner configuration: real verified sender, receiving inbox, mail-provider credentials, running queue worker, and scheduler. Those are deployment/configuration prerequisites, not fabricated business details.

## Verification performed

- `php artisan test --compact tests/Feature/PasswordRecoveryTest.php tests/Feature/InquiryNotificationTest.php tests/Feature/ClientTwoFactorTest.php tests/Feature/BusinessWebsiteTest.php`: **34 tests, 458 assertions passed**.
- Coverage includes unknown-account response parity, encrypted queued recovery work, expiry, single use, confirmation, token/account mismatch, request throttling, session and remember-token invalidation, retained 2FA, stale 2FA challenges, transport failures, notification authorization/configuration, default-off behavior, optional receipts, outbox/queue failure isolation, delivery retries, duplicate jobs, escaped admin content, and acknowledgement content safety.
- Targeted PHPStan for all changed recovery/notification application classes: passed, no errors.
- Targeted Laravel Pint, Vue ESLint, and Prettier: passed.
- `npm run types:check`: passed.
- Tests use temporary databases and `Mail::fake`, `Notification::fake`, and `Queue::fake`/mocks. No real email was sent. Root-task delivery notes record the final application build and browser checks.

## Follow-up session security audit

The follow-up audit added `CustomerSessionSecurityTest` for login-time password fingerprints, the reset-before-first-protected-request race on non-database sessions, initially null-password social accounts, completed two-factor sign-in, shared admin/customer numeric IDs, admin preservation in dual-guard sessions, encrypted JSON payloads, more than one chunk of customer sessions, and mixed-case existing account recovery. Guard-aware database pruning supports the configured PHP/JSON format and optional session encryption without instantiating serialized classes. Unreadable payloads are not attributed to a customer; native session authentication remains the fallback.

The targeted security, recovery, existing two-factor and social-login suites pass. Full final test totals are in the root feature delivery report. No customer password was changed outside isolated test data and no message was sent.
