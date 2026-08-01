# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

WP-ShowHide follows `_standards/STANDARDS.md` in the parent folder, which is the
contract for all nineteen plugins in the collection. Where this file and that
one disagree, that one wins.

## What it is

One shortcode. `[showhide]…[/showhide]` returns a toggle button and a content
block, with a word count folded into the button's label. A stylesheet, a
delegated click handler, and nothing else — no admin screen, no settings, no
options, no database. It is the smallest plugin in the collection at ~200 lines
of `includes/`.

## Storage: none

Not even the version row (§2.1). `uninstall.php` deletes `wp_showhide_version`
only for sites that ran an early unreleased 3.0.0 build; nothing writes it now.

## Traps

* **The shortcode returns two sibling elements, not one wrapper.** That is why
  the `.wp-showhide` root class appears twice, on the `.sh-link` div and on the
  `.sh-content` div. It looks like a copy-paste and is the only way to scope the
  stylesheet without changing markup a theme may already style.
* **The class names, element IDs and the three `sh-link:*` DOM events are public
  API.** `sh-link:more`, `sh-link:less` and `sh-link:toggle` predate the
  collection's `{{UNDER}}_` hook-prefix rule and keep their spelling; the 3.0.0
  Upgrade Notice promises posts need no editing.
* **The style is enqueued unconditionally, the script only by the shortcode.**
  Deliberate asymmetry (`class-wp-showhide.php::register_assets()`): the sheet
  must be in the head or the toggle flashes as a native button before CSS
  arrives; the script is registered in the footer and enqueued from
  `shortcode()`, so only pages that use the shortcode pay for it.
* **More/less text is interpolated with `str_replace()`, not `sprintf()`.** The
  strings are user-supplied attributes, and a stray `%d` in `sprintf()` is a
  fatal, not a typo.
* **`sanitize_type()` strips rather than escapes**, because the value lands in
  an HTML `id` *and* a class, where an escaped quote is still wrong. It returns
  the default when `preg_replace()` yields null — which it does on invalid
  UTF-8, not just on an empty result.
* **`hidden="No"` used to do the opposite of what it said.** The comparison is
  `strtolower( trim( … ) )` for that reason.
* **`$instances` numbers repeat renders of the same type within a post** so the
  document never carries a duplicate `id`; the *first* occurrence keeps the
  unsuffixed id it has always had, which is what themes and anchors target.
* `(string) $content` before the word count is not defensive padding — a
  self-closing `[showhide]` passes null, which is deprecated into a string
  parameter on PHP 8.1+.
* The click handler uses `event.target.closest?.()` because a click on a text
  node inside the button reports a non-element target in some browsers.

## Tests

`tests/test-shortcode.php` covers attribute handling and the instance
numbering; `tests/test-escaping.php` is the §7.2.4 regression guard for the
attribute values that reach `id`/`class`; `tests/e2e/showhide.spec.js` is the
only place the toggle's `aria-expanded` and event dispatch are actually
exercised.

**`tests/test-metadata.php` here is historically significant.** §7.2 says the
idea and several assertions came from this file, but that its own names and
structure lose to the standard — the file predated §7.1 and declared
`class Test_ShowHide_Metadata extends WP_UnitTestCase`. It now extends the
shared `Plugin_Metadata_TestCase` like everyone else. Do not treat it as the
reference copy.

## Known discrepancy

The README's 3.0.0 Upgrade Notice ends "The plugin now stores one row,
`wp_showhide_version`, and deletes it on uninstall." That is untrue — commit
`0d31075` ("Store nothing at all") removed it. wp-relativedate and wp-serverinfo
carry the same stale sentence.
