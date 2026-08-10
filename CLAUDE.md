# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What it is

One shortcode and one block over one renderer. `[showhide]…[/showhide]` returns
a toggle button and a content block, with a word count folded into the button's
label; `wp-showhide/showhide` wraps its inner blocks in the same two elements. A
stylesheet, a delegated click handler, and nothing else — no admin screen, no
settings, no options, no database.

`src/` is the block source and is committed; `build/` is what `bin/build`
compiles it into, is gitignored, and is what actually ships — a checkout that
has never been built has no block, which is why the test scripts build first.

## Storage: none

Not even a version marker row: a plugin with no settings and no tables has
nothing to migrate and nothing to stamp. `uninstall.php` deletes
`wp_showhide_version` only for sites that ran an early unreleased 3.0.0 build;
nothing writes it now.

## Traps

* **The shortcode returns two sibling elements, not one wrapper.** That is why
  the `.wp-showhide` root class appears twice, on the `.sh-link` div and on the
  `.sh-content` div. It looks like a copy-paste and is the only way to scope the
  stylesheet without changing markup a theme may already style.
* **The class names, element IDs and the three `sh-link:*` DOM events are public
  API.** `sh-link:more`, `sh-link:less` and `sh-link:toggle` keep their historic
  spelling rather than taking the plugin's `wp_showhide_` prefix; the 3.0.0
  Upgrade Notice promises posts need no editing.
* **The style is enqueued unconditionally, the script only by whatever rendered
  a toggle.** Deliberate asymmetry (`class-wp-showhide.php::register_assets()`):
  the sheet must be in the head or the toggle flashes as a native button before
  CSS arrives; the script is registered in the footer and enqueued from
  `shortcode()` **and from the block's render callback**, so only pages that
  render a toggle pay for it. That second call site is the one a change is
  likely to drop, and dropping it ships perfect markup with a dead button —
  which no assertion about markup can see, so both are pinned in
  `tests/test-blocks.php` and the click is exercised in
  `tests/e2e/blocks.spec.js`.
* **The block is dynamic *and* saves content, because the shortcode encloses.**
  Its `save` returns `<InnerBlocks.Content />` — a `save` returning null would
  serialise the block self-closing and lose the children — so post_content holds
  the writer's blocks, WordPress renders them, and the render callback is handed
  the result as `$content`. Only the wrapper is decided at render time. No
  `ServerSideRender`, no wrapper element of the block's own in either half, and
  `className` support is off: there is nothing saved for a class to land on.
* **The block's boolean `hidden` becomes `yes`/`no` in one place**,
  `WP_ShowHide_Blocks::shortcode_atts()`, along with the rule that an empty
  label means the *default* label rather than an empty button — the defaults are
  translated strings, so a `block.json` cannot hold them.
* **Neither entry point may call the other.** The block must not build
  `[showhide]…[/showhide]` and hand it to `do_shortcode()`, and the shortcode
  must not be rewritten as a thin wrapper over the block. They are siblings over
  `WP_ShowHide_Template`, and each is tested with the other unregistered.
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

`bin/test.sh` runs PHPUnit, `bin/test-multisite.sh` the network pass, and
`bin/test-e2e.sh` the Playwright suite. **Run them rather than trusting a note
about their last result** — CI is the authority, and this file cannot be.

`tests/test-shortcode.php` covers attribute handling and the instance numbering;
`tests/test-escaping.php` is the regression guard for the attribute values that
reach `id`/`class`; `tests/test-blocks.php` pins that the block and the
shortcode wrap content identically and that neither needs the other;
`tests/e2e/showhide.spec.js` and `tests/e2e/blocks.spec.js` are the only places
the toggle's `aria-expanded` and event dispatch are actually exercised.

The two markup comparisons in `test-blocks.php` normalise one thing away: the
renderer numbers repeat uses of a type within a post, and rendering a block and
a shortcode is two uses, so the second gets `-2` on its ids. That is the counter
working, and a separate test asserts it does.

**Scan the plugin's own files with an allow list, not a deny list.** A metadata
test that walked everything under the plugin root and subtracted `tests/` found
two classes locally and three under CI, where `composer install` has run and
`vendor/composer/autoload_real.php` declares a `ComposerAutoloaderInit<hash>`
class inside the plugin directory. Name `includes/` plus the root entry points
instead; a deny list acquires a new member every time somebody installs
something.
