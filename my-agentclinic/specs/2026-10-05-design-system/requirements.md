# Requirements: AgentClinic Design System

## Scope
Apply the AgentClinic design system (published as a Claude design-system artifact: calm paper, scrubs-green, one balm accent, Newsreader + Instrument Sans + JetBrains Mono, pill shapes) to the whole app: every page, the e-mails and the six error pages.

Decisions (from the user): **reskin everything**, **night theme follows the system** (`prefers-color-scheme`), **visual only** (no copy or translation changes).

Out of scope: rewriting EN/FR texts, new features, a manual theme toggle, the design system's React components (the app is Blade).

## Decisions
- **Tokens as CSS variables** in `resources/css/app.css` (`:root` = Day ward, `@media (prefers-color-scheme: dark)` = Night shift): surface, surface-raised, surface-sunk, line, line-strong, ink, ink-muted, scrub(+soft, on), balm(+soft, on), dose(+soft, ink, on), critical(+soft), radii, shadows, focus ring. Values copied exactly from the design system's `tokens.json`.
- **Tailwind remap, not a rewrite**: `tailwind.config.js` maps the existing utility families onto the tokens (`indigo` → scrub, `gray` and `white` → surfaces/ink/lines, `red` → critical, `green` → scrub, `yellow` → dose) so existing Blade classes follow the theme in both modes. Where a class cannot be remapped honestly (text on a filled colour), use the `on-*` token.
- **Fonts**: Newsreader for headings and page titles, Instrument Sans for the interface, JetBrains Mono for ids and times. Self-hosted woff2 via `@fontsource` (no third-party request at runtime).
- **Shape**: primary button, status tags and queue chips are pills; cards `radius-lg`, notices `radius-md`, inputs `radius-sm`.
- **Status**: always a word plus colour (Stable/Booked, Under observation, Critical, Discharged/Cancelled mapped to the appointment statuses already in the app).
- **Focus**: the design system's focus ring on every interactive element; hover/pressed overlays; disabled at 45%.
- **Motion**: 200ms ease-out, none under `prefers-reduced-motion`.
- **E-mails**: light theme only (mail clients), brand colours and fonts with system fallbacks.
- **No gradients, no emoji.**

## Context
- Current UI: Tailwind with `indigo-*`/`gray-*`/`bg-white` classes in ~40 views, Figtree font, `touch-target` utility in `resources/css/layout.css`.
- Contrast: every text pair named in the design tokens holds 4.5:1 in both themes; the remap must not introduce failing pairs.
