# Validation: AgentClinic Design System

The branch can merge when all of the following hold.

## Automated
- [ ] `php artisan test` passes (no behaviour change) and coverage of `app/` stays at 100%.
- [ ] `npm run build` succeeds; no remaining Figtree reference.
- [ ] A test checks every text/ground token pair named in the design system reaches 4.5:1 (3:1 for control borders and focus ring) in both themes.

## Browser (Edge via Playwright)
- [ ] Every page (guest, agent, staff, admin) and the six error pages render in the day and the night theme, EN and FR, with no console errors.
- [ ] No horizontal scroll at 320, 390, 768, 1024, 1280px; controls at least 44px below `lg`.
- [ ] Keyboard focus ring visible on links, buttons and fields.
- [ ] Status always shows its word, not colour alone.
- [ ] Screenshots reviewed against the design system (paper background, scrubs-green primary, pill buttons, serif headings).

## Hygiene
- [ ] `.env` not committed; README, CHANGELOG and roadmap updated.
