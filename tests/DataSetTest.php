<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets — the configuration, and the JavaScript it writes
 *
 * A DataSet does two things that can go wrong quietly. It decides which columns
 * exist, which is the whitelist every caller leans on when it builds an
 * `ORDER BY`; and it emits a block of JavaScript that the application drops
 * inside a `<script>` element — the library's own docblock shows exactly that
 * usage.
 *
 * The second one is where the interesting failure lives. `json_encode()` makes
 * a value safe *as a JSON string*: quotes and backslashes are escaped, so
 * nothing can break out of the literal. But a value never had to break out of
 * the literal — it breaks out of the **element**. A label containing
 * `</script>` closes the tag, and everything after it is parsed as HTML.
 *
 * `JSON_HEX_TAG` is what prevents that, and three of the sixteen call sites in
 * the driver passed `JSON_UNESCAPED_SLASHES`, which removes the accidental
 * protection `<\/script>` would otherwise have given.
 *
 * Run: php src/Libs/Italix/DataSets/tests/DataSetTest.php
 */

declare(strict_types=1);

(static function (): void {
    foreach ([
        __DIR__ . '/../../../../../vendor/autoload.php',
        __DIR__ . '/../../../../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../vendor/autoload.php',
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
use Italix\DataSets\DataSet;
use Italix\DataSets\Drivers\Tabulator\TabulatorDriver;

use function Italix\Testing\{suite, section, test, summary};

/**
 * A column, and a table, written here rather than borrowed.
 *
 * `Italix\Forms` ships a perfectly good array adapter, and using it would make
 * this library's tests depend on that one — against house rule 13, in the
 * direction nobody notices until the package is extracted. A fixture is nine
 * lines.
 */
final class TestColumn implements ColumnMeta
{
    private string $name_c;
    private string $type_c;

    public function __construct(string $name_c, string $type_c = 'VARCHAR')
    {
        $this->name_c = $name_c;
        $this->type_c = $type_c;
    }

    public function get_name(): string { return $this->name_c; }
    public function get_type(): string { return $this->type_c; }
    public function is_nullable(): bool { return $this->name_c !== 'id'; }
    public function is_primary_key(): bool { return $this->name_c === 'id'; }
    public function get_length(): ?int { return $this->type_c === 'VARCHAR' ? 200 : null; }
    public function get_default() { return null; }
    public function has_default(): bool { return false; }
}

/** A source that hands back a plain **array**, which the contract permits. */
final class ArrayTable implements TableMeta
{
    /** @var array<string, ColumnMeta> */
    private array $columns = [];

    public function __construct(array $names)
    {
        foreach ($names as $name_c) {
            $this->columns[$name_c] = new TestColumn($name_c);
        }
    }

    public function describe_columns(): iterable { return $this->columns; }
    public function describe_column(string $name): ?ColumnMeta { return $this->columns[$name] ?? null; }
}

/** The same source, handing back a **Generator** instead. */
final class GeneratorTable implements TableMeta
{
    private array $columns = [];

    public function __construct(array $names)
    {
        foreach ($names as $name_c) {
            $this->columns[$name_c] = new TestColumn($name_c);
        }
    }

    public function describe_columns(): iterable
    {
        foreach ($this->columns as $name_c => $column) {
            yield $name_c => $column;
        }
    }

    public function describe_column(string $name): ?ColumnMeta { return $this->columns[$name] ?? null; }
}

suite('Italix DataSets — configuration and emitted JavaScript');

$table   = static fn (): TableMeta => new ArrayTable(['id', 'title', 'status']);
$dataset = static fn (): DataSet => new DataSet($table());

// -----------------------------------------------------------------------------
section('an iterable source is an iterable source, array or not');

// `TableMeta::describe_columns()` is declared `iterable`, and the interface's
// own example returns an array. `iterator_to_array()` did not accept arrays
// until PHP 8.2 while this library declares `php: >=7.4`, so every array-backed
// source threw a TypeError on every supported version below that.
//
// Found by writing this file: the fixture above is an ordinary implementation
// of the published contract, and it crashed on the first call.
$both_flag = true;

foreach ([new ArrayTable(['id', 'title']), new GeneratorTable(['id', 'title'])] as $source) {
    try {
        $columns = (new DataSet($source))->get_visible_columns();

        if ($columns !== ['id', 'title']) {
            $both_flag = false;
        }
    } catch (\Throwable $e) {
        $both_flag = false;
    }
}

test('AN ARRAY-BACKED SOURCE WORKS, AND SO DOES A GENERATOR-BACKED ONE',
    $both_flag,
    'the contract says iterable; only one of the two shapes used to work');

// -----------------------------------------------------------------------------
section('the column whitelist is the one every caller leans on');

test('a configured column is accepted', $dataset()->column('title')->get_name() === 'title');

$refuses = static function (callable $call): bool {
    try {
        $call();

        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    }
};

test('a column the source does not have is refused',
    $refuses(static fn () => $dataset()->column('no_such_column')),
    'this list is what an ORDER BY is checked against; silently accepting a name defeats that');
test('…and so is a hostile one', $refuses(static fn () => $dataset()->column('id; DROP TABLE users --')));
test('columns() refuses the whole set if any member is unknown',
    $refuses(static fn () => $dataset()->columns(['title', 'nope'])),
    'a partially applied whitelist is the worst of the three outcomes');
test('visible columns default to every source column',
    $dataset()->get_visible_columns() === ['id', 'title', 'status']);
test('…and narrow to what columns() was given',
    $dataset()->columns(['title'])->get_visible_columns() === ['title']);

// -----------------------------------------------------------------------------
section('the emitted script: nothing may close the element it lives in');

$driver = new TabulatorDriver();

$breakout_c = '</script><img src=x onerror=alert(1)>';

$entry_points = [
    'column label' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->column('title')->label($breakout_c);

        return $set;
    },
    'ajax url' => static fn (): DataSet => $dataset()->ajax_url('/x?' . $breakout_c),
    'search placeholder' => static fn (): DataSet => $dataset()->global_search(true, $breakout_c),
    'dataset id' => static fn (): DataSet => $dataset()->id($breakout_c),
    'css class' => static fn (): DataSet => $dataset()->css_class($breakout_c),
    'ajax params' => static fn (): DataSet => $dataset()->ajax_params(['note' => $breakout_c]),
    'column css class' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->column('title')->css_class($breakout_c);

        return $set;
    },
    'column formatter params' => static function () use ($dataset, $breakout_c): DataSet {
        $set = $dataset();
        $set->column('title')->formatter('html', ['prefix' => $breakout_c]);

        return $set;
    },
    'extra options' => static fn (): DataSet => $dataset()->extra(['note' => $breakout_c]),
];

$leaks = [];

foreach ($entry_points as $where_c => $build) {
    $script = $driver->render_script($build()->ajax_url('/data.json'), '#table');

    if (strpos($script, '</script>') !== false) {
        $leaks[] = $where_c;
    }
}

test('NO ENTRY POINT CAN CLOSE THE SCRIPT TAG',
    $leaks === [],
    $leaks === [] ? '' : 'closes the element via: ' . implode(', ', $leaks));

// The other half: the value must still arrive intact. An escape that mangles
// the text passes the test above and breaks every label with a `<` in it.
$set = $dataset();
$set->column('title')->label('Profit < loss & "quoted"');

$script = $driver->render_script($set->ajax_url('/data.json'), '#table');

// Anchored on the *pair*: the first `"title":` in the document belongs to the
// `id` column's header, not to the column called `title`. The first version of
// this assertion read that one and reported "Id".
preg_match('/"title":\s*("(?:[^"\\\\]|\\\\.)*")\s*,\s*"field":\s*"title"/', $script, $found);

test('the label survives the escaping intact',
    isset($found[1]) && json_decode($found[1]) === 'Profit < loss & "quoted"',
    'decoded: ' . var_export(isset($found[1]) ? json_decode($found[1]) : null, true));

// -----------------------------------------------------------------------------
section('and the script is still JavaScript afterwards');

// Escaping that produces a syntax error is a different outage from the one it
// prevents. Node is the only honest judge of that, and its absence is a skip
// with a reason rather than a silent pass.
$node_c = trim((string) @shell_exec('command -v node 2>/dev/null'));

if ($node_c === '') {
    echo "  SKIPPED — node is not installed, so the emitted script cannot be parsed.\n";
} else {
    $set = $dataset();
    $set->column('title')->label($breakout_c);
    $set->column('status')->label('Ünïcodé — ' . $breakout_c);

    $script_c = tempnam(sys_get_temp_dir(), 'ix_ds_') . '.js';
    file_put_contents($script_c, $driver->render_script($set->ajax_url('/data.json'), '#table'));

    $status_n = 0;
    @exec(escapeshellcmd($node_c) . ' --check ' . escapeshellarg($script_c) . ' 2>&1', $output, $status_n);

    test('node parses the emitted script', $status_n === 0, implode(' ', $output));

    unlink($script_c);
}

// -----------------------------------------------------------------------------
section('the rest of the configuration survives a round trip');

$set = $dataset()
    ->ajax_url('/documents/data.json')
    ->per_page(50)
    ->default_sort('title', 'desc');

test('the ajax url is kept', $set->get_ajax_url() === '/documents/data.json');
test('the default sort column is kept', $set->get_default_sort_column() === 'title');
test('…and its direction', $set->get_default_sort_direction() === 'desc');
test('the emitted script names the endpoint',
    strpos($driver->render_script($set, '#table'), '/documents/data.json') !== false);
test('…and the selector it binds to', strpos($driver->render_script($set, '#table'), '#table') !== false);

exit(summary());
