# Account, authentication, support and administration redesign

The redesign preserves existing Vue/Inertia forms, URLs, data props, billing actions, admin guards, and pre-existing working-tree features. It introduces a consistent light canvas, charcoal hierarchy, teal actions, modest borders, and shared dark-mode tokens. Existing business actions are retained.

## Implemented presentation

- `ClientAreaHero`, `AccountNav`, `AccountSettingsTabs`, `AccountCard`, `MobileTabBar`, `ServiceCard`, and `InvoicesTable` cover dashboard, orders, products/licenses/downloads, subscriptions/renewals, invoices/refunds/credit notes, quotes, affiliate, profile/password/2FA/export, and notifications. Account navigation includes account settings and notifications, current-page semantics, mobile scrolling and bottom navigation. Active route matching ignores query strings.
- Login, registration, email verification, client 2FA, admin login, and admin 2FA share the new responsive `AuthPanel`. Submissions still use their existing endpoints. Existing form validation is associated using `aria-invalid` and `aria-describedby`. Registration no longer promises instant license delivery.
- Support landing and department selection use real department/documentation data, search, useful empty states, and contact fallbacks. Ticket creation/reply preserve real form submissions and expose loading states. Existing signed-in ticket lists and messages use the same account design.
- Announcements have shared light/dark introductions, readable content, and preserved dates and routing.
- `/dashboard` is now a coherent client account hub. `/settings/appearance` is a live preference screen in the client layout.
- All administrative pages receive shared navigation, surface/form/card/table styling and theme tokens from `AdminLayout`. Dashboard and authentication screens are directly redesigned; business forms remain intact. The project inquiry inbox navigation is guarded by `support.manage`.
- Administration has a skip link, visible mouse/keyboard search trigger, and a Reka dialog with focus containment and Escape behavior. Search has a label and failure messages. Existing search permissions/backend data remain unchanged.
- The generic Laravel icon in the admin sidebar is replaced with a typographic ASR Tech mark. License copy actions now report clipboard failure rather than silently failing.

## Checks actually performed for this scope

- Graph freshness checked first: graph and HEAD `551ce99a7057` matched. Source inspection followed for templates and current dirty files.
- `WebsiteRouteCoverageTest`: 3 tests, 384 assertions passed. It renders 14 client navigation destinations in empty-account state, 33 administration list/create/settings destinations, and verifies a support admin can access inquiries/tickets while catalog/invoices remain forbidden. No mail or notification was sent.
- Targeted ESLint passed across all edited account/auth/support/announcement/shared components, admin layout/search/auth/dashboard, and admin ProductForm.
- Prettier run for edited pages/components and 17 existing admin format warnings at the coordinating agent’s request; no business logic was changed by formatting.
- Vue typecheck passed after initial implementation; subsequent final global checks are coordinated by the root task as concurrent changes settle.
- No browser claims are made by this subtask. Cross-width signed-in and admin browser QA is coordinated by the root task in the isolated local preview.

## Limits and retained decisions

No forgotten-password endpoint or form existed; logged-in password change, email verification, and 2FA recovery remain. No real payments, subscription changes, refunds, customer communications, or deployment were performed by this subtask. Existing license/checkout/ownership/resource-flow tests remain the evidence for those backend workflows, alongside the integrated suite.

See [REDESIGN-ROUTES.md](REDESIGN-ROUTES.md) and [redesign-route-inventory.json](redesign-route-inventory.json) for the full 90-page, 257-route inventory, including conditional and dormant screens.
