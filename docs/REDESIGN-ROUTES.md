# ASR Tech redesign route coverage

Inventory captured from the application router and controller render methods on 2026-09-17. It contains **105 Vue page components and 298 registered HTTP routes** (including APIs, downloads, redirects, callbacks, and framework endpoints). The existing architecture remains Laravel + Inertia + Vue/TypeScript. The original redesign snapshot had 90 components and 257 routes; the follow-up feature implementation adds 15 components and 41 routes. The current inventory was refreshed after those additions.

Graph checked first: HEAD `551ce99a7057`, graph at matching HEAD; working-tree changes were already present and preserved. Route/action/middleware source of truth: [redesign-route-inventory.json](redesign-route-inventory.json). Static catalog and public-page helper render calls were traced explicitly. Database identifiers in paths are placeholders, not fabricated public products.

## How to read this inventory

- **Direct** means that page composition or presentation was changed. **Shared** means the common layout and design tokens carry the redesign while the page’s business form and data stay in place.
- Feature-test references record server response, validation, ownership, or workflow coverage. They do not claim pixel-level browser verification of every state.
- `WebsiteRouteCoverageTest` checks 16 customer navigation destinations with an empty verified account, 37 administration navigation/create destinations, public recovery/maintenance pages, owned detail and checkout-review pages, unpublished plans, guest/email-verification gates, ownership, and support/catalog role boundaries (8 tests / 596 assertions). Email, notification, and queue fakes assert that these read checks send no messages or create billing records.
- Build and Vue type checks cover all compiled page components. Integrated browser and final command results are recorded in [the delivery notes](REDESIGN-DELIVERY.md); do not infer live gateway verification from feature tests.

## Page components and read routes

### Public, content, catalog and commerce

| Page component | GET route(s) | Design coverage | Verification reference |
| --- | --- | --- | --- |
| `Client/About` | `/about` | Direct page redesign + shared client theme | BusinessWebsiteTest |
| `Client/Cart/Index` | `/cart` | Direct page redesign + shared client theme | PromotionAndTaxTest, CartTest |
| `Client/Checkout/Create` | `/checkout` | Direct page redesign + shared client theme | PromotionAndTaxTest, CartTest |
| `Client/Contact` | `/contact` | Direct page redesign + shared client theme | BusinessWebsiteTest |
| `Client/Home` | `/` | Direct page redesign + shared client theme | AdminSeoSettingsTest |
| `Client/Maintenance` | Storefront middleware when maintenance mode is enabled (503) | Direct maintenance redesign + shared client theme; middleware-controlled 503 screen | AdminGeneralSettingsTest |
| `Client/Pages/Show` | `/pages/{page}`; `/{legalPage}` (`legalPage`: terms-of-service, privacy-policy, refund-policy; existing published records required) | Direct page redesign + shared client theme | PublicContentTest |
| `Client/Products/Documentation` | `/products/{productType}/{product}/documentation` | Direct page redesign + shared client theme | StorefrontTest |
| `Client/Products/Index` | `/products`; `/categories/{category}`; `/categories/{category}/{group}` | Direct page redesign + shared client theme | StorefrontTest, TaxonomySeoTest |
| `Client/Products/Show` | `/products/{productType}/{product}` | Direct page redesign + shared client theme | StorefrontTest, ProductAuthoringAndReviewTest |
| `Client/Services/Index` | `/services` | Direct page redesign + shared client theme | BusinessWebsiteTest |
| `Client/Services/Show` | `/services/{service}` | Direct page redesign + shared client theme | BusinessWebsiteTest |
| `Client/SoftwareDevelopment` | `/software-development` | Direct page redesign + shared client theme | PublicContentTest |

### Authentication, accounts and settings

| Page component | GET route(s) | Design coverage | Verification reference |
| --- | --- | --- | --- |
| `Client/Account/Affiliate` | `/client-area/affiliate` | Account hero/navigation + aligned page surfaces/actions/validation | AffiliateTest, WebsiteRouteCoverageTest |
| `Client/Account/ChangePassword` | `/client-area/change-password` | Account hero/navigation + aligned page surfaces/actions/validation | WebsiteRouteCoverageTest, ProfileUpdateTest |
| `Client/Account/CreditNote` | `/client-area/credit-notes/{creditNote}` | Account hero/navigation + aligned page surfaces/actions/validation | Build/type coverage; dynamic route behavior in domain suites |
| `Client/Account/Details` | `/client-area/account-details` | Account hero/navigation + aligned page surfaces/actions/validation | WebsiteRouteCoverageTest, ProfileUpdateTest |
| `Client/Account/ExtendSubscription` | `/client-area/subscriptions/{subscription}/extend` | Account hero/navigation + aligned page surfaces/actions/validation | Build/type coverage; dynamic route behavior in domain suites |
| `Client/Account/Index` | `/client-area` | Account hero/navigation + aligned page surfaces/actions/validation | EmailVerificationTest, PromotionAndTaxTest, AdminUserCreationTest |
| `Client/Account/Invoice` | `/client-area/invoice/{invoice}` | Account hero/navigation + aligned page surfaces/actions/validation | ClientAccountTest |
| `Client/Account/Invoices` | `/client-area/invoices` | Account hero/navigation + aligned page surfaces/actions/validation | ClientAccountTest, WebsiteRouteCoverageTest |
| `Client/Account/Notifications` | `/client-area/notifications` | Account hero/navigation + aligned page surfaces/actions/validation | ClientNotificationTest, WebsiteRouteCoverageTest |
| `Client/Account/Product` | `/client-area/product/{license}` | Account hero/navigation + aligned page surfaces/actions/validation | ProductReleaseDownloadTest, ClientAccountTest |
| `Client/Account/Products` | `/client-area/products` | Account hero/navigation + aligned page surfaces/actions/validation | ClientAccountTest, WebsiteRouteCoverageTest |
| `Client/Account/Quote` | `/client-area/quotes/{quote}` | Account hero/navigation + aligned page surfaces/actions/validation | Build/type coverage; dynamic route behavior in domain suites |
| `Client/Account/Quotes` | `/client-area/quotes` | Account hero/navigation + aligned page surfaces/actions/validation | QuoteWorkflowTest, WebsiteRouteCoverageTest |
| `Client/Account/Security` | `/client-area/security` | Account hero/navigation + aligned page surfaces/actions/validation | ClientTwoFactorTest, WebsiteRouteCoverageTest |
| `Client/Account/Subscription` | `/client-area/subscriptions/{subscription}` | Account hero/navigation + aligned page surfaces/actions/validation | RecurringSubscriptionTest |
| `Client/Account/Subscriptions` | `/client-area/subscriptions` | Account hero/navigation + aligned page surfaces/actions/validation | ClientNotificationTest, WebsiteRouteCoverageTest, RecurringSubscriptionTest |
| `Client/Auth/Login` | `/login` | Direct AuthPanel redesign + accessible existing forms | BusinessWebsiteTest, AdminGeneralSettingsTest, ClientAccountTest |
| `Client/Auth/Register` | `/register` | Direct AuthPanel redesign + accessible existing forms | EmailVerificationTest, AffiliateTest, AdminGeneralSettingsTest |
| `Client/Auth/TwoFactorChallenge` | `/two-factor-challenge` | Direct AuthPanel redesign + accessible existing forms | ClientTwoFactorTest |
| `Client/Auth/VerifyEmail` | `/verify-email` | Direct AuthPanel redesign + accessible existing forms | EmailVerificationTest, AdminGeneralSettingsTest, AuthenticationTest |
| `Dashboard` | `/dashboard` | Direct page redesign + shared client theme | DashboardTest, WebsiteRouteCoverageTest |
| `settings/Appearance` | `/settings/appearance` | Direct page redesign + shared client theme | WebsiteRouteCoverageTest |

### Support and announcements

| Page component | GET route(s) | Design coverage | Verification reference |
| --- | --- | --- | --- |
| `Client/Announcements/Index` | `/announcements` | Direct page redesign + shared client theme | AnnouncementTest |
| `Client/Announcements/Show` | `/announcements/{announcement}` | Direct page redesign + shared client theme | Build/type coverage; dynamic route behavior in domain suites |
| `Client/Support/Create` | `/client-area/tickets/create` | Direct page redesign + shared client theme | PublicContentTest, SupportTicketTest, WebsiteRouteCoverageTest |
| `Client/Support/Departments` | `/support/ticket` | Direct page redesign + shared client theme | PublicContentTest |
| `Client/Support/Index` | `/client-area/tickets` | Direct page redesign + shared client theme | SupportTicketTest, ClientNotificationTest, WebsiteRouteCoverageTest |
| `Client/Support/Landing` | `/support` | Direct page redesign + shared client theme | PublicContentTest |
| `Client/Support/Show` | `/client-area/ticket/{ticket}` | Direct page redesign + shared client theme | SupportTicketTest |

### Administration

| Page component | GET route(s) | Design coverage | Verification reference |
| --- | --- | --- | --- |
| `Admin/Auth/Login` | `/admin/login` | Direct authentication redesign via AuthPanel | AdminDocumentationTest, AdminCatalogManagementTest, AdminUserCreationTest |
| `Admin/Auth/TwoFactorChallenge` | `/admin/two-factor-challenge` | Direct authentication redesign via AuthPanel | AdminSecurityTest |
| `Admin/Catalog/Categories/Index` | `/admin/categories` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminCatalogManagementTest, TaxonomySeoTest, WebsiteRouteCoverageTest |
| `Admin/Catalog/Groups/Index` | `/admin/subcategories`; `/admin/groups` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminCatalogManagementTest, TaxonomySeoTest, WebsiteRouteCoverageTest |
| `Admin/Catalog/ProductReleases/Index` | `/admin/products/{product}/releases` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | Build/type coverage; dynamic route behavior in domain suites |
| `Admin/Catalog/ProductReviews/Index` | `/admin/product-reviews` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | ProductAuthoringAndReviewTest, WebsiteRouteCoverageTest |
| `Admin/Catalog/ProductTypes/Index` | `/admin/product-types` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminCatalogManagementTest, WebsiteRouteCoverageTest |
| `Admin/Catalog/Products/Create` | `/admin/products/create` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | WebsiteRouteCoverageTest |
| `Admin/Catalog/Products/Edit` | `/admin/products/{product}/edit` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | Build/type coverage; dynamic route behavior in domain suites |
| `Admin/Catalog/Products/Index` | `/admin/products` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminCatalogManagementTest, AdminSecurityTest, ProductAuthoringAndReviewTest |
| `Admin/Commerce/Affiliates` | `/admin/affiliates` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AffiliateTest, WebsiteRouteCoverageTest |
| `Admin/Commerce/Promotions/Index` | `/admin/promotions` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | PromotionAndTaxTest, WebsiteRouteCoverageTest |
| `Admin/Commerce/Quotes/Index` | `/admin/quotes` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | QuoteWorkflowTest, WebsiteRouteCoverageTest |
| `Admin/Commerce/TaxRates/Index` | `/admin/tax-rates` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | PromotionAndTaxTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/ApiTokens` | `/admin/settings/api-tokens` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | RestApiTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/Settings/EmailTemplates/Edit` | `/admin/settings/emailtemplates/{emailtemplate}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminEmailTemplateTest |
| `Admin/Configuration/Settings/EmailTemplates/Index` | `/admin/settings/emailtemplates` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminEmailTemplateTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/Settings/Gateways` | `/admin/settings/gateways` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminGatewaySettingsTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/Settings/General` | `/admin/settings/general` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminGeneralSettingsTest, AdminStorageSettingsTest, AdminSecurityTest |
| `Admin/Configuration/Settings/Seo` | `/admin/settings/seo` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSeoSettingsTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/Settings/Storage` | `/admin/settings/storage` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminStorageSettingsTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/Webhooks` | `/admin/settings/webhooks` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | OutboundWebhookTest, WebsiteRouteCoverageTest |
| `Admin/Content/Announcements` | `/admin/announcements` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | WebsiteRouteCoverageTest, AnnouncementTest |
| `Admin/Content/Pages/Create` | `/admin/pages/create` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | WebsiteRouteCoverageTest |
| `Admin/Content/Pages/Edit` | `/admin/pages/{page}/edit` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | Build/type coverage; dynamic route behavior in domain suites |
| `Admin/Content/Pages/Index` | `/admin/pages` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminPageManagementTest, WebsiteRouteCoverageTest |
| `Admin/Dashboard` | `/admin/dashboard` | Direct page redesign + AdminLayout | AdminGeneralSettingsTest, AdminSupportTicketTest, AdminAuthenticationTest |
| `Admin/Docs/Index` | `/admin/docs` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminDocumentationTest, WebsiteRouteCoverageTest |
| `Admin/Invoices/Index` | `/admin/invoices` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminInvoiceManagementTest, AdminBulkActionsTest, WebsiteRouteCoverageTest |
| `Admin/Invoices/Show` | `/admin/users/{user}/invoice/{invoice}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminInvoiceManagementTest, AdminUserManagementTest |
| `Admin/Licenses/Show` | `/admin/licenses/{license}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminLicenseManagementTest |
| `Admin/Payments/Index` | `/admin/payments` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSecurityTest, PaymentReliabilityTest, WebsiteRouteCoverageTest |
| `Admin/Payments/Show` | `/admin/payments/webhooks/{webhookEvent}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | PaymentReliabilityTest |
| `Admin/RefundRequests/Index` | `/admin/refund-requests` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | WebsiteRouteCoverageTest |
| `Admin/RefundRequests/Show` | `/admin/refund-requests/{refundRequest}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | Build/type coverage; dynamic route behavior in domain suites |
| `Admin/Reports/Index` | `/admin/reports` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminReportsTest, WebsiteRouteCoverageTest |
| `Admin/Security/Index` | `/admin/security` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSecurityTest, WebsiteRouteCoverageTest |
| `Admin/Subscriptions/Index` | `/admin/subscriptions` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | WebsiteRouteCoverageTest, RecurringSubscriptionTest |
| `Admin/Subscriptions/Show` | `/admin/subscriptions/{subscription}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | RecurringSubscriptionTest |
| `Admin/Support/Departments/Create` | `/admin/support/departments/create` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSupportDepartmentTest, WebsiteRouteCoverageTest |
| `Admin/Support/Departments/Edit` | `/admin/support/departments/{department}/edit` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSupportDepartmentTest |
| `Admin/Support/Departments/Index` | `/admin/support/departments` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSupportDepartmentTest, AdminSupportTicketTest, WebsiteRouteCoverageTest |
| `Admin/Support/Inquiries/Index` | `/admin/inquiries` | Direct page redesign + AdminLayout | BusinessWebsiteTest, WebsiteRouteCoverageTest |
| `Admin/Support/Tickets/Index` | `/admin/support/tickets` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSupportTicketTest, WebsiteRouteCoverageTest |
| `Admin/Support/Tickets/Show` | `/admin/support/tickets/{ticket}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminSupportTicketTest |
| `Admin/Users/Index` | `/admin/users` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminUserCreationTest, AdminBulkActionsTest, AdminUserManagementTest |
| `Admin/Users/Show` | `/admin/users/{user}/{tab?}` | Shared AdminLayout, tokens, navigation, card/form/table treatment; workflows retained | AdminClientProfileTest, AdminUserManagementTest |

### Dormant scaffold

| Page component | GET route(s) | Design coverage | Verification reference |
| --- | --- | --- | --- |
| `Welcome` | No registered route | Dormant starter view; not routed | Build/type coverage; dynamic route behavior in domain suites |

## Follow-up feature pages

These 15 additional Vue pages use the established shared design system. The maintenance payment review also reuses `Client/Checkout/Create`; it is not counted as another component. All 41 additional endpoints, conditions and access requirements are listed in [FEATURES-ROUTES.md](FEATURES-ROUTES.md).

| Page component | GET route | Verification reference |
| --- | --- | --- |
| `Client/Auth/ForgotPassword` | `/forgot-password` | PasswordRecoveryTest, WebsiteRouteCoverageTest |
| `Client/Auth/ResetPassword` | `/reset-password/{token}` | PasswordRecoveryTest, WebsiteRouteCoverageTest |
| `Admin/Configuration/Settings/InquiryNotifications` | `/admin/settings/inquiry-notifications` | InquiryNotificationTest, WebsiteRouteCoverageTest |
| `Admin/Support/Inquiries/Show` | `/admin/inquiries/{inquiry}` | ProjectWorkspaceTest, WebsiteRouteCoverageTest |
| `Admin/Projects/Index` | `/admin/projects` | ProjectWorkspaceTest, WebsiteRouteCoverageTest |
| `Admin/Projects/Show` | `/admin/projects/{project}` | ProjectWorkspaceTest, WebsiteRouteCoverageTest |
| `Client/Projects/Index` | `/client-area/projects` | ProjectWorkspaceTest, WebsiteRouteCoverageTest |
| `Client/Projects/Show` | `/client-area/projects/{project}` | ProjectWorkspaceTest, WebsiteRouteCoverageTest |
| `Client/MaintenancePlans/Index` | `/maintenance` | MaintenancePlanTest, WebsiteRouteCoverageTest |
| `Client/MaintenancePlans/Show` | `/maintenance/{plan}` | MaintenancePlanTest, WebsiteRouteCoverageTest |
| `Client/Account/Maintenance/Index` | `/client-area/maintenance` | MaintenancePlanTest, WebsiteRouteCoverageTest |
| `Client/Account/Maintenance/Show` | `/client-area/maintenance/{maintenanceRequest}` | MaintenancePlanTest, WebsiteRouteCoverageTest |
| `Admin/Maintenance/Plans` | `/admin/maintenance/plans` | MaintenancePlanTest, WebsiteRouteCoverageTest |
| `Admin/Maintenance/Requests` | `/admin/maintenance/requests` | MaintenancePlanTest, WebsiteRouteCoverageTest |
| `Admin/Maintenance/Request` | `/admin/maintenance/requests/{maintenanceRequest}` | MaintenancePlanTest, WebsiteRouteCoverageTest |

## Retained route families and workflows

| Family | Retained behavior and scope |
| --- | --- |
| Authentication | Client/admin credential sign-in, registration, email verification/resend, OAuth redirects/callbacks for enabled providers, sign-out, client/admin two-factor challenges, administrator impersonation. Native customer password recovery now uses the existing Laravel broker, a newly required token table, generic responses, encrypted queued delivery, one-hour expiry, and single-use tokens. Existing change-password URLs remain unchanged. |
| Catalog | Catalog search/type filters/pagination; category/subcategory landing routes; typed product/detail/documentation URLs; legacy product redirects; authenticated reviews. All catalog output remains database-driven. |
| Commerce | Cart add/remove, promotion apply/remove, checkout, mock/local and configured payment gateway handoffs/callbacks; paid-order provisioning retained. No real charges are authorized or tested. |
| Account and licenses | Owned products/license details, release downloads, license reissue, subscriptions (cancel/resume/extend/billing portal), invoices/PDFs, credit notes/PDFs, refund requests/cancellation, quotes accept/decline, affiliate join/history, notifications/read-all, profile/password/security, data export and account deletion. |
| Projects and maintenance | Owned project workspaces, milestone/progress/file collaboration, explicit deliverable approvals; published maintenance plans, scoped requests, agreed-price checkout through existing integrations, and permission-checked service/billing management. No new prices or business promises are seeded. |
| Support | Public department/docs discovery; authenticated ticket creation, reading, reply, close; protected department fields and ticket operations in admin. Existing backend permissions remain authoritative. |
| Business inquiries | Persisted contact submissions with validation/throttling, a support.manage-protected inbox, assignments/private notes/follow-up dates, explicit client linkage and permission-checked quote conversion. Optional durable email notifications and acknowledgements start disabled pending verified owner configuration. Tests use no external customer messages. |
| Administration | Catalog CRUD, releases/files, reviews, promotions/tax, customer profiles/manual orders, invoice/payment/refund/license/subscription management, quotes, affiliates, reporting/exports, managed pages/announcements, departments/tickets, security/roles, settings/gateways/storage/SEO/email templates, API tokens/webhooks and search. |
| Integrations | Existing license verification API, REST API v1, inbound email and payment callbacks/webhooks, outbound-webhook tools, queues and scheduled business processes preserved. Technical routes are listed in the JSON; they are not new public products. |
| Technical responses | Invoice/credit-note PDFs, exports, downloads, OAuth/gateway redirects and JSON endpoints retain existing output rather than receiving storefront markup. Existing mail Blade templates are retained; no external messages sent as design validation. |

## Aliases, conditional screens and business decisions

- `/account`, `/settings`, `/settings/profile`, admin group/settings aliases, legacy product/documentation URLs, and other existing redirects remain in the route map.
- `/software-development` remains available and uses the new service detail presentation.
- `/settings/appearance` remains live, using the client layout. Light/dark/system preference is retained; colors use shared tokens.
- `Client/Maintenance` is conditional middleware output, not a direct page route. `Welcome` is an unused starter component and has no user-facing route.
- Legal pages depend on published managed-page records. Missing policies require owner-approved terms; no binding policy copy is invented.
- Customer forgotten-password recovery is now implemented. Sending reset links requires a configured mail transport and queue worker. Logged-in password change and two-factor recovery codes remain implemented, and password changes invalidate pending two-factor challenges.
- Empty catalog, documentation, departments, account history, and admin tables are legitimate states. The redesign does not populate them with fictional business records.

## Browser verification status

Cross-width browser QA is coordinated in the root task using an isolated local database. Feature tests and compiled views cover the full inventory; browser QA samples representative public, authenticated, commerce, and administration screens and must be reported separately. Live provider transactions and production deployment remain outside scope.
