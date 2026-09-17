# Maintenance service plans

ASR Tech can publish real WordPress, WHMCS, or server maintenance offerings, accept customer requests against an agreed scope, and connect those requests to the existing subscription or quote workflows. The migration creates no demo plans, prices, support commitments, or legal terms. An empty public plan catalog is intentional until the business configures and publishes its offerings.

## Configuration and customer flow

1. A billing administrator creates a plan at `/admin/maintenance/plans`. Enter its actual scope, exclusions, and support arrangements. Attach an enabled monthly/yearly product price using the existing checkout, or leave pricing custom so a quote can be agreed. External purchase links and disabled/non-recurring prices are not accepted.
2. Published plans appear at `/maintenance`. The client signs in, reviews the scope, acknowledges it, and submits their requirements. A content-based version token prevents submitting an outdated plan. Open duplicate requests for the same customer and plan are rejected.
3. The request records a scope and billing snapshot. Later plan/price edits do not silently change that agreement. The customer sees their requests at `/client-area/maintenance`; support staff review them at `/admin/maintenance/requests`.
4. Support can review the request, post customer-visible updates, keep private internal notes, and change its service status. Linking or changing billing requires `billing.manage`. Customers may withdraw only while requested/reviewing; closed requests cannot be reopened to bypass agreement.
5. For a priced request, staff set `awaiting_payment`. The customer reviews the existing checkout screen, current taxes, and configured gateways, then pays through the existing checkout service. Free trials are not offered through this maintenance checkout. The current product price must still match the agreed amount, currency, cycle, and setup fee.
6. Payment is linked to the request. Staff explicitly confirm service activation once billing is valid. An active service requires a matching subscription providing access, or the customer's converted custom quote with a paid matching order. Draft quotes and internal notes are hidden from customers.

The customer subscription link continues to use the existing renewal, cancellation, billing portal, and invoice workflows. With custom pricing, staff use the existing quote workflow to agree and collect payment; this feature does not infer recurring charges from freeform scope.

## Checkout protection and recovery

Maintenance checkout reserves its order inside the existing order-creation transaction before invoking a gateway. A repeated POST cannot create another order while the first is pending/paid/refunded, when a subscription is already linked, or when the same customer has a non-canceled subscription for that price. Another maintenance request with a pending payment for the same customer and price also blocks duplicate payment.

- Confirmed failed payments can be retried, producing an auditable new order.
- Pending hosted payments retain their original gateway redirect, encrypted at rest, so the customer can resume the same payment session.
- Uncertain gateway exceptions retain the pending order and display its reference with a support path. They never automatically create or charge a replacement order. Staff must reconcile an uncertain payment using the existing provider/order facilities.
- Delayed payment settlement exposes the resulting subscription and removes the resume action without requiring a second charge.

The common Checkout/Create component accepts optional action and back-link props; existing ordinary cart checkout retains its original defaults. Product authoring now preserves price IDs by billing cycle and disables removed prices instead of deleting identities referenced by subscriptions or agreed plans.

## Local setup and verification

Apply the additive migrations before previewing:

```sh
php artisan migrate
```

Relevant migrations are `2026_09_17_193000_create_maintenance_plans_tables.php` and `2026_09_17_195000_add_checkout_reservation_to_maintenance_requests.php`. The second migration also upgrades previews that had already applied the initial plan schema.

Verified during backend implementation:

- `php artisan test --compact tests/Feature/MaintenancePlanTest.php`: 13 tests / 226 assertions passed.
- The related cart, checkout, subscription, free-trial, product-authoring, and compatibility suites passed with the initial 12 maintenance tests: 61 tests / 794 assertions. The added thirteenth maintenance test then passed with the complete maintenance suite.
- Targeted PHPStan passed for all maintenance controllers, models, and service, plus shared CheckoutService and ProductService.
- Vue TypeScript checking and targeted lint passed for the extended existing checkout component; Pint passed for the backend.
- Tests use in-memory SQLite, a fake mailer, the sandbox gateway, and mocked redirect/failure/timeout gateways. No real charge, customer email, or live provider operation occurred.

The main delivery report owns final browser, whole-repository checks, and preview migration status. Business scope, prices, exclusions, support terms, and real recurring-provider configuration still require verified business data before publishing plans.
