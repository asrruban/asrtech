# Catalog and commerce redesign coverage

Updated locally on 2026-09-17. This is the catalog/commerce portion of the wider site redesign; it is not a deployment record.

## Presentation implemented

- Light surfaces, charcoal text, restrained teal accents, shared site typography/buttons/layouts, consistent borders and cards.
- Product cards use actual catalog fields and actual configured prices. A reusable abstract component illustration appears only when no catalog image exists; it is not presented as a screenshot.
- Product detail includes a responsive gallery, keyboard-operable lightbox, native radio controls for plan selection, actual price features/setup fees, accessible content tabs, product metadata, requirements, optional services, releases, documentation, and reviews.
- Only reviews marked as verified purchases are rendered or included in the displayed average. The backend's appended legacy testimonials are not treated as verified customer evidence.
- Hardcoded 30-day guarantee, assumed free updates, assumed included support, and generic payment-security promises were removed. Configured licensing and price features continue to render as catalog data.
- Cart and checkout share one totals component so server-calculated tax, setup fees, discounts, currency, and total remain consistent. The complete checkout form works in one mobile column; no overlapping fixed purchase controls.
- Catalog, documentation, cart, and checkout surfaces and text use the shared appearance tokens, preserving contrast when an existing dark-mode preference is active.
- Search, empty states, promotion validation, cart removal loading, cart-add validation, disabled payment submission, and unavailable-payment state are presented explicitly.
- Public product metadata continues through the shared SEO component. Cart and checkout request `noindex,follow`.

## Existing routes and actions retained

| Route | Coverage |
| --- | --- |
| `GET /products` | Catalog search, type filter, category navigation, result count, pagination and empty results |
| `GET /categories/{category}` | Existing category landing rendered by the catalog page |
| `GET /categories/{category}/{group}` | Existing subcategory landing and parent navigation |
| `GET /products/{productType}/{product}` | Full detail, plan selection, gallery, tabs and actual configured content |
| `GET /products/{productType}/{product}/documentation` | Full published guide, product information, external documentation and support navigation |
| `GET /products/{product}` | Existing legacy redirect retained |
| `GET /products/{product}/documentation` | Existing legacy documentation redirect retained |
| `POST /products/{productType}/{product}/reviews` | Existing authorized review create/update form, validation and moderation state |
| `POST /cart/{product}/prices/{price}` | Add to cart in place or continue to cart, preserving the existing `stay_on_product` behavior |
| `GET /cart` | Populated/empty basket, plan details, totals and checkout navigation |
| `DELETE /cart/items/{price}` / `DELETE /cart` | Existing remove and clear actions |
| `POST /cart/promotion` / `DELETE /cart/promotion` | Existing promotion apply/remove and server validation |
| `GET /checkout` / `POST /checkout` | Existing authentication boundary, order review, enabled gateway selection, processing and validation |
| `POST /checkout/{product}/prices/{price}` | Existing direct checkout endpoint untouched |

No backend routes, schema, gateway integrations, billing calculations, permissions, customer records, or session cart semantics were changed by this portion of the work. Links to `/contact` and `/support` use routes available in the redesigned project.

## Verification performed

- Fresh graph checked first: branch `main`, commit `551ce99a70579681dc1b7748e1562e1c04be514e`, current HEAD matched the graph. Used graph imports/tests queries, then targeted source review for Vue markup, controller payloads and commerce behavior.
- Repository `npm run types:check` passed after the main rewrite.
- Targeted ESLint passed across all catalog, cart, checkout and owned components after fixes. Prettier applied only to owned files.
- `php artisan test tests/Feature/CartTest.php tests/Feature/CheckoutTest.php tests/Feature/ProductAuthoringAndReviewTest.php tests/Feature/AdminDocumentationTest.php` passed: 26 tests, 187 assertions. Tests use SQLite in memory, an array mailer, fake storage/HTTP where needed, and the sandbox payment gateway. No real charges or customer email.
- Existing tests verify cart add/replace/remove/clear, returning to product, guest checkout login redirect, authenticated multi-item checkout, license provisioning, disabled/foreign price rejection, gateway validation, product authoring/reviews, and documentation routes.
- Inspected route registration and frontend destinations for all routes above. Reviewed native keyboard controls, labelled validation, long-content wrapping, mobile plan/review controls, tablet search layout, and currency/source-of-truth handling in source.
- A second independent source review identified and corrected mixed hardcoded light surfaces with inherited dark-mode text in owned catalog/commerce pages. Targeted ESLint, TypeScript, and `git diff --check` passed after the correction.
- Browser viewport and final combined build verification are coordinated separately by the root redesign task; this document does not claim that source inspection alone proves browser behavior.

## Catalog provenance requiring owner review

The local three-product catalog matches `database/seeders/DemoCatalogSeeder.php`: WHMCS Automation Toolkit, Hosting Business Vue Template, and Professional Web Development. That seeder supplies illustrative prices, screenshots, compatibility, license terms, add-ons, version history and a legacy testimonial. Presence in the database does not independently verify release availability or commercial terms. Records and purchasing workflows were preserved. Confirm these details and replace demonstration material before launch. The invented legacy review is not shown as verified evidence.

There is no existing trustworthy per-record demo/provenance marker, so this redesign does not attempt to infer real product status from names or delete product data.
