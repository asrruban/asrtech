# Added feature route inventory

Snapshot: **298 registered HTTP routes and 105 Vue page components**, refreshed on 17 September 2026. The original redesign had 257 routes and 90 components. This follow-up adds **41 routes and 15 components** without replacing existing authentication, billing, product, support, or administration routes.

[redesign-route-inventory.json](redesign-route-inventory.json) is the complete machine-readable map of current methods, route names, controller actions, gathered middleware and page components. It was regenerated from the live Laravel router; existing component mappings were retained and the new render targets were traced in source. `HEAD` accompanies the listed GET routes. Placeholders are route parameters, not fabricated business data.

## Capabilities and activation requirements

| Feature | Available locally after migrations | Owner/deployment prerequisites |
| --- | --- | --- |
| Password recovery | Guest request/reset forms; broker validation; expiry; single use; retained 2FA and stale-session invalidation | Configure real mail delivery and run the durable queue worker to deliver links. No account enumeration in the public response. |
| Inquiry notifications | Protected settings page, outbox history and retry controls | **Off by default.** Owner must configure a real sender/transport and receiving inbox, confirm sender verification and inbox ownership, explicitly enable notifications/optional receipts, and run worker/scheduler. No addresses are seeded. |
| Inquiry to quote | Assignment, private notes, due follow-ups, explicit customer linkage, existing/draft quote connection | Administrator confirms customer identity and supplies real commercial terms. Quote conversion needs both support and billing permission. Draft creation sends no email and takes no payment. |
| Client project workspaces | Owned lists/details, milestones, progress discussions, private files and explicit approvals | Support administrator creates a workspace for the correct existing customer and records agreed scope/deadlines. No projects or deliverable promises are fabricated. |
| Product compatibility | Existing catalog/details/admin authoring accept published verified version ranges | **No ranges seeded.** Catalog administrator verifies and publishes actual WHMCS/WordPress/PHP compatibility. Unknown/draft ranges do not match filters. |
| Maintenance plans | Public list, protected administration, scoped customer requests and existing checkout integration | **No plans or prices seeded.** Billing administrator publishes real scope/support terms and either links an existing enabled monthly/yearly price or uses a custom quote. Fixed-price checkout requires matching agreed terms; activation requires eligible paid billing. |

Changing mail configuration does not automatically send test/customer emails. Existing legal pages and the need for owner-approved business terms remain unchanged. Queue workers must be restarted when mail transport settings change.

## All added endpoints

“Owner” applies to resource routes; list routes are restricted to the signed-in customer’s records. Every admin route also inherits authentication and admin audit middleware. Additional nested ownership and state checks reside in the controllers/services and are tested in the domain suites.

| Method | Route | Access | Behavior / page |
| --- | --- | --- | --- |
| GET | `/forgot-password` | Guest | Recovery request form |
| POST | `/forgot-password` | Guest; throttled | Queue a generic recovery request |
| GET | `/reset-password/{token}` | Guest | Token reset form |
| POST | `/reset-password` | Guest; throttled | Consume reset token and change password |
| GET | `/client-area/projects` | Signed in; current session; verified email; owner | Owned project list |
| GET | `/client-area/projects/{project}` | Signed in; current session; verified email; owner | Owned project workspace |
| POST | `/client-area/projects/{project}/updates` | Signed in; current session; verified email; owner; throttled | Post customer discussion update |
| POST | `/client-area/projects/{project}/files` | Signed in; current session; verified email; owner; throttled | Upload a private project file |
| GET | `/client-area/projects/{project}/files/{file}` | Signed in; current session; verified email; owner | Download an owned project file |
| POST | `/client-area/projects/{project}/approvals/{approval}` | Signed in; current session; verified email; owner | Approve or request deliverable changes |
| GET | `/maintenance` | Public | Published plan list; valid empty state |
| GET | `/maintenance/{plan}` | Public | Published plan details |
| POST | `/maintenance/{plan}/request` | Signed in; current session; verified email; owner; throttled | Request scoped maintenance after acknowledgement |
| GET | `/client-area/maintenance` | Signed in; current session; verified email; owner | Owned maintenance requests |
| GET | `/client-area/maintenance/{maintenanceRequest}` | Signed in; current session; verified email; owner | Owned request and agreed scope |
| POST | `/client-area/maintenance/{maintenanceRequest}/checkout` | Signed in; current session; verified email; owner | Begin agreed-price checkout |
| GET | `/client-area/maintenance/{maintenanceRequest}/checkout` | Signed in; current session; verified email; owner | Existing checkout review page for agreed price |
| POST | `/client-area/maintenance/{maintenanceRequest}/checkout/pay` | Signed in; current session; verified email; owner; throttled | Existing gateway checkout for agreed price |
| POST | `/client-area/maintenance/{maintenanceRequest}/withdraw` | Signed in; current session; verified email; owner | Withdraw an eligible request |
| GET | `/admin/inquiries/{inquiry}` | Admin; support.manage | Private inquiry follow-up and quote workspace |
| POST | `/admin/inquiries/{inquiry}/quote` | Admin; support.manage; billing.manage | Link an owned quote or create a draft |
| GET | `/admin/projects` | Admin; support.manage | Project administration list/create interface |
| POST | `/admin/projects` | Admin; support.manage | Create a workspace for an explicit client |
| GET | `/admin/projects/{project}` | Admin; support.manage | Administrative project workspace |
| PATCH | `/admin/projects/{project}` | Admin; support.manage | Update scope, target date and status |
| POST | `/admin/projects/{project}/milestones` | Admin; support.manage | Add a milestone |
| PATCH | `/admin/projects/{project}/milestones/{milestone}` | Admin; support.manage | Update a nested milestone |
| POST | `/admin/projects/{project}/updates` | Admin; support.manage | Post a project update |
| POST | `/admin/projects/{project}/files` | Admin; support.manage; throttled | Upload a private project file |
| GET | `/admin/projects/{project}/files/{file}` | Admin; support.manage | Download a private project file |
| POST | `/admin/projects/{project}/approvals` | Admin; support.manage | Request explicit deliverable approval |
| DELETE | `/admin/projects/{project}/approvals/{approval}` | Admin; support.manage | Cancel a pending approval request |
| GET | `/admin/settings/inquiry-notifications` | Admin; settings.manage | Mail setup and paginated delivery history |
| PUT | `/admin/settings/inquiry-notifications` | Admin; settings.manage | Save/enable verified-owner notification settings |
| POST | `/admin/settings/inquiry-notifications/{delivery}/retry` | Admin; settings.manage; throttled | Retry a held or failed delivery |
| GET | `/admin/maintenance/plans` | Admin; billing.manage | Maintenance plan administration |
| POST | `/admin/maintenance/plans` | Admin; billing.manage | Create an explicitly scoped/priced plan |
| PATCH | `/admin/maintenance/plans/{plan}` | Admin; billing.manage | Update/publish a plan |
| GET | `/admin/maintenance/requests` | Admin; support.manage | Maintenance request administration |
| GET | `/admin/maintenance/requests/{maintenanceRequest}` | Admin; support.manage | Service request and billing review |
| PATCH | `/admin/maintenance/requests/{maintenanceRequest}` | Admin; support.manage | Update service status and permitted billing links |

## Existing endpoints extended

- `/contact` still persists an inquiry before optional notification processing. Its successful response means the inquiry is saved, not that an email was delivered.
- `/admin/inquiries` and `PATCH /admin/inquiries/{inquiry}` now support follow-up filters, assignment, private notes and explicit client linkage while retaining support permission checks.
- `/products`, category/subcategory listings, typed product details, and existing admin product create/update forms now support verified version compatibility. Their URLs, purchasing and downloads are preserved.
- `/login` includes recovery navigation and binds pending 2FA challenges to the current password. Existing logged-in `password.update` remains separate from new `password.reset.store`.
- Existing account navigation, protected client session middleware, admin navigation, and shared checkout presentation are reused by the new features.

## Verification actually performed

`WebsiteRouteCoverageTest` now contains **8 tests / 596 assertions**. It exercises:

- 16 empty-account navigation destinations and 37 administrator navigation/create screens.
- Guest recovery pages, an empty maintenance list, a published plan with long scope, and unpublished-plan concealment.
- Guest redirects and email-verification gates for project/request/detail/payment-review destinations.
- Owned project/maintenance details, empty workspace collections, concealed internal notes, wrong-owner 404 responses, and checkout not-ready validation.
- Maintenance payment review through the existing checkout component with an agreed price, asserting that reading it creates no order or subscription.
- New admin detail pages, unauthenticated admin redirects, and support/catalog permission boundaries.

These are HTTP/Inertia and access checks, not browser layout assertions. They use temporary fixtures, an isolated test database, and Mail/Notification/Queue fakes; no real charge or customer communication occurs. `withoutVite()` keeps route tests independent of generated build artifacts; the root task verifies the compiled build separately.

Mutation, retry, token, file, approval and billing behavior is covered separately in `PasswordRecoveryTest`, `InquiryNotificationTest`, `ProjectWorkspaceTest`, `ProductCompatibilityTest`, and `MaintenancePlanTest`. Domain coverage and prerequisite details are recorded in the corresponding feature documents. This inventory does not imply every endpoint has pixel-level QA or that live payment/email providers have been verified.

Full build results, final integrated test totals and any representative desktop/tablet/mobile browser verification belong to [FEATURES-DELIVERY.md](FEATURES-DELIVERY.md).
