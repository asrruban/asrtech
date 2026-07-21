# DESIGN.md: ModulesGarden-Inspired Product Detail

## Source

- URL: https://www.modulesgarden.com/products/whmcs/sms-center
- Capture date: 2026-07-20
- Target stack: Laravel 13, Inertia 3, Vue 3, TypeScript, Tailwind CSS 4
- Evidence: Firecrawl branding and image extraction, page markdown, viewport screenshot, and full-page screenshot

## Reference Screenshot

The local full-page reference capture is intentionally excluded from version
control. Use the source URL above as the visual reference for hierarchy,
density, and page rhythm. The third-party logo, images, promotional creative,
and copy are reference-only and must not be reused in ASRTech.

## Design Summary

A compact B2B software marketplace page with a dark navy product-summary area, a strong green purchase action, a sticky white section rail, and dense white content panels over a cool gray canvas. The implementation should feel established and technical while remaining easier to scan than the source. ASRTech content, assets, and identity remain unchanged.

## Design Tokens

### Colors

- Hero and footer navy: `#26364d` (observed/inferred from screenshot)
- Deep navy: `#1a2333` (inferred for high-contrast panels)
- Primary green: `#4fb250` (observed)
- Green hover: `#439c45` (inferred)
- Page canvas: `#e9edf2` (observed)
- White surface: `#ffffff`
- Primary ink: `#2e3442` (observed)
- Muted copy: `#737980` (observed)
- Border: `#d7dadb` (observed)
- Informational blue: `#4aa7d9` (inferred from product/category accents)

### Typography

- Reference family: Raleway for body and headings (observed).
- ASRTech implementation: retain Instrument Sans to preserve existing brand and avoid importing third-party typography.
- Product title: 36–48px desktop, 30–36px mobile, 700 weight, tight line height.
- Section title: 26–32px, 700 weight.
- Body: 14–16px, 1.65–1.8 line height.
- Labels and tabs: 12–14px, 600–700 weight.

### Spacing And Layout

- Maximum content width: 1280px.
- Desktop hero: three columns for media, description, and purchase card.
- Section rhythm: 40–64px vertical padding.
- Content panel padding: 24px mobile, 32–40px desktop.
- Corners: 4px in the reference; use 10–16px selectively for the established ASRTech component language.
- Shadows: restrained surface shadow; stronger shadow only for the purchase card and mobile CTA.
- Borders: light gray, one pixel.

## Components

### Product Hero

- Cool-gray breadcrumb strip above a dark navy summary.
- Product media on the left, product name and technical metadata in the center, purchase card on the right.
- Compact chips for product type, version, compatibility, and release information.
- On mobile, stack media, content, and purchase card in that order.

### Purchase Card

- White elevated surface against the dark hero.
- Plan selector when multiple prices exist.
- Large effective price with the original price struck through when discounted.
- Green primary CTA and quiet secondary demo/trial action.
- Surface trust cues: support, updates, secure ordering, and setup fee when applicable.

### Sticky Section Rail

- White bar with subtle shadow beneath the hero.
- Green active state and underline.
- Horizontally scrollable on mobile; no wrapped labels.
- Sections: Overview, Features, Screenshots, Changelog, Reviews, Documentation.

### Content Panels

- White panels on `#e9edf2` canvas.
- Overview uses a wide article and a compact technical-information sidebar.
- Feature groups use bordered disclosure rows with plus/chevron affordance and green checks.
- Changelog uses version cards or a timeline.
- Reviews use a responsive card grid.
- Screenshots use a responsive gallery with 16:10 media.

### Mobile Purchase Bar

- Fixed near the bottom edge below `lg`.
- Shows selected price and a green order button.
- Respects safe-area inset and leaves page padding so content is not obscured.

## Page Patterns

1. Breadcrumb
2. Navy product summary with three-column desktop layout
3. Sticky section tabs
4. Pale-gray content canvas with one active white panel
5. Dark conversion footer
6. Mobile-only persistent purchase bar

Responsive behavior:

- Under 1024px: hero becomes one column; purchase card stays full width.
- Under 768px: metadata becomes a two-column grid where possible; tabs scroll horizontally.
- Under 640px: media thumbnails and screenshot grid scroll or stack; content padding reduces; CTAs become full width.

## Content Style

- Professional and direct, aimed at WHMCS operators and technical buyers.
- Lead with the operational result, then list compatibility and implementation detail.
- CTA language is short: “Order now”, “Choose plan”, “View demo”, “Open docs”.
- Avoid reusing ModulesGarden copy, trademarks, promotional artwork, or screenshots.

## Agent Build Instructions

- Adapt the existing ASRTech `Product` payload; do not hardcode the reference product.
- Retain SEO, gallery, price, feature group, requirements, changelog, review, and documentation support.
- Pick a featured price first, otherwise the first enabled price.
- Keep every action keyboard reachable with visible focus styles.
- Use semantic headings, buttons, links, `details`, and lists.
- Prevent horizontal page overflow at 320px; only the tab and thumbnail rails may scroll.
- Preserve dark-mode legibility even though the reference is light-first.

## Rerun Inputs

```text
workflow: firecrawl-website-design-clone
source_url: https://www.modulesgarden.com/products/whmcs/sms-center
target_stack: Laravel 13 + Inertia 3 + Vue 3 + TypeScript + Tailwind CSS 4
output: DESIGN.md + implementation
```
