# Verified product compatibility

The existing product catalog now accepts `whmcs_version`, `wordpress_version`, and `php_version` filters. Search, product type, category/subcategory URLs, and pagination continue to work together. Category links retain active filters. The catalog has loading, validation, no-match, and clear-filter states. Product pages display published compatibility ranges separately from retained freeform requirements.

## Authoring

Administrators with the existing `catalog.manage` permission can open a product's **Verified compatibility** section, add WHMCS / WordPress / PHP ranges, and check **I have verified this range. Publish it for buyers.** Unchecked rows are saved as drafts and never used for public filtering or exposed in public product details. Removing every row from the form deletes the ranges. Older integrations which omit compatibility fields leave existing ranges intact.

No ranges are seeded or inferred from existing descriptions, legacy compatibility text, product types, screenshots, or demo catalog data. ASR Tech must verify and publish its real product ranges before compatibility searches return those products.

## Matching policy

- Accepted formats: stable `major.minor` or `major.minor.patch`, with decimal components 0–999 and no leading zeroes. Wildcards, prereleases, qualifiers, and additional components are rejected with validation errors.
- A missing patch component means zero: `8.2` is exactly `8.2.0`, not every 8.2 patch.
- Bounds are inclusive: `minimum <= installed version <= maximum`. Use identical bounds for one exact version or multiple rows for disjoint supported ranges.
- Numeric ordering correctly places `8.10` after `8.9`. Validation compares normalized versions with PHP `version_compare`; queries compare indexed integer values calculated from three fixed-width components, which have the same ordering for the accepted format.
- Every supplied platform filter must match a published row for that same platform. Missing or draft compatibility is unknown and does not match. Empty results do not prove incompatibility.

## Storage and rollout

`2026_09_17_192000_create_product_compatibilities_table.php` adds a separate table with a cascading product foreign key. Existing product/pricing/release data is untouched. Creation and replacement occur in the existing product-save transaction. The numeric comparison columns are derived in the model and cannot be supplied through the admin request.

Apply this migration to the local or isolated preview database before opening product details:

```sh
php artisan migrate --path=database/migrations/2026_09_17_192000_create_product_compatibilities_table.php
```

Do not seed compatibility values for the demo catalog merely to populate filters. The empty verified-compatibility state is intentional until data is confirmed.

## Verification performed

- `php artisan test --compact tests/Feature/ProductCompatibilityTest.php tests/Feature/AdminCatalogManagementTest.php tests/Feature/ProductAuthoringAndReviewTest.php`: 23 tests, 356 assertions passed.
- New tests cover inclusive bounds, numeric ordering, omitted patch behavior, unknown/draft/hidden products, disjoint ranges, platform intersection, retained search/type/subcategory/pagination, invalid versions, admin create/update/removal, payload validation, and access boundaries.
- `npm run types:check`: passed.
- Targeted ESLint and Prettier checks on changed Vue components: passed.
- Pint on the touched PHP files and migration: passed.
- Targeted PHPStan reported the previously documented 11 findings in existing `Admin/ProductController.php` initial-release cleanup and `Client/ProductDetailResource.php` legacy changelog/review handling. It reported no compatibility-code findings.
- Tests use in-memory SQLite and the array mailer. No checkout, charges, or customer communications were performed by this feature's verification.

Browser integration and the full repository build/test results belong to the main feature delivery report; they are not asserted by this isolated feature note.
