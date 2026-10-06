# Validation: Internationalization and Error Pages

The `i18n-error-pages` branch can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [x] `sail test` passes (PHPUnit). This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: the order of precedence of the language (user, cookie, `Accept-Language`, default), regional and weighted `Accept-Language` values, unsupported languages, the switcher route and its validation, saving the language on the account and at registration, Livewire requests keeping the language.

## Translations (automated)
- [x] No missing translations: every string passed to `__()` or `trans_choice()` in `app/` and `resources/`, every permission label and group, and every built-in role name has an entry in `lang/fr.json`.
- [x] No dead translations: every key in `lang/fr.json` is used by the code.
- [x] Laravel's validation, authentication and password messages appear in French, and field names read naturally in both languages.
- [x] Dates and times follow the language (English "Mon, Oct 5", French "lun. 5 oct."), on the pages and in the e-mails.
- [x] E-mails are sent in the language of the agent's account, and in English without one, including queued reminders.

## Error pages (automated)
- [x] 403, 404, 419, 429, 500 and 503 each render the designed page, with the right title and message in English and in French.
- [x] The way-out buttons are right for a guest, an agent and a staff member; 419 offers a reload and a login for a guest; 429 and 503 show the wait when the response gives one; 500 shows no technical detail.
- [x] An unknown URL gives a localized 404 page, and the maintenance page uses the browser's language.
- [x] `/_errors/{code}` shows the page in `local` and `testing` and answers like an unknown URL (404) in `production`.

## Browser (automated, Microsoft Edge via Playwright, headless: 182/182 checks)
- [x] With a French browser language, a first visit shows the welcome and login pages in French with the switcher on "FR"; with an English browser language, in English.
- [x] Switching language on a page returns to that page in the new language and the choice survives a reload and a new tab.
- [x] A signed-in user's choice is saved: signing out and back in, or opening a fresh browser with the other `Accept-Language`, keeps their language.
- [x] A French user sees French on every page they can reach (dashboard, ailments, therapies, availability, appointments, the agent pages, profile, users, roles), with French dates, validation messages and confirmation dialogs; Mailpit shows the confirmation e-mail in French.
- [x] Every error page is reviewed in both languages (screenshots), including a real 404 and a real 403, and the way-out buttons work.
- [x] No console errors on any page visited.

## Responsive
- [x] The switcher and the six error pages show no horizontal scroll at 320, 390, 768, 1024 and 1280px, and controls are at least 44px below `sm`; French text, which is longer, does not break the layout of any page.

## Hygiene
- [x] `.env` is not committed.
- [x] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [x] No dead code (the 100% coverage gate enforces this).

## Results
- PHPUnit: 493 tests, 2825 assertions, 100% line coverage of `app/`.
- Edge walkthrough (`i18n-walk.js`): 182/182 checks, including French dates, validation, e-mail in Mailpit, six error pages in both languages, switcher persistence, responsive widths 320 to 1280px.
- Known: the account's saved language beats the browser's (an agent saved as EN sees English on a French browser, by design). The full suite takes about 14 minutes.
