# ASR Tech redesign delivery

This document records the initial redesign stage. The subsequent six-feature implementation, current verification totals and setup requirements are documented in [FEATURES-DELIVERY.md](FEATURES-DELIVERY.md).

Implemented locally on 17 September 2026 in the existing Laravel 13 / Inertia 3 / Vue 3 / TypeScript / Tailwind 4 application. No production deployment was performed. Existing uncommitted work was retained; a source snapshot was taken before editing. The code-review graph was consulted first, verified against HEAD `551ce99a7057`, and updated after implementation.

## Design and coverage

The website now uses white and pale green surfaces, deep charcoal type, restrained teal actions, a typographic ASR Tech wordmark, locally served Instrument Sans, and a code-native technical illustration. Shared components provide consistent navigation, buttons, cards, forms, account headers, and administrative surfaces. Existing dark appearance preferences remain supported. Unused Raleway font downloads were removed; no dependencies were added.

- Home, Services overview and eight detail pages, About, Contact, legacy software-development URL, support center, announcements, managed content, and legal-page presentation.
- Catalog/search/type/category/subcategory filters, products, image gallery, compatibility and licensing data, reviews, documentation, cart, promotions, checkout, and payment-unavailable states.
- Customer sign-in/registration/email verification/2FA, dashboard/order history, products/licenses/downloads, subscriptions/renewals, invoices/credit notes/refund requests, quotes, support tickets, notifications, affiliate account, profile/password/security/appearance settings.
- Administrative catalog, customers, payments, billing, subscriptions, refunds, quotes, affiliates, reports, support, content, configuration, security, and the new inquiry inbox. Business permissions/actions are retained.
- Maintenance presentation reviewed; the unused starter Welcome component remains dormant.

See [the complete inventory](REDESIGN-ROUTES.md) and [machine-readable route map](redesign-route-inventory.json): 90 Vue views and 257 HTTP routes including redirects, APIs, callbacks, and downloads. Direct composition changes and shared-layout coverage are distinguished per page. The inventory does not claim every resource state was inspected visually.

## Working inquiry flow

POST `/contact` validates and persists real guest inquiries without creating a customer account or sending email. The form includes service selection, pending state, associated field errors, HTTP/network failure handling, and a focused visible success acknowledgment. Administrators with the existing support permission review inquiries at `/admin/inquiries`. Failed status changes revert the visible selection.

One additive `project_inquiries` migration was necessary because the existing ticket system requires an authenticated customer. This migration has been applied to the working local SQLite database. Existing table row counts were checked against a pre-migration backup and are unchanged; no test inquiries or orders were inserted into the working database.

## Verification actually performed

- `npm run build`: passed.
- `npm run types:check`: passed.
- `npm run lint:check`: passed.
- `npm run format:check`: passed.
- `composer lint:check`: passed.
- `php artisan test --compact`: **345 tests passed, 3,054 assertions**. Initial baseline was 333 tests / 2,413 assertions. Tests include genuine inquiry persistence and failure cases, permission boundaries, public routes, 14 customer navigation destinations, 33 admin destinations, and existing authentication/commerce/subscription/license/invoice/support suites.
- `composer types:check`: **fails with 13 pre-existing PHPStan errors** in `Admin/ProductController.php`, `Client/ProductDetailResource.php`, and `PaymentDisputeService.php`. All three files are byte-for-byte unchanged from the initial working-tree snapshot. The new inquiry backend and changed shared middleware pass targeted PHPStan checks.
- `git diff --check`: passed.

Browser testing used an isolated SQLite copy with `MAIL_MAILER=array`, rather than customer records in the working database. No real charge or customer communication was made.

- Public route families, all eight service details, existing product variants, category/subcategory, documentation, support, announcements, and legal pages loaded at **320 CSS pixels** without page overflow.
- Customer dashboard, products/license detail, subscriptions/detail, invoices/detail, quotes, tickets/create, profile, security, notifications, and affiliate pages loaded at **390px** without page overflow. Empty account states also have automated route coverage.
- 32 administrative read/create/settings pages loaded at **390px**, with no page overflow or browser console errors. Admin inbox and search dialog were inspected visually.
- Representative homepage, services, product detail, cart, checkout, account dashboard, invoice, and contact screens were inspected at **390px, 768px, and/or 1440px**; layouts are not claimed to have been visually inspected in every size/state combination.
- Verified mobile menu open/close/Escape; gallery dialog/Escape; catalog search empty state; invalid promotion feedback; service preselection; contact validation and genuine success; inquiry visibility and review-status update in admin.
- Completed a **sandbox** annual-plan checkout through guest cart → sign-in → checkout → customer dashboard. The resulting paid order, license, subscription, and invoice appeared in the isolated preview account.
- Checked dark appearance on account settings and product detail, then restored light. Verified unique canonical tags, absolute social-image URLs, correct title suffixes, private-page noindex, and no displayed legacy unverified testimonial.

## Remaining information and limits

- The existing local catalog is from `DemoCatalogSeeder`; prices, compatibility/version claims, screenshots, add-ons, and licensing/support descriptions need business approval or replacement before launch. They are retained as data, not endorsed as verified releases. Local catalog screens show a preview notice. No WordPress plugin was fabricated to fill the catalog.
- Existing legal records were retained. Policy terms and the handling/retention of inquiry data need owner review before production use; no binding policy was invented.
- No verified email, phone number, or opening hours were supplied/configured, so none were invented. The contact form, address, website, and Facebook link are available.
- Password recovery was added in the subsequent feature implementation; see [recovery details](FEATURES-RECOVERY-NOTIFICATIONS.md). Authenticated password changes remain available.
- Real payment-provider execution, external social-login providers, delivery of email, live server configuration, and production deployment were not tested or performed. Existing integration feature tests use local/test facilities.

## Local preview

The running review preview is [http://127.0.0.1:8040](http://127.0.0.1:8040), using the isolated test copy. Its lifetime is the running local PHP server process.

To run the website against the existing working local database from a new terminal:

```sh
cd /Users/alamin/Project/ASRTech
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Then open [http://127.0.0.1:8000](http://127.0.0.1:8000). For future checkouts or other databases, apply the additive migration first:

```sh
php artisan migrate --path=database/migrations/2026_09_17_180000_create_project_inquiries_table.php
```

For frontend development, `npm run dev` is the verified Vite command and runs alongside the PHP server. No scheduler or queue worker is needed to preview the pages or use the inquiry inbox.
