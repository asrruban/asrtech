# ASR Tech design system

## Identity and content

The site uses a typographic ASR Tech wordmark when an administrator has not supplied a logo. Public business identity comes from `config/asrtech.php` through the shared `business` prop: ASR Tech, Al Amin (Owner and Founder), the Purbadhala address, asrtech.bd, and the supplied Facebook page. No business history, sales metrics, client logos, or testimonials are invented.

The light surface, charcoal, and teal palette is a design assumption for this redesign. It is not a claimed historical brand standard. Existing theme preferences remain available.

## Visual language

- Primary/action: `#087f75`; hover: `#06645d`.
- Ink: `#172c2c`; muted copy: `#5c6e6d`.
- White surfaces; pale green canvas: `#f4f7f5`; borders: `#dce5e1`.
- Footer and conceptual code illustration: deep charcoal green.
- Existing locally bundled Instrument Sans typography; no additional font dependency.
- Content width: 1240px, with responsive 16px/24px gutters.
- Editorial headings with restrained letter spacing; readable body copy and clear form labels.
- Shared `.site-container`, `.page-intro`, `.section-kicker`, `.display-title`, `.section-title`, `.body-copy`, `.surface-card`, `.button-primary`, and `.button-secondary` classes.
- `.page-intro` sets the surface and border. Its content container controls vertical padding.

## Composition

The homepage combines a light split hero, a code-native illustration of connected services, four service panels, real catalog payloads, a three-step engagement explanation, founder/location introduction, native disclosure FAQs, and a shared contact footer. The illustration is explicitly conceptual, not a screenshot or portfolio claim.

Services separate development, platform extensions, and ongoing management. Product pages retain data-driven media, plans, requirements, documentation, reviews, and purchase controls. Missing media uses abstract technical artwork rather than a fabricated screenshot. Customer and administrative screens use shared navigation and form/table patterns with the same colors and typography.

## Interaction and accessibility

Keyboard focus is visible. Mobile navigation is an in-flow disclosure, so closed links are not focusable. Dialogs use the existing Reka primitives. Forms use labels, native controls, associated error descriptions, and loading states. Contact success requires database persistence. Motion respects reduced-motion preferences. Public decorative artwork is clipped within its section to prevent page overflow.

## Content boundaries

The existing local database includes DemoCatalogSeeder records, including example prices, compatibility information, and screenshots. A local preview notice identifies sample catalog content. Those records and existing commerce integrations are preserved; they require business review before any production release. Legacy unverified testimonials are not rendered. Existing managed legal content is retained and requires owner review; the redesign does not create new legal promises.
