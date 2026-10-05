# Validation: Internationalization and Error Pages

The `i18n-error-pages` branch can merge when all of the following hold.

## Automated
- [ ] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [ ] `sail test` passes (PHPUnit). This is the merge gate.
- [ ] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [ ] Tests cover: the order of precedence of the language (user, cookie, `Accept-Language`, default), regional and weighted `Accept-Language` values, unsupported languages, the switcher route and its validation, saving the language on the account and at registration, Livewire requests keeping the language.

## Translations (automated)
- [ ] No missing translations: every string passed to `__()` or `trans_choice()` in `app/` and `resources/`, every permission label and group, and every built-in role name has an entry in `lang/fr.json`.
- [ ] No dead translations: every key in `lang/fr.json` is used by the code.
- [ ] Laravel's validation, authentication and password messages appear in French, and field names read naturally in both languages.
- [ ] Dates and times follow the language (English "Mon, Oct 5", French "lun. 5 oct."), on the pages and in the e-mails.
- [ ] E-mails are sent in the language of the agent's account, and in English without one, including queued reminders.

## Error pages (automated)
- [ ] 403, 404, 419, 429, 500 and 503 each render the designed page, with the right title and message in English and in French.
- [ ] The way-out buttons are right for a guest, an agent and a staff member; 419 offers a reload and a login for a guest; 429 and 503 show the wait when the response gives one; 500 shows no technical detail.
- [ ] An unknown URL gives a localized 404 page, and the maintenance page uses the browser's language.
- [ ] `/_errors/{code}` exists in `local` and `testing` and does not exist in `production`.

## Browser (automated, Chrome via Playwright)
- [ ] With a French browser language, a first visit shows the welcome and login pages in French with the switcher on "FR"; with an English browser language, in English.
- [ ] Switching language on a page returns to that page in the new language and the choice survives a reload and a new tab.
- [ ] A signed-in user's choice is saved: signing out and back in, or opening a fresh browser with the other `Accept-Language`, keeps their language.
- [ ] A French user sees French on every page they can reach (dashboard, ailments, therapies, availability, appointments, the agent pages, profile, users, roles), with French dates, validation messages and confirmation dialogs; Mailpit shows the confirmation e-mail in French.
- [ ] Every error page is reviewed in both languages (screenshots), including a real 404 and a real 403, and the way-out buttons work.
- [ ] No console errors on any page visited.

## Responsive
- [ ] The switcher and the six error pages show no horizontal scroll at 320, 390, 768, 1024 and 1280px, and controls are at least 44px below `sm`; French text, which is longer, does not break the layout of any page.

## Hygiene
- [ ] `.env` is not committed.
- [ ] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [ ] No dead code (the 100% coverage gate enforces this).
