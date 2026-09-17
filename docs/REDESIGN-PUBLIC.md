# Public services and inquiry implementation

Updated locally on 17 September 2026. This note covers the public-services workstream; the overall redesign inventory covers other public, commerce, account, support, and administrative pages.

## Route coverage

| Route | Presentation and workflow |
| --- | --- |
| `/services` | Service overview, eight offerings, explanation of development, products, and ongoing care. |
| `/services/web-development` | Audience, typical scope, project intake. |
| `/services/mobile-app-development` | Audience, typical scope, project intake. |
| `/services/whmcs-customisation` | Audience, typical scope, project intake. |
| `/services/whmcs-modules-templates` | Audience, typical scope, product and custom-work explanation. |
| `/services/wordpress-plugins` | Audience, typical scope, product and custom-work explanation. |
| `/services/wordpress-whmcs-management` | Existing-platform management scope, separated from development. |
| `/services/server-management` | Server maintenance scope and intake. |
| `/services/technical-support` | Troubleshooting scope and existing customer support route. |
| `/software-development` | Existing URL retained, now shares the web-development presentation. |
| `/about` | Authoritative business description, Al Amin as Owner and Founder, Bangladesh location. |
| `/contact` | Working guest inquiry form with service selection and preserved service query, required fields, validation, loading, real persistence success, and failure states. Form appears before address details on mobile. |
| `/admin/inquiries` | Paginated inquiry inbox and new/reviewed/closed status. Uses existing `support.manage` permission, admin authentication, and audit middleware. |

Every service action opens `/contact?service={slug}`. The existing `/client-area/tickets` route remains the path for account/product support.

## Architecture and data

- Existing Laravel/Inertia/Vue architecture retained; no new dependencies.
- Public identity is centralized in `config/asrtech.php` under `business` and shared as `page.props.business`. Existing configurable `site` fields and settings remain supported. A `site.localPreview` flag identifies local-environment previews.
- Service content lives in `resources/js/modules/client/data/services.ts`; backend service identifiers and accepted inquiry choices are explicit in configuration.
- Existing support tickets require an existing user. Creating synthetic customers or weakening ticket ownership was avoided by adding one `project_inquiries` table. No existing table or customer record is changed.
- The contact endpoint saves name, email, selected service, and message; new inquiries always start as `new`. Unknown request fields cannot set the review status. No outbound email, queue notification, or customer communication is triggered.
- The new migration is `database/migrations/2026_09_17_180000_create_project_inquiries_table.php`. The form requires this migration. Apply this additive migration to the intended local database with `php artisan migrate --path=database/migrations/2026_09_17_180000_create_project_inquiries_table.php`.
- Status updates accept only `new`, `reviewed`, or `closed`. The inbox is never shared in public page props. Failed, interrupted, or unconfirmed updates restore the displayed select value; validation, HTTP, and network failures display an error.
- POST `/contact` uses CSRF protection, server validation, and a five-request-per-ten-minute throttle. Storage failures return a real error and never the success flag. The UI additionally handles expired sessions, rate limiting, other HTTP failures, and network failure.
- Public pages use shared theme tokens and reusable page/card/button styles. Both service URLs share a presentation component, while remaining separate Vite page entry points.
- Existing legal pages and policies are unchanged. No phone, email, hours, history, statistics, testimonials, portfolio, or commercial guarantees were invented.

## Metadata

`SeoHead` provides canonical, social, and structured organization information from verified business details. A Blade fallback renders metadata in the initial HTML for crawlers without JavaScript. Its `data-inertia` keys match Inertia v3, allowing client navigation to replace them. Configured existing product/category/page metadata is retained. Product featured images are the fallback for social previews; relative image paths resolve against the authoritative business website while absolute CDN URLs remain unchanged. Private account, authentication, checkout, cart, settings, and administrative paths receive `noindex,nofollow` in both the fallback and `SeoHead`.

## Verification performed

- Used the code-review graph before targeted implementation review. Graph freshness matched commit `551ce99a7057`; a later impact query identified the shared middleware and SEO reach.
- `php artisan test --compact tests/Feature/BusinessWebsiteTest.php tests/Feature/PublicContentTest.php tests/Feature/SupportTicketTest.php tests/Feature/AdminSupportTicketTest.php`: **24 tests, 468 assertions passed**.
- New tests exercise all eight services, overview/about/contact routes, missing-service 404, valid/invalid service preselection, real inquiry persistence, no customer creation, no outbound mail, protected review status, server validation, simulated storage failure, rate limiting, guest/unauthorized/admin permission boundaries, and allowed status updates.
- Metadata tests verify initial HTML title, social URL, JSON-LD, one canonical tag, and noindex on login.
- The shared `auth.impersonating` flag requires both authenticated guards and a matching `impersonating_user_id` session marker. Tests distinguish independent customer/admin logins from true impersonation, reject mismatched markers, and require an authenticated admin. Authentication controllers and permissions are unchanged.
- PHPStan on the three new controllers and inquiry model: **passed, zero errors**.
- Follow-up `php artisan test --compact tests/Feature/BusinessWebsiteTest.php`: **9 tests, 257 assertions passed**, including relative product-image resolution, explicit SEO image precedence, and absolute CDN URL preservation.
- Full `npm run types:check`: **passed** after implementation and both final fixes.
- Targeted ESLint and Prettier checks: **passed** for owned Vue, TypeScript, and shared-type files.
- `npm run build`: **passed** after extracting the shared service presentation. An initial direct page-to-page import caused Vite to omit the service-detail entry from its manifest; this was fixed and route tests subsequently passed.
- No existing working database was migrated by this workstream. Tests used the repository's isolated SQLite test database and fake mail facilities.
- Responsive browser checks and end-to-end preview verification are recorded by the main redesign workstream. Its contact review prompted mobile form-first ordering and visible focus/scroll to the success message. Success focus now watches the mounted status element after the processing state clears, avoiding the premature onSuccess callback.

## Remaining business information

No business choice blocks the inquiry workflow. Inquiries must be reviewed in the admin inbox; automated notifications and response-time commitments have not been assumed. Existing configurable email/phone appear only when configured. Product claims, prices, catalog provenance, and legal-policy readiness remain part of the main redesign review.
