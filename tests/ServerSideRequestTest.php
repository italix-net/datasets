<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets — the parser that stands between the browser and the SQL
 *
 * `ServerSideRequest` reads a query string written by whoever is holding the
 * browser and hands back a page number, an offset, a sort direction and a list
 * of columns. Three of those four go into a statement.
 *
 * So the questions here are not "does it parse" but:
 *
 *   - can a value reach `LIMIT` or `OFFSET` that is negative, enormous, or not
 *     a number at all?
 *   - can anything other than `asc` or `desc` come out of a direction?
 *   - and where the library deliberately does **not** sanitise — the column
 *     names — is that written down, so the caller knows the whitelist is theirs?
 *
 * The last one matters most. A library that half-sanitises is worse than one
 * that does not, because the caller stops checking.
 *
 * Run: php src/Libs/Italix/DataSets/tests/ServerSideRequestTest.php
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

use Italix\DataSets\ServerSideRequest;

use function Italix\Testing\{suite, section, test, summary};

suite('Italix DataSets — the request parser');

$from = static fn (array $params): ServerSideRequest => ServerSideRequest::from_array($params);

// -----------------------------------------------------------------------------
section('it reads what Tabulator actually sends');

$request = $from(['page' => '3', 'size' => '50']);

test('the page comes back', $request->page() === 3);
test('…and the page size', $request->per_page() === 50);
test('…and the offset is derived from both', $request->offset() === 100,
    'offset: ' . $request->offset());
test('an absent page is the first one', $from([])->page() === 1);
test('an absent size falls back to the default given by the caller',
    $from([])->per_page(25) === 25);

// -----------------------------------------------------------------------------
section('nothing hostile reaches LIMIT or OFFSET');

// Each of these is a value somebody can type into a URL. None of them may come
// back as a negative number — `LIMIT -1` is a syntax error on MySQL and a full
// table scan on some others — and none may come back enormous, which is a
// denial of service written as a query string.
$hostile = [
    'negative page'    => ['page' => '-5'],
    'zero page'        => ['page' => '0'],
    'huge page'        => ['page' => '99999999999999'],
    'word page'        => ['page' => 'DROP'],
    'array page'       => ['page' => ['1']],
    'negative size'    => ['size' => '-25'],
    'zero size'        => ['size' => '0'],
    'huge size'        => ['size' => '100000000'],
    'word size'        => ['size' => 'all'],
    'float size'       => ['size' => '25.9'],
    'sql-ish size'     => ['size' => '25 UNION SELECT'],
];

$bad = [];

foreach ($hostile as $label_c => $params) {
    $request = $from($params);

    $page_n   = $request->page();
    $size_n   = $request->per_page();
    $offset_n = $request->offset();

    if ($page_n < 1) {
        $bad[] = "{$label_c}: page {$page_n}";
    }

    if ($size_n < 1) {
        $bad[] = "{$label_c}: size {$size_n}";
    }

    if ($offset_n < 0) {
        $bad[] = "{$label_c}: offset {$offset_n}";
    }
}

test('PAGE, SIZE AND OFFSET ARE NEVER NEGATIVE OR ZERO, WHATEVER IS SENT',
    $bad === [], implode('; ', $bad));

// Documented rather than asserted as a bound: this library does not cap the
// page size, and a caller that passes it straight to LIMIT has published a
// cheap way to ask for the whole table. Pinned so the behaviour is a decision.
test('the page size is NOT capped, which is the caller\'s job to know',
    $from(['size' => '100000000'])->per_page() === 100000000,
    'a cap belongs where the cost is known; what matters is that it is not silently assumed here');

// -----------------------------------------------------------------------------
section('a sort direction is asc or desc and nothing else');

$directions = [
    'desc'                  => 'desc',
    'DESC'                  => 'desc',
    'DeSc'                  => 'desc',
    'asc'                   => 'asc',
    'asc; DROP TABLE users' => 'asc',   // falls back to the default
    'desc--'                => 'asc',
    ''                      => 'asc',
    'ascending'             => 'asc',
    '0'                     => 'asc',
];

$wrong = [];

foreach ($directions as $sent_c => $expected_c) {
    $got_c = $from(['sort_dir' => $sent_c])->sort_direction('asc');

    if ($got_c !== $expected_c) {
        $wrong[] = "\"{$sent_c}\" gave \"{$got_c}\", wanted \"{$expected_c}\"";
    }
}

test('every direction is normalised or refused', $wrong === [], implode('; ', $wrong));

// Found by writing this suite, not by reading the file: `?sort_dir[]=asc` is a
// URL anybody can type, it arrived at `strtolower()` as an array, and PHP threw
// a TypeError — a 500 on demand from an unauthenticated query string.
$survives = static function (array $params): bool {
    try {
        $request = ServerSideRequest::from_array($params);
        $request->sort_direction('asc');
        $request->sorts();

        return true;
    } catch (\Throwable $e) {
        return false;
    }
};

test('AN ARRAY WHERE A DIRECTION BELONGS DOES NOT CRASH THE REQUEST',
    $survives(['sort_dir' => ['asc']])
    && $survives(['sorts' => [['field' => 't', 'dir' => ['desc']]]])
    && $survives(['sorts' => [['field' => ['x'], 'dir' => 'asc']]]),
    'a query string can hold an array, and every one of these did throw before 2026-08-14');
test('…and the array is refused rather than stringified',
    ServerSideRequest::from_array(['sort_dir' => ['desc']])->sort_direction('asc') === 'asc',
    'casting an array gives the literal word "Array", which fails later and less clearly');
test('…and a sort entry with an array field is dropped',
    ServerSideRequest::from_array(['sorts' => [['field' => ['x'], 'dir' => 'asc']]])->sorts() === []);

test('…and the same holds inside the multi-sort format',
    $from(['sorts' => [['field' => 'title', 'dir' => 'desc; DROP TABLE x']]])->sorts()[0]['direction'] === 'asc',
    'the two formats are parsed by different code and only one of them was obvious');

// -----------------------------------------------------------------------------
section('multi-sort: malformed entries are dropped, not half-read');

$sorts = $from(['sorts' => [
    ['field' => 'title', 'dir' => 'desc'],
    ['dir' => 'asc'],                        // no field at all
    'not-an-array',
    ['field' => 'created_dt'],               // no direction: gets the default
]])->sorts();

test('the well-formed entries survive', count($sorts) === 2, 'count: ' . count($sorts));
test('…in order', $sorts[0]['column'] === 'title' && $sorts[1]['column'] === 'created_dt');
test('an entry with no field is skipped rather than yielding an empty column name',
    !in_array('', array_column($sorts, 'column'), true),
    'an empty column name concatenated into ORDER BY is a syntax error at best');
test('a missing direction defaults to asc', $sorts[1]['direction'] === 'asc');
test('sort_column() reports the first of them', $from(['sorts' => [
    ['field' => 'title', 'dir' => 'desc'],
]])->sort_column() === 'title');

// -----------------------------------------------------------------------------
section('column names are NOT sanitised, and that is the contract');

// This is the sharp edge of the library and it is pinned deliberately. The
// parser cannot know which columns exist — only the DataSet does — so it hands
// back what was sent. Every caller must check the name against a whitelist
// before it reaches a statement.
//
// `App\Support\DataSetsQuery::build_order()` does exactly that, in both of its
// branches. This test exists so that the day somebody assumes otherwise, the
// assumption is contradicted by a file rather than by an incident.
$injection_c = 'id; DROP TABLE users --';

test('a hostile column name comes back untouched',
    $from(['sort' => $injection_c])->sort_column() === $injection_c,
    'the parser has no column list, so it cannot filter; the DataSet has one and must');
test('…and so does a hostile search-column list',
    $from(['search_columns' => [$injection_c]])->search_columns() === [$injection_c]);
test('…and a filter key', $from(['filters' => [$injection_c => 'x']])->filters() === [$injection_c => 'x']);

// -----------------------------------------------------------------------------
section('search and filters');

test('a search term is returned verbatim, to be bound and never concatenated',
    $from(['search' => "O'Brien"])->search() === "O'Brien");
test('an empty search is null rather than an empty string',
    $from(['search' => ''])->search() === null,
    'the difference decides whether a WHERE clause is added at all');
test('a named filter is readable on its own', $from(['filters' => ['status' => 'draft']])->filter('status') === 'draft');
test('an absent filter is null', $from([])->filter('status') === null);

// -----------------------------------------------------------------------------
section('the superglobal constructor is one of three, not the only one');

// Relevant beyond tidiness: `from_globals()` is what makes this class unusable
// under a resident worker, and it is a *named* constructor rather than the
// only one — so the library is already ready for a request object, and the
// coupling is opt-in.
test('from_array() needs no superglobals', $from(['page' => '2'])->page() === 2);
test('all() hands back what was given', $from(['a' => '1'])->all() === ['a' => '1']);
test('get() reads an arbitrary key with a default',
    $from([])->get('missing', 'fallback') === 'fallback');
test('from_globals() exists as a separate entry point',
    method_exists(ServerSideRequest::class, 'from_globals'),
    'named rather than baked in, which is what keeps $_GET out of the constructor');

exit(summary());
