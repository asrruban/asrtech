# Inquiry follow-up and client project workspaces

Implemented locally in September 2026. These features use the existing Laravel, Inertia, Vue and role-based admin architecture. No new dependency, external integration or automatic customer communication is required.

## Inquiry to quote

- `/admin/inquiries` now filters by status and due/overdue follow-up. Each inquiry has a detail page with an assigned support administrator, a follow-up date and private internal notes.
- Administrators must explicitly select an existing customer account after checking identity. An email match alone never links an inquiry, and the workflow never creates customer accounts.
- An administrator with both `support.manage` and `billing.manage` can link an existing quote owned by that client, or create a draft using an active catalog product, explicit unit price, quantity, billing cycle, tax and validity date. Existing support-only roles cannot mutate billing. Under the current role definitions, the combined permission belongs to super administrators.
- The existing `QuoteService` calculates line items and totals. Creating the draft does not send email, charge a customer, create an order or bypass existing quote approval. Review and send it in the existing Quotes interface.
- Draft quotes remain private to administrators until sent. Quote acceptance and decline recheck the current record under a row lock; repeated acceptance reuses the original order and invoice without another in-app notification. Quote linking is transactional and repeat requests return the existing link. Client ownership is locked after an inquiry has a linked quote or project. Assignment and conversion include explicit audit context.

## Project workspace

Admin entry: `/admin/projects`. Customer entry: `/client-area/projects`.

- Create a workspace for an existing customer, optionally from a linked inquiry or an accepted/converted quote. Source client identity must match. Repeating creation from the same source reuses its workspace.
- Creation makes the project visible immediately to its selected customer. It does not create an invoice or charge. A project created from an inquiry marks that inquiry won.
- Record the agreed scope and target date. Manually manage project status, dated milestones and progress updates. Milestone counts reflect saved records rather than guessed completion percentages.
- Customers and support administrators can post discussion updates and share files. Updates are labeled by client/ASR Tech; internal inquiry notes do not enter the workspace.
- Administrators request approval for a described deliverable. The owning customer approves it or requests changes with a required explanation. Decisions retain the actor and time and cannot be overwritten. Administrators may cancel a pending request; subsequent revisions require a new request. Approval does not authorize payment.
- Pending approvals must be resolved or cancelled before closing a project. All milestones must be completed before its status can be completed. Closed projects remain readable but cannot receive new collaboration. Administrators may reopen them explicitly.

## Access and private files

- Admin project and inquiry routes use existing `support.manage`, admin authentication and mutation audit middleware. Customer routes require authentication, verified email and current authenticated session.
- Every customer read, comment, upload, download and approval action checks project ownership. Nested files, milestones and approvals are checked against their project. Unauthorized resource access returns 404.
- Project files are stored on the explicit `local` disk at `storage/app/private/projects/{id}`. They are never stored on public uploads or exposed through a public URL. Stored paths are omitted from client payloads.
- Download routes enforce authorization each time and force an attachment with `application/octet-stream`, `nosniff` and private/no-store cache headers.
- Both MIME and extension checks restrict uploads to PDF, TXT, PNG, JPEG, WebP and ZIP, up to 10 MB. A failed database record write removes the newly stored file. Uploads are throttled. No credentials should be placed in project discussion or files.

## Verification

`php artisan test --compact tests/Feature/ProjectWorkspaceTest.php` verifies explicit identity linkage, quote ownership and billing permissions, idempotent quote/project conversion, source quote eligibility, private storage, MIME/size limits, nested ownership, customer/admin permissions, email verification, milestone/approval state validation and absence of outgoing mail.

Targeted PHPStan on the new models, controllers and service passes. Vue TypeScript and targeted ESLint pass. New pages use the shared design system and native labeled controls, loading states, validation summaries and confirmation messages.

This document describes application capabilities, not a business promise. ASR Tech must still agree project scope, deadlines and commercial terms with each client. The feature does not invent those details or send external messages automatically.
