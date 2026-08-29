<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets — action buttons, the toolbar, and trees
 *
 * Four classes with no assertions between them, and the reason to start here
 * rather than anywhere else in the package: **their text is the text a
 * translator writes.** A column label is usually a noun somebody typed once; a
 * button carries a label, a tooltip and a confirmation sentence, and those come
 * out of a message catalogue that grows for the life of the application.
 *
 * They also take a different route into the page than the column labels the
 * existing corpus covers. `DataSet`'s escaping was fixed "at one door" —
 * `json_encode()` with `JSON_HEX_TAG` — but the button markup is assembled as a
 * string first, through `escape_js_attr()` and `escape_js_html()`, and only
 * then handed to that door. Three paths, four call sites, one of which could
 * lose its escaping without any of the others noticing.
 *
 * Run: php src/Libs/Italix/DataSets/tests/ActionsTest.php
 */

declare(strict_types=1);

(static function (): void {
    foreach ([
        __DIR__ . '/../vendor/autoload.php',               // checked out on its own
        __DIR__ . '/../../../../../vendor/autoload.php',   // vendored in a project
        __DIR__ . '/../../../../vendor/autoload.php',      // installed as a package
        __DIR__ . '/../../../autoload.php',                // sibling autoloader
    ] as $autoload) {
        if (is_file($autoload)) {
            require_once $autoload;

            return;
        }
    }

    fwrite(STDERR, "Could not find an autoloader. Run composer install.\n");
    exit(2);
})();

use Italix\Contracts\ColumnMeta;
use Italix\Contracts\TableMeta;
use Italix\DataSets\ActionButton;
use Italix\DataSets\DataSet;
use Italix\DataSets\DataTree;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

use function Italix\Testing\{suite, section, test, summary};

suite('Italix DataSets — actions, toolbar, trees');

/**
 * The smallest thing that satisfies the source contract.
 *
 * Written out rather than reached for from `italix/orm`, because a fixture is
 * fifteen lines and a dependency from this package's tests to that one is a
 * dependency in the direction nobody notices until the package is extracted.
 */
final class ActionsColumn implements ColumnMeta
{
    /** @var string */
    private $name_c;

    public function __construct(string $name_c)
    {
        $this->name_c = $name_c;
    }

    public function get_name(): string { return $this->name_c; }
    public function get_type(): string { return 'VARCHAR'; }
    public function is_nullable(): bool { return $this->name_c !== 'id'; }
    public function is_primary_key(): bool { return $this->name_c === 'id'; }
    public function get_length(): ?int { return 200; }
    public function get_default() { return null; }
    public function has_default(): bool { return false; }
}

final class ActionsTable implements TableMeta
{
    /** @var array<string, ColumnMeta> */
    private $columns = [];

    /** @param string[] $names */
    public function __construct(array $names)
    {
        foreach ($names as $name_c) {
            $this->columns[$name_c] = new ActionsColumn($name_c);
        }
    }

    public function describe_columns(): iterable { return $this->columns; }
    public function describe_column(string $name): ?ColumnMeta { return $this->columns[$name] ?? null; }
}

/** @return array{0: bool, 1: string} */
$throws = static function (callable $fn): array {
    try {
        $fn();

        return [false, ''];
    } catch (\Throwable $e) {
        return [true, $e->getMessage()];
    }
};

$dataset = static function (): DataSet {
    return (new DataSet(new ActionsTable(['id', 'title'])))
        ->columns(['id', 'title'])
        ->id('t')
        ->ajax_url('/data.json');
};

$driver = new TabulatorDriver();

// -----------------------------------------------------------------------------
section('a button is configured and described');

$set    = $dataset();
$column = $set->action_column();
$button = $column->button('edit', 'Edit');

test('action_column() reports itself present', $set->has_action_column());
test('…and is the same object on a second call', $set->action_column() === $column);
test('button() returns something configurable', $button instanceof ActionButton);
test('the button is registered on the column', $column->get_buttons() === [$button]);

$button->css_class('btn btn-sm')->icon('pencil')->title('Edit this row')->confirm('Sure?')
       ->extra(['data-turbo' => 'false']);

test('the fluent calls all came back to the button',
    $button->get_css_class() === 'btn btn-sm'
    && $button->get_icon() === 'pencil'
    && $button->get_title() === 'Edit this row'
    && $button->get_confirm() === 'Sure?'
    && $button->get_extra() === ['data-turbo' => 'false']);

$described = $button->to_array();

test('to_array() carries the name, which is what the click handler matches on',
    ($described['name'] ?? null) === 'edit', json_encode($described));
test('…and the label', ($described['label'] ?? null) === 'Edit');
test('…and the confirmation, which is the one with consequences',
    ($described['confirm'] ?? null) === 'Sure?');

$bare = (new ActionButton('delete', 'Delete'))->to_array();

test('an unconfigured button describes no confirmation',
    ($bare['confirm'] ?? null) === null, json_encode($bare));

// -----------------------------------------------------------------------------
section('the column around them');

$column->label('')->width('130px')->position('end')->frozen()->css_class('actions');

test('the column reports its own configuration',
    $column->get_width() === '130px'
    && $column->get_position() === 'end'
    && $column->is_frozen()
    && $column->get_css_class() === 'actions');

$column_array = $column->to_array();

test('to_array() lists every button', count($column_array['buttons'] ?? []) === 1,
    json_encode(array_keys($column_array)));

$column->button('delete', 'Delete');

test('a second button is appended, not replaced', count($column->get_buttons()) === 2);
test('…and both survive to_array()', count($column->to_array()['buttons']) === 2);

// -----------------------------------------------------------------------------
section('the toolbar');

$set     = $dataset();
$toolbar = $set->toolbar();

// `has_toolbar()` means "there is a toolbar worth rendering", not "toolbar()
// was called" — an empty one draws an empty bar. `has_action_column()` reads
// the same way, and the pair is pinned here because the asymmetry with the
// accessor is the kind of thing a later refactor helpfully "fixes".
test('an empty toolbar is not a toolbar', !$set->has_toolbar());
test('…and neither is an empty action column', !$dataset()->action_column() || !$dataset()->has_toolbar());
test('toolbar() returns the same object on a second call', $set->toolbar() === $toolbar);

$new    = $toolbar->button('new', 'New');
$delete = $toolbar->button('bulk_delete', 'Delete selected', 'selected');

test('one button is enough to make it a toolbar', $set->has_toolbar());
test('a toolbar button defaults to needing no selection', $new->get_scope() === 'none');
test('…and one can be declared to need it', $delete->get_scope() === 'selected');

test('THE TOOLBAR KNOWS IT NEEDS A SELECTION',
    $toolbar->requires_selection(),
    'a button scoped to the selection is present and the toolbar denies it — the client would '
    . 'enable it with nothing selected');

$empty_toolbar = $dataset()->toolbar();
$empty_toolbar->button('new', 'New');

test('…and says so only when one actually does', !$empty_toolbar->requires_selection());

test('to_array() lists the buttons with their scope',
    ($toolbar->to_array()['buttons'][1]['scope'] ?? null) === 'selected',
    json_encode($toolbar->to_array()));

// The vocabulary is three words and used to be enforced nowhere. `'selection'`
// for `'selected'` is the obvious typo, and it produced a button the client
// never enables — no error, no log, just a control that does nothing forever.
[$threw, $message] = $throws(static function () use ($dataset): void {
    $dataset()->toolbar()->button('bulk', 'Bulk', 'selection');
});

test('A SCOPE OUTSIDE THE VOCABULARY IS REFUSED', $threw,
    'accepted silently; the button would never enable and nothing would say why');
test('…and the message lists what is allowed',
    strpos($message, 'selected') !== false && strpos($message, 'none') !== false, $message);

foreach (['selected', 'all', 'none'] as $scope_c) {
    [$threw] = $throws(static function () use ($dataset, $scope_c): void {
        $dataset()->toolbar()->button('b', 'B', $scope_c);
    });

    test("\"{$scope_c}\" is accepted", !$threw);
}

// -----------------------------------------------------------------------------
section('trees');

$tree = new DataTree(new ActionsTable(['id', 'title', 'parent_id']));

test('a tree says it is one', $tree->is_tree());
test('a flat dataset says it is not', !$dataset()->is_tree());

$config = $tree->tree_config();
$config->parent_column('parent_id')->id_column('id')->start_open()->max_depth(4)
       ->toggle_column('title')->show_toggle();

test('the configuration reads back', $config->get_parent_column() === 'parent_id'
    && $config->get_id_column() === 'id'
    && $config->is_start_open()
    && $config->get_max_depth() === 4
    && $config->get_toggle_column() === 'title'
    && $config->is_show_toggle());

test('to_array() describes it', ($config->to_array()['parent_column'] ?? null) === 'parent_id',
    json_encode($config->to_array()));

test('max_depth accepts null, meaning no limit', $config->max_depth(null)->get_max_depth() === null);

// -----------------------------------------------------------------------------
section('NONE OF THIS TEXT MAY CLOSE THE SCRIPT ELEMENT');

// The existing corpus covers nine entry points, all of them on the dataset or a
// column. These are the ones that were not covered, and they are the ones a
// translator's text flows through.
$breakout_c = '</script><img src=x onerror=alert(1)>';

$entry_points = [
    'action column label' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->label($breakout_c)->button('edit', 'Edit');

        return $set;
    },
    'action column css class' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->css_class($breakout_c)->button('edit', 'Edit');

        return $set;
    },
    'button label' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('edit', $breakout_c);

        return $set;
    },
    'button name' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button($breakout_c, 'Edit');

        return $set;
    },
    'button css class' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('edit', 'Edit')->css_class($breakout_c);

        return $set;
    },
    'button icon' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('edit', 'Edit')->icon($breakout_c);

        return $set;
    },
    'button title' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('edit', 'Edit')->title($breakout_c);

        return $set;
    },
    'button confirmation' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('delete', 'Delete')->confirm($breakout_c);

        return $set;
    },
    'button extra options' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('edit', 'Edit')->extra(['note' => $breakout_c]);

        return $set;
    },
    'toolbar button label' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->toolbar()->button('new', $breakout_c);

        return $set;
    },
    'toolbar button name' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->toolbar()->button($breakout_c, 'New');

        return $set;
    },
    'toolbar css class' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->toolbar()->css_class($breakout_c)->button('new', 'New');

        return $set;
    },
    'row click callback' => static fn (): DataSet => $dataset()->on('edit', $breakout_c),
    'card layout title field' => static fn (): DataSet => $dataset()->card_layout($breakout_c, 480),
    'height' => static fn (): DataSet => $dataset()->css_class('x')->height($breakout_c),
];

$leaks = [];

foreach ($entry_points as $where_c => $build) {
    $script = $driver->render_script($build(), '#t');

    if (strpos($script, '</script>') !== false) {
        $leaks[] = $where_c;
    }
}

test('NOT ONE OF ' . count($entry_points) . ' ENTRY POINTS CAN CLOSE THE ELEMENT',
    $leaks === [],
    $leaks === [] ? '' : 'closes it via: ' . implode(', ', $leaks));

// The tree config takes the same treatment.
$tree_script = (static function () use ($breakout_c, $driver): string {
    $tree = new DataTree(new ActionsTable(['id', 'title', 'parent_id']));
    $tree->columns(['id', 'title'])->id('t')->ajax_url('/data.json');
    $tree->tree_config()->parent_column($breakout_c)->toggle_column($breakout_c);

    return $driver->render_script($tree, '#t');
})();

test('…nor can the tree configuration', strpos($tree_script, '</script>') === false);

// -----------------------------------------------------------------------------
section('NOR MAY IT ESCAPE THE ATTRIBUTE IT IS WRITTEN INTO');

// A second, separate defence, and the one the section above does not cover.
//
// The button markup is assembled as an HTML string before it is JSON-encoded,
// so a title containing a double quote closes `title="` and everything after
// it becomes attributes of the button. `JSON_HEX_TAG` does not help: nothing
// closes the script element, the JSON is valid, and the payload arrives as a
// working event handler.
//
// Measured, with `escape_js_attr()` disabled:
//   title="a" onmouseover=alert(1) x="">X</button>
$fragment = static function (DataSet $set) use ($driver): string {
    $script = $driver->render_script($set, '#t');

    if (preg_match('/_cardActionHtml\s*=\s*("(?:[^"\\\\]|\\\\.)*")/', $script, $m) !== 1) {
        return '';
    }

    return (string) json_decode($m[1]);
};

$attribute_payload_c = 'a" onmouseover=alert(1) x="';

$escapes = [
    'the tooltip' => static function () use ($dataset, $attribute_payload_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('del', 'X')->title($attribute_payload_c);

        return $set;
    },
    'the icon class' => static function () use ($dataset, $attribute_payload_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('del', 'X')->icon($attribute_payload_c);

        return $set;
    },
    'the css class' => static function () use ($dataset, $attribute_payload_c): DataSet {
        $set = $dataset();
        $set->action_column()->button('del', 'X')->css_class($attribute_payload_c);

        return $set;
    },
];

/**
 * The attributes a browser actually sees, as `element/attribute` pairs.
 *
 * Parsed rather than grepped, because the payload's text appears in the markup
 * either way — harmlessly inside a quoted value when the escaping worked, and
 * as a live handler when it did not. `strpos()` cannot tell those apart; an
 * HTML parser is the only thing that can, and it is the same parser the attack
 * would be relying on.
 *
 * @return string[]
 */
$attributes_of_button = static function (string $html): array {
    if ($html === '') {
        return [];
    }

    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    // Every element, not only the button. An injection through the icon class
    // lands on the `<i>` inside it, and a handler there is just as live —
    // looking only at the button missed exactly that case.
    $names = [];

    foreach ((new DOMXPath($doc))->query('//body//*') as $element) {
        foreach ($element->attributes as $attribute) {
            $names[] = $element->nodeName . '/' . $attribute->nodeName;
        }
    }

    return $names;
};

$escaped_out = [];

foreach ($escapes as $where_c => $build) {
    $attributes = $attributes_of_button($fragment($build()));

    foreach ($attributes as $name_c) {
        if (strpos((string) strstr($name_c, '/'), '/on') === 0) {
            $escaped_out[] = $where_c . ' (' . $name_c . ')';
        }
    }
}

test('A QUOTE CANNOT OPEN A NEW ATTRIBUTE ON THE BUTTON',
    $escaped_out === [],
    $escaped_out === [] ? '' : 'injected a handler via: ' . implode(', ', $escaped_out));

// The payload is still *in* the markup — as the text of the title, which is
// exactly right. Asserting that too, so a future "fix" that strips it instead
// of escaping it does not pass by deleting the evidence.
$titled = $dataset();
$titled->action_column()->button('del', 'X')->title($attribute_payload_c);

test('…and the text itself is kept, not stripped',
    strpos($fragment($titled), 'onmouseover') !== false,
    'the escaping deleted the value instead of quoting it');

// And markup in a label must arrive as text. The fragment is assigned as HTML,
// so an unescaped `<b>` is not a rendering nicety — it is arbitrary markup in
// a row the operator is about to click.
$set = $dataset();
$set->action_column()->button('del', '<img src=x onerror=alert(1)>');

test('…and markup in a label is text, not markup',
    strpos($fragment($set), '<img') === false,
    $fragment($set));

// -----------------------------------------------------------------------------
section('and the escaping must not destroy the text');

// The other half, and the one a passing XSS test hides: an escape that strips
// characters passes every assertion above and quietly breaks every label with
// an ampersand or an apostrophe in it. Both are ordinary in Italian and French.
$set = $dataset();
$set->action_column()->label('Azioni & altro')
    ->button('delete', "Elimina l'anagrafica")
    ->title('Rimuove <b>tutto</b>')
    ->confirm("Sei sicuro? L'operazione è definitiva & non reversibile.");

$script = $driver->render_script($set, '#t');

/**
 * The text as the browser finally has it.
 *
 * Two layers, and they are not the same on every path. Values in the
 * configuration object are JSON-escaped once (`&` becomes `\u0026`). Values
 * that end up inside the button markup go through `htmlspecialchars()` first
 * and are then JSON-escaped, so `<` arrives as `\u0026lt;` — correct, because
 * that fragment is assigned as HTML and the entity is what renders the literal
 * character.
 *
 * Undoing both is what a reader of the page effectively does, and it is the
 * only way to ask "did the escaping preserve the text" rather than "did the
 * escaping happen".
 */
$as_the_browser_sees_it = static function (string $script): string {
    $out = '';

    // Every double-quoted JSON string in the emitted script.
    if (preg_match_all('/"(?:[^"\\\\]|\\\\.)*"/', $script, $matches) === 0) {
        return $script;
    }

    foreach ($matches[0] as $literal) {
        $decoded = json_decode($literal);

        if (is_string($decoded)) {
            $out .= html_entity_decode($decoded, ENT_QUOTES, 'UTF-8') . "\n";
        }
    }

    return $out;
};

$readable = $as_the_browser_sees_it($script);

foreach ([
    'an ampersand in the column label'  => 'Azioni & altro',
    'an apostrophe in the button label' => "Elimina l'anagrafica",
    'markup in the tooltip'             => 'Rimuove <b>tutto</b>',
    'both in the confirmation'          => "L'operazione è definitiva & non reversibile.",
] as $label => $needle) {
    test("{$label} survives intact", strpos($readable, $needle) !== false, $needle);
}

// And the accent, which a wrong charset argument to htmlspecialchars() eats
// silently — leaving an empty string where a sentence was.
test('an accented character is not swallowed', strpos($readable, 'è definitiva') !== false);

test('…and the result is still parseable JavaScript',
    (static function () use ($script): bool {
        $node = trim((string) shell_exec('command -v node 2>/dev/null'));

        if ($node === '') {
            return true;   // reported below
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ix-ds-') . '.js';
        file_put_contents($tmp, preg_replace('#</?script[^>]*>#', '', $script));
        exec(escapeshellarg($node) . ' --check ' . escapeshellarg($tmp) . ' 2>&1', $out, $code);
        @unlink($tmp);

        return $code === 0;
    })(),
    'node --check rejected the emitted script');

if (trim((string) shell_exec('command -v node 2>/dev/null')) === '') {
    echo "  (node absent — the JavaScript is not parsed, only inspected)\n";
}

exit(summary());
