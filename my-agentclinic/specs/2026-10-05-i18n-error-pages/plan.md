# Plan: Internationalization and Error Pages

Vertical slices, each with its tests. Run `sail test` after every slice.

## 1. Language detection, switcher and preference
1. Migration: `users.locale` nullable; add to the `User` fillable list.
2. `config/app.php`: `supported_locales` (`en`, `fr`); a small `App\Support\Locales` class for the list, the default and "is this supported".
3. `SetLocale` middleware, appended to the `web` group: user preference, then cookie, then `Accept-Language`, then English; sets the app and Carbon locale and marks the request as handled.
4. `LocaleController` and the route `GET /locale/{locale}`: validates the language, sets the one-year cookie, saves `users.locale` for a signed-in user, redirects back (or home).
5. `Route::fallback` so unknown URLs run the web middleware and a 404 is localized.
6. `<x-locale-switcher>` Blade component (EN | FR, current one marked, 44px targets), placed in the navigation menus, the guest layout and the welcome page.
7. Registration stores the current language on the new account.
8. Tests: every step of the precedence, `Accept-Language` quality values and regional codes (`fr-CA`, `fr;q=0.9,en;q=0.8`), unsupported languages, the cookie, the saved preference on the account, the switcher route (valid, invalid, guest, signed in, redirect back), Livewire requests keep the language, registration saves it.

## 2. Framework messages
1. Publish the English language files; write `lang/fr/{auth,validation,passwords,pagination}.php`.
2. Friendly `attributes` for every field name the app validates, in English and French.
3. Tests: a French validation error, a French login failure and throttle message, a French password-reset message, and the friendly attribute names in both languages.

## 3. The interface
1. Wrap every remaining hard-coded English string in `__()` (views, components, Volt pages, PHP, the permission labels and groups, status names, built-in role names, page titles, placeholders, `aria` labels).
2. Write `lang/fr.json` with a French entry for every string; use `trans_choice` for plurals.
3. The Carbon macro `localized()` and the `dates.*` formats in both languages; replace every `format('D, M j')`-style call.
4. The "no missing translations" test (every used string has a French entry, no unused French entry), a spot check of each page in French, and a test that dates follow the language.

## 4. E-mails
1. Send the confirmation and the reminder with `->locale()` set to the agent's account language, or English without an account.
2. Translate the two e-mail views and their subjects.
3. Tests: sent in French for a French account, in English otherwise, queued reminders keep the agent's language, rendered content in both languages.

## 5. Error pages
1. A shared `errors/layout.blade.php` and the views `403`, `404`, `419`, `429`, `500`, `503`, with witty English and French copy.
2. The way-out buttons by profile; the retry hint on 429 and 503; the language handling for pages that run outside the web group (503).
3. The `/_errors/{code}` gallery in `local` and `testing`.
4. Tests: each status renders the right page and message in English and French; the buttons for a guest, an agent and a staff member; the 404 for an unknown URL is localized; 419, 429 and 503 hints; the gallery exists only in non-production.

## 6. Docs and verification
1. README (languages, how detection works, adding a language, the error pages), `CHANGELOG.md`, `specs/roadmap.md`.
2. `sail test` and `sail composer test:coverage` (100%).
3. Browser walkthrough in Chrome with a French and an English browser language, the switcher, and every error page; the responsive check (see `validation.md`).
4. Complete `validation.md` and merge into `main`.
