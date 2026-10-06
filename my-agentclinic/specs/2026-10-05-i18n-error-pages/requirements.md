# Requirements: Internationalization (English and French) and Error Pages

## Scope
Make the whole app available in English and French, with the language chosen automatically and changeable by the user, and replace the framework's plain error screens with designed, bilingual ones.

In scope:
- **Two languages**, English (`en`, the default and fallback) and French (`fr`).
- **Automatic detection**, a **language switcher**, and a **saved preference** (cookie, and the user's account when signed in).
- **Everything translated**: every string in the interface, Laravel's validation, authentication and password messages, the e-mails, and all dates and times.
- **Designed error pages** for 403, 404, 419, 429, 500 and 503, in the clinic's colors and tone, each with a useful way out.

Out of scope: more than two languages (the structure allows them), right-to-left languages, translating data that staff or agents type or that is seeded (ailment and therapy names and descriptions, agent bios, role names the administrator creates), translating the README and the specs, per-user time zones.

## Decisions
### Language choice
- **Order of precedence** for the language of a request: the signed-in user's saved language (`users.locale`), else the `locale` cookie, else the browser's `Accept-Language` header matched against the supported languages, else English.
- **Switcher**: a small EN | FR control in the navigation (desktop and mobile menus), on the guest pages (login, register, password pages), on the welcome page and on the error pages. Choosing a language sets the cookie (one year) and, for a signed-in user, saves it in `users.locale`, then returns to the page the user was on.
- **Registration** saves the language the visitor was seeing as the new account's language.
- **Livewire**: update requests go through the same middleware, so a component's re-render stays in the user's language.
- **Unknown languages** (for example `de`) are ignored and fall back to English; a bad switcher value is rejected.

### Translating
- **Keys are the English text**, in Laravel's JSON translation files: `lang/fr.json` holds the French for every English string, and the existing `__('English text')` calls stay as they are. English needs no file.
- **Framework messages** (validation, auth, passwords, pagination) go in `lang/fr/*.php`, with friendly field names (`attributes`) in both languages so a Livewire property such as `agentId` reads "patient" instead of "agent id".
- **Hard-coded English** in views and PHP (labels, headings, buttons, titles, placeholders, status names, the permission labels and groups, the built-in role names) is wrapped in `__()` so it can be translated.
- **Placeholders and plurals** are kept (`:name`, `:count`), and plural strings use `trans_choice`.
- **Dates and times** follow the language: names of days and months come from Carbon's locale, and the order differs (English "Mon, Oct 5", French "lun. 5 oct."). One format per style lives in the translation files (`dates.short`, `dates.long`, `dates.datetime`, ...) and a Carbon macro, `->localized('short')`, applies it. Times stay on a 24-hour clock in both languages.
- **No missing translations**: a test lists every string passed to `__()` or `trans_choice()` in `app/` and `resources/`, plus the permission labels, groups and built-in role names, and fails if any lacks a French entry; it also fails if `lang/fr.json` holds a key nothing uses.

### E-mails
- A confirmation or a reminder is sent in the language of the agent's account (`users.locale`), or in English when the agent has no account or has not chosen one. The language is set on the mailable (`->locale()`), so queued reminders use it too, whatever language the staff member who booked was using.

### Error pages
- **Pages**: 403 (access denied), 404 (not found), 419 (page expired), 429 (too many requests), 500 (server error) and 503 (maintenance), as Blade views in `resources/views/errors/` sharing one layout.
- **Design**: a centered card on the app's gray background with the clinic cross, a large status code in the brand indigo, a witty title and a warm message in the product's tone, a primary button, a secondary button, the language switcher (except on 503), and the app name. Mobile-first, readable from 320px up, buttons at least 44px.
- **A way out** on every page, chosen by who is asking: a staff member (anyone who can see the dashboard) goes to the Dashboard, an agent to My appointments, a guest to the welcome page and Log in. 419 offers "Reload the page" and, for a guest, "Log in again". 429 shows how long to wait when the response says so. 500 never shows technical detail. 503 says the clinic is closed for maintenance and when to try again, when `Retry-After` is set.
- **Language on error pages**: pages that run inside the normal middleware use the usual order of precedence. Unknown URLs go through a fallback route so a 404 is localized too, and maintenance (503) is raised before sessions and cookies exist, so it uses the browser's `Accept-Language`.
- **Gallery for development**: in the `local` and `testing` environments, `/_errors/{code}` renders each page so it can be reviewed and tested in a browser (`?retry=90` adds a waiting time to 429 and 503). In any other environment the address answers like an unknown URL (the 404 page): the route is registered so that this can be tested, but it refuses to show anything.
- The raw stack trace shown by `APP_DEBUG=true` for an unhandled exception is left to Laravel; `abort()` and production use the designed pages.

### General
- **Testing**: PHPUnit per `specs/tech-stack.md`; 100% line coverage of `app/`; `RefreshDatabase` and factories.
- **Responsive**: the switcher and the error pages are mobile-first, use `.touch-target`, and are checked at 320, 390, 768, 1024 and 1280px.
- **Tone**: witty and warm in both languages (not a word-for-word translation: the French jokes are written to work in French).

## Context
- Builds on the earlier steps. Strings already go through `__()` (about 270 calls in 39 files), there is no `lang` directory yet, and `config/app.php` already reads `APP_LOCALE` and `APP_FALLBACK_LOCALE`.
- The navigation collapses to the mobile menu below 1024px, so the switcher has a place in both menus.
- Dates currently use `format('D, M j')`-style English formats in the Livewire views and the e-mails.

## Data
- `users.locale`: nullable string (5), the language the user chose (`en` or `fr`), null until chosen.

## Open Questions
- Should staff be able to set an agent's e-mail language? (Default: no; it follows the agent's own account.)
