# ASR Tech — six-feature delivery

Implemented locally on 17 September 2026 on top of the completed redesign. The existing Laravel / Inertia / Vue / TypeScript architecture and integrations remain in place. No dependency was added, no deployment was performed, and existing unrelated working-tree changes were preserved.

## Delivered

| Feature | Where to use it | Behavior |
| --- | --- | --- |
| Password recovery | Login → Forgot password | Queued reset email, one-hour single-use tokens, throttling, generic acknowledgement, strong-password validation, session revocation, and retained two-factor authentication. |
| Inquiry notifications | Admin → Inquiry notifications | Default-off owner email and optional customer acknowledgement, durable outbox, delivery history and bounded retries. Saving an inquiry succeeds independently of email delivery. |
| Inquiry follow-up and quotes | Admin → Project inquiries | Assignment, follow-up date, private notes, explicit existing-client linkage, and draft quote creation/linking using the existing billing workflow. Drafts stay private until sent. |
| Project workspace | Client Area → Projects; Admin → Projects | Scope, milestones, target dates, progress discussions, private attachments, and recorded approval/change requests. Customer ownership and administrator permissions are enforced. |
| Compatibility filtering | Products; product editor → Verified compatibility | WHMCS, WordPress and PHP version filters combine with the existing search and category/type filters. Only explicitly verified, published ranges match; unknown data has an honest empty state. |
| Maintenance plans | Services → Maintenance plans; Client Area → Maintenance; Admin → Maintenance | Editable WordPress/WHMCS/server plans, published scope and exclusions, saved customer agreement, review/status updates, existing recurring-price checkout or custom quote linkage, and explicit activation after valid billing. |

All new screens use the shared light-surface, charcoal-text and teal-action design, responsive layouts, labelled controls, visible validation and loading states. Existing customer and admin navigation now exposes the new areas. Recovery and private client routes have noindex metadata. The route inventory contains **298 endpoints and 105 Vue pages**; the feature extension adds **41 endpoints and 15 page components**.

## Integration hardening

- Reset credentials invalidate customer sessions without confusing administrator/customer IDs in shared database-session records. Login fingerprints are seeded before the first protected request; pending 2FA challenges cannot survive a credential reset.
- Admin impersonation replaces only the customer session fingerprint and preserves administrator access.
- Quote owner changes are rejected once linked to an inquiry, project or maintenance request. Acceptance/decline serialize against current quote state; repeated acceptance cannot create duplicate orders, invoices or notifications.
- Project operations serialize against closure. Private files are stored outside the public disk and checked for ownership on download.
- Maintenance checkout reserves an order before invoking the existing gateway, rejects duplicate pending/paid payment attempts, preserves an encrypted hosted-payment resume URL, and exposes uncertain payment references for support review.
- Product editing preserves price IDs and retires removed prices rather than deleting subscription/maintenance billing identities.

## Verification actually performed

Final checks after implementation:

- `npm run build` — passed.
- `npm run types:check` — passed.
- `npm run lint:check` — passed.
- `npm run format:check` — passed.
- `composer lint:check` — passed.
- `php artisan test --compact` — **413 tests passed, 4,138 assertions**.
- `git diff --check` — passed.
- Route registration — **298 routes, no duplicate route names**; all inventory component mappings resolve to files.
- Targeted PHPStan checks for the new feature backends and affected checkout/product/quote/session services — passed.
- `composer types:check` still reports the **same 13 pre-existing findings** in Admin ProductController, Client ProductDetailResource and PaymentDisputeService. They concern existing stored-file/JSON resource/nullability typing; no suppression or baseline entry was added. The final full command is not claimed to pass.

The build was completed before the final full test run. An earlier test run overlapped rebuilding assets and briefly encountered a missing generated font CSS file; rerunning sequentially passed all tests.

Browser verification used the isolated SQLite preview at `http://127.0.0.1:8040` and `MAIL_MAILER=array`, with synthetic preview accounts and records:

- At **390 CSS pixels**, inspected maintenance empty/published/detail screens, submitted a real local request, and verified the saved customer scope and admin visibility. Tested an invalid activation without billing, then saved a customer-visible review update.
- Created a project and milestone, submitted a customer discussion update, and recorded a synthetic request-changes decision. Inspected desktop, **768px tablet**, and **390px mobile** workspace states; no page-level horizontal overflow was observed at measured mobile/tablet widths.
- Followed Login → Forgot password, submitted the preview email and verified the generic acknowledgement and private noindex metadata. Rendered the reset form without entering or changing credentials in the browser. A local database queue worker processed the recovery job successfully using the non-delivering array mailer. Actual token expiration, reuse, reset, 2FA and session behavior are covered by automated tests.
- Inspected disabled inquiry email setup in admin, assigned an inquiry, linked the synthetic customer, and created a draft quote without sending it.
- Submitted a PHP compatibility filter, verified unknown compatibility produces zero matches, and verified invalid-version feedback on mobile.
- Existing authentication, ownership, cart, checkout, payment callbacks, subscriptions, invoices, licenses/downloads and support regressions ran in the full automated suite. New payment success/failure/redirect/uncertainty/repeat cases use sandbox or fake gateways. No live payment-provider transaction was performed.

Private upload/download and cross-customer denial are covered by automated HTTP/storage tests, not a claim of browser upload QA. Not every width/state/provider combination was tested visually.

## Data and remaining owner configuration

Seven additive migrations have been applied to the working local SQLite database and the isolated preview. A backup was saved at `/tmp/asrtech-features-baseline/working-before-features.sqlite`. Counts in all 59 pre-existing tables were compared after migration: only the migration history count changed. No customer, order, invoice, subscription, plan or compatibility fixture was inserted into the working database. Synthetic browser fixtures exist only in the preview database; its demonstration plan was returned to draft after testing.

Before enabling live use:

- Supply a real verified sending address, mail-provider configuration and receiving inbox. Enable inquiry notifications explicitly after verification, and run the database queue worker and scheduler. Real email delivery has not been tested.
- Enter and approve maintenance scope, exclusions, support commitments and actual billing prices. No maintenance plan, price or service promise was fabricated or automatically published.
- Add verified product compatibility ranges and review the pre-existing demo catalog’s prices, licensing/support text and screenshots before publishing real products.
- Review existing legal policies and inquiry-data retention decisions. Existing policies were preserved; no new binding terms were invented.

These are business configuration requirements. The implemented interfaces and backend flows are available locally. Production deployment remains outside this delivery.

## Preview and operation

The running preview remains available at [http://127.0.0.1:8040](http://127.0.0.1:8040). To start another local preview against the working database:

```sh
cd /Users/alamin/Project/ASRTech
php artisan migrate
npm run build
MAIL_MAILER=array php artisan serve --host=127.0.0.1 --port=8000
```

For frontend development, use `npm run dev` alongside the PHP server instead of rebuilding each change. The repository also documents `composer dev` / `php artisan dev`; the commands above are the direct commands verified during this task.

To exercise local queued recovery without delivering messages, use the same database configuration as the PHP server:

```sh
MAIL_MAILER=array php artisan queue:work database --tries=5 --timeout=60
```

The scheduler is required for inquiry retry recovery. Run `php artisan schedule:work` only in the intended environment, since the existing application also schedules subscription and invoice work. Real email delivery requires a configured provider in both the web and worker environments; the array mailer deliberately sends nothing.

Further detail: [route/access inventory](FEATURES-ROUTES.md), [recovery and email](FEATURES-RECOVERY-NOTIFICATIONS.md), [inquiries and projects](FEATURES-PROJECTS.md), [compatibility](FEATURES-COMPATIBILITY.md), and [maintenance](FEATURES-MAINTENANCE.md).
