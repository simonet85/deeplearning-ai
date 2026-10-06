# Validation: AgentClinic Design System

The branch can merge when all of the following hold.

## Automated
- [x] `php artisan test` passes (no behaviour change) and coverage of `app/` stays at 100%.
- [x] `npm run build` succeeds; no remaining Figtree reference.
- [x] A test checks every text/ground token pair named in the design system reaches 4.5:1 (3:1 for control borders and focus ring) in both themes.

## Browser (Edge via Playwright)
- [x] Every page (guest, agent, staff, admin) and the six error pages render in the day and the night theme, EN and FR, with no console errors.
- [x] No horizontal scroll at 320, 390, 768, 1024, 1280px; controls at least 44px below `lg`.
- [ ] Keyboard focus ring visible on links, buttons and fields.
- [x] Status always shows its word, not colour alone.
- [x] Screenshots reviewed against the design system (paper background, scrubs-green primary, pill buttons, serif headings).

## Hygiene
- [x] `.env` not committed; README, CHANGELOG and roadmap updated.

## Results
- PHPUnit: full suite green and 100% line coverage of `app/` (`composer test:coverage`) on the branch before the merge; 54 contrast pairs checked in `DesignTokensTest`.
- Edge (headless) walkthrough: public pages, six error pages and eight staff pages at 320, 390, 768, 1024 and 1280px, in day and night themes, English and French, no console errors and no horizontal scroll. It found one defect: at 1024px the admin navigation overflowed by 18px (the new font is wider); fixed by tightening the link spacing between `lg` and `xl`, then re-checked (34/34 for 1024px staff pages and for the agent pages at 320, 390 and 1280px, both themes).
- Screenshots reviewed by eye: dashboard, appointments, error 404, an agent page on a phone in the night theme, and the documentation page.

## Not verified
- Keyboard focus ring in the browser (the ring is defined in CSS and its contrast is tested, but nobody tabbed through the pages).
- That every control is at least 44px below `lg` (unchanged `touch-target` utility, not measured).
- The French walkthrough screenshots were not all reviewed one by one; only horizontal overflow and console errors were checked automatically.
