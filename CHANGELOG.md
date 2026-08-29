# Changelog — italix/datasets

## [2.1.1] — 2026-08-30

### Fixed

- A docblock example on `DataSetColumn::cell_lines()` used "buyer" as its composite-column
  illustration, a term carried over from the application this library was extracted from. Changed
  to "customer".

## [2.1.0] — 2026-08-29

### Added

- **Responsive and card layout.** `DataSetColumn::responsive()`/`card_visible()`/`card_order()`/
  `cell_lines()` and `DataSet::responsive_layout()`/`card_layout()` — narrow-viewport handling
  (hide/collapse/scroll low-priority columns, or render each row as a vertical card below a
  configurable breakpoint). Rendered by `Drivers/Tabulator/TabulatorDriver`, which also gained the
  viewport-width bootstrap script and card-mode config emission this needs.

### Fixed

- **Wrong license header**: every file carried `@license LGPL-2.1-or-later` with no MPL notice
  block — this package has been MPL-2.0 since its first declared license (below); LGPL-2.1 was
  never a deliberate choice, and the header was the only trace of it anywhere in the affected files.
- **`</script>`-breakout escaping**, `?sort_dir[]=` array-typed query params, and array-backed
  `TableMeta` support — all already shipped locally, now actually present in the published history
  (this repository had never had these commits pushed before this release; see "Reconnect" below).

### Changed

- `composer.json`'s `italix/contracts` constraint corrected from `dev-main` to `^2.0`; author,
  license, and version fields corrected to match this package's actual history.

### Reconnect note

This is the first release where the published GitHub history and this project's own accumulated
local work are the same codebase. They had diverged: GitHub was set up independently by a separate
Claude Code session (its own "Initial commit", its own `src/`/`tests/`/`examples/` layout, the wrong
license) without access to this local checkout's already-released 1.1.0–2.0.0 work. That local work
is the functional superset and is now what ships — see `[1.1.0]` through `[2.0.0]` below for the
fixes it already contains. GitHub's genuinely useful additions were kept: 13 usage examples
(`examples/`), and its PHPUnit test suite, moved to `tests/PHPUnit/` (namespace
`Italix\DataSets\Tests\PHPUnit`) so it coexists with this project's own homegrown `Italix\Testing`
suite in `tests/` without a filename collision — the two shared three names
(`DataSetTest.php`/`ServerSideRequestTest.php`/`ServerSideResponseTest.php`) with completely
different content.

One layout decision reversed from GitHub's snapshot, deliberately: classes stay **flat** at the
package root (`DataSet.php`, not `src/DataSet.php`), not GitHub's `src/`-nested PSR-4 layout. The
live application that actually consumes this library autoloads
`"Italix\\DataSets\\": "src/Libs/Italix/DataSets/"` — no `src/` subdirectory — so adopting GitHub's
layout would have broken every real consumer the moment this checkout reached them.

## [2.0.0] — 2026-08-28

### Changed — BREAKING

`DataSetColumn::h_align()` → `horizontal_align()`, `get_h_align()` → `get_horizontal_align()`.
Unlike the `_c` postfix renames elsewhere in this round, this one is a bare single-letter prefix
with no documented meaning and no `v_align` sibling anywhere in this codebase to make the
abbreviation self-evident by contrast — verified directly, nothing named `v_align` exists here.
Spelling it out costs four characters and removes the guesswork. No behavior change; verified
directly (constructed a column via reflection to bypass the unrelated `ColumnMeta` constructor
dependency, called `horizontal_align('right')`, confirmed `get_horizontal_align()` returns it and
still flows through to Tabulator's own `hozAlign` property exactly as before).

Not used anywhere in the application this library ships with today — `get_h_align()` had exactly
one caller (`Drivers\Tabulator\TabulatorDriver`, updated to match) and the setter had none — so
this is a real break for anyone else's code, but a costless one here.

## [1.2.1] — 2026-08-28

### Changed

Internal only, no public API change: `ServerSideRequest`'s private `direction_c()` renamed to
`direction_code()`, following the function-naming half of `src/Libs/Italix/CONVENTIONS.md`'s `_c`
convention update (`_c` now reserved for variables/columns; a private method has no external
caller to break). `require-dev`'s `italix/testing` and `require`'s `italix/contracts` both widened
to `^2.0` (were `^1.0`) to match that same round elsewhere.

## [1.2.0] — 2026-08-17

### Security

- **A quote in a button's tooltip, icon class or CSS class escaped the HTML attribute** it was
  written into, injecting a working event handler:

  ```
  title="a" onmouseover=alert(1) x="">X</button>
  ```

  The escaping was in place — `escape_js_attr()` — and nothing tested it, so the finding here is
  that it was one edit away from being removed with every suite still green. It is a *different*
  defence from the `</script>` one: `JSON_HEX_TAG` stops the payload closing the script element and
  does nothing about the attribute, because nothing is closed and the JSON stays valid.

  Now covered by a mutation. Disabling `escape_js_attr()` fails one assertion and only that one.

### Changed

- **A toolbar button's scope is whitelisted** against `selected`, `all`, `none`, and an unknown one
  throws. The vocabulary was written down in four docblocks and enforced nowhere; `'selection'` for
  `'selected'` is the obvious typo and produced a button the client never enables — no error, no
  log, a control that does nothing forever.

  Technically a behaviour change, and the only code it breaks is code that was already broken.

### Added

- **`ServerSideResponse` had no assertions**, and it is the half of the wire contract the client
  reads. 35 of them now: the key names it publishes, `last_page()` across nine boundaries, the
  clamping that stops a per-page of zero reaching a division, agreement with the request it was
  built from, and — the one that matters most quietly — that data which cannot be JSON-encoded
  throws instead of sending an empty body, which a table reads as "no rows".

- **Action buttons, the toolbar and trees had none either.** 43 assertions, including a breakout
  corpus of **15 entry points** that the existing one did not reach. These are the surfaces a
  translator's text flows through — a label, a tooltip, a confirmation sentence — and they take a
  different route into the page than a column label does.

  Three escaping paths, three mutations, each failing a different assertion:

  | disabling | fails |
  |---|---|
  | `JSON_HEX_TAG` | the script element can be closed |
  | `escape_js_attr()` | a handler is injected into the button and the icon |
  | `escape_js_html()` | markup in a label renders as markup |

  Plus the half a passing XSS test hides: an ampersand, an apostrophe, an accent and a `<b>` all
  arrive intact after both decoding layers a browser performs.

  Written against an HTML parser rather than `strpos()`. The payload's text appears in the markup
  either way — harmlessly quoted when the escaping worked, live when it did not — and only a parser
  tells those apart. The first version of the assertion grepped, passed, and proved nothing.

## [1.1.1] — 2026-08-17

### Fixed

- **`php: >=7.4` was not true**, because of two return types: `get_responsive_layout(): string|false`
  and `get_responsive_priority(): int|false|null`. Union types are PHP 8.0, so the whole package
  failed to parse on the version it claimed to support.

  Written back out as untyped returns with the same `@return` docblocks, rather than raising the
  floor — two accessors are not a reason to require a newer PHP of everyone who installs this.

- **Added a README.** The package had none, which for a library whose main risk is the
  `</script>` breakout in its drivers meant the one thing a consumer most needed to know before
  writing their own driver was only in the CHANGELOG.

## [1.1.0] — 2026-08-14

### Security

- **A column label containing `</script>` closed the element it was emitted into.** The driver's own
  docblock shows the output being placed inside a `<script>` block, and `json_encode()` makes a value
  safe as a JSON *string* — quotes and backslashes are escaped, so nothing can break out of the
  literal. Nothing had to: it breaks out of the **element** instead, and everything after it is
  parsed as HTML. Three of the sixteen call sites also passed `JSON_UNESCAPED_SLASHES`, which removes
  the accidental protection `<\/script>` would have given.

  Fixed with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` — and applied at **one**
  door rather than at sixteen call sites, because sixteen is a number that becomes seventeen. Values
  still decode identically in the browser; `node --check` confirms the emitted script still parses.

- **`?sort_dir[]=asc` returned a 500.** A query string can hold an array, that array reached
  `strtolower()`, and PHP threw a `TypeError` — an unauthenticated crash from a URL anybody can type.
  Non-scalars are now refused rather than cast, because casting an array yields the literal word
  `Array`, which fails later and less clearly. The same guard covers `sorts()`, which parses the
  other wire format in separate code.

### Fixed

- **`DataSet` rejected any array-backed `TableMeta`.** `describe_columns()` is declared `iterable`
  and the interface's own example returns an array, but `iterator_to_array()` did not accept arrays
  before PHP 8.2 while this library declares `php: >=7.4`. Every conforming array-backed source threw
  a `TypeError` on the first call. `Italix\Rules\Checker::keys_of()` already had the right guard;
  this is the same one.

### Added

- **The first tests this library has had**, 43 assertions across two suites — and all three items
  above were found by writing them, not by reading the code.

  - `ServerSideRequestTest` — eleven hostile page/size values asserted never to produce a negative or
    zero `LIMIT`/`OFFSET`; every sort direction normalised or refused, in both wire formats; and the
    place where the library deliberately does **not** sanitise — column names — pinned explicitly, so
    that the day somebody assumes otherwise the assumption is contradicted by a file.
  - `DataSetTest` — nine entry points into the emitted script, each asserted unable to close the
    element; the label asserted to survive escaping intact; and `node --check` on the result, skipped
    with a reason when node is absent.

Format: [Keep a Changelog](https://keepachangelog.com/). Versioning policy: `VERSIONING.md` at the
project root.


### Legal

- **Licensed under MPL-2.0**, applied 2026-08-13: the `license` field in `composer.json`, a `LICENSE`
  file, and the Exhibit A notice in every source file — MPL §1.4 defines "Covered Software" per file,
  so the per-file header is what makes the licence apply rather than decoration.

  This is a **first declaration, not a relicensing.** The package carried no licence at all before,
  which in most jurisdictions means all rights reserved: nothing had been granted, so nothing is
  taken away and no consumer's position gets worse. That is why it is recorded here rather than
  treated as a breaking change — unlike `italix/orm`, which went Apache-2.0 → MPL-2.0 and took a
  MAJOR because that direction does narrow what a consumer already had.

## [1.0.0] — baseline

Versioning starts here. This entry records the state of the library at the time the policy was
adopted, not a release.

### Contents

- **`DataSet`**, **`DataSetColumn`**, **`ActionColumn`**, **`ActionButton`**, **`Toolbar`**,
  **`ToolbarButton`** — the table described in PHP.
- **`DataTree`**, **`TreeConfig`** — hierarchical variant.
- **`ServerSideRequest`** / **`ServerSideResponse`** — the server-side pagination contract.
- **`Drivers/`** — render the client configuration; Tabulator is the shipped driver.
- `functions.php` — the `dataset()` factory (house rule 9).

### Safety position

Column names are **validated at construction**, not escaped at use (house rule 7). A name that is
not on the whitelist is refused rather than encoded, because a column name reaching SQL is not a
problem escaping can fix.

### Known compatibility notes

`$dataset_script` is markup typed as a `string`, which is why it is an allow-list entry in
`.encode-lint`. Narrowing the driver's return type to `Italix\Encode\Html` is a planned MAJOR; see
`VERSIONING.md`, house rule 15, for the deprecation path.
