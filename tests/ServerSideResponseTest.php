<?php
/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */
/**
 * Italix DataSets — the answer half of the wire contract
 *
 * `ServerSideRequest` has a suite because hostile input arrives there. This is
 * the other end of the same conversation and had none, which is the more
 * ordinary kind of risk: nothing here is attacked, it is simply read by a
 * JavaScript table that will not say what it expected.
 *
 * The keys of `to_array()` are a published interface — the client reads
 * `last_page` to decide whether to draw a "next" button. Rename one and the
 * table silently shows one page of a thousand rows, with no error anywhere.
 *
 * Run: php src/Libs/Italix/DataSets/tests/ServerSideResponseTest.php
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

use Italix\DataSets\ServerSideRequest;
use Italix\DataSets\ServerSideResponse;

use function Italix\Testing\{suite, section, test, summary};

suite('Italix DataSets — ServerSideResponse');

/** @return array{0: bool, 1: string} */
$throws = static function (callable $fn): array {
    try {
        $fn();

        return [false, ''];
    } catch (\Throwable $e) {
        return [true, get_class($e)];
    }
};

$rows = [['id' => 1, 'title' => 'a'], ['id' => 2, 'title' => 'b']];

// -----------------------------------------------------------------------------
section('the shape the client reads');

$array = (new ServerSideResponse($rows, 57, 2, 25))->to_array();

test('every key the client depends on is present',
    array_keys($array) === ['data', 'last_page', 'page', 'per_page', 'total'],
    implode(', ', array_keys($array)));

test('data is the rows, untouched', $array['data'] === $rows);
test('total is the count before pagination, not after', $array['total'] === 57);
test('page is what was asked for', $array['page'] === 2);
test('per_page is what was asked for', $array['per_page'] === 25);

// -----------------------------------------------------------------------------
section('last_page, where the arithmetic is');

// The number the client uses to decide whether there is a next page. Off by one
// in either direction and the table either hides rows or offers an empty page.
$cases = [
    // total, per_page, expected
    [0,   25, 1],   // no rows at all is still one page
    [1,   25, 1],
    [25,  25, 1],   // exactly full
    [26,  25, 2],   // one over
    [50,  25, 2],
    [51,  25, 3],
    [100, 10, 10],
    [101, 10, 11],
    [7,    3, 3],
];

foreach ($cases as [$total_n, $per_page_n, $expected_n]) {
    $got_n = (new ServerSideResponse([], $total_n, 1, $per_page_n))->last_page();

    test("{$total_n} rows at {$per_page_n} per page is {$expected_n} page(s)",
        $got_n === $expected_n, "got {$got_n}");
}

// -----------------------------------------------------------------------------
section('values that would divide by zero, or go backwards');

// `per_page` reaches a division. `ServerSideRequest` clamps what it parses, but
// nothing stops a caller constructing this directly with whatever it computed.
[$threw] = $throws(static function (): void {
    (new ServerSideResponse([], 10, 1, 0))->last_page();
});

test('A PER-PAGE OF ZERO DOES NOT DIVIDE BY ZERO', !$threw);
test('…and is clamped to one rather than guessed at',
    (new ServerSideResponse([], 10, 1, 0))->get_per_page() === 1);

[$threw] = $throws(static function (): void {
    (new ServerSideResponse([], 10, 1, -25))->last_page();
});

test('a negative per-page does not divide by zero either', !$threw);
test('…and is also clamped to one', (new ServerSideResponse([], 10, 1, -25))->get_per_page() === 1);

test('page 0 becomes page 1', (new ServerSideResponse([], 10, 0, 25))->get_page() === 1);
test('a negative page becomes page 1', (new ServerSideResponse([], 10, -3, 25))->get_page() === 1);

test('a negative total still reports at least one page',
    (new ServerSideResponse([], -5, 1, 25))->last_page() === 1);

// -----------------------------------------------------------------------------
section('built from the request it is answering');

// The pairing that matters: whatever the request settled on after clamping is
// what the response must report back, or the client paginates against numbers
// the server did not use.
$request = ServerSideRequest::from_array(['page' => '3', 'size' => '10']);
$response = ServerSideResponse::from_request($rows, 95, $request);

test('page comes from the request', $response->get_page() === $request->page(),
    $response->get_page() . ' vs ' . $request->page());
test('per_page comes from the request', $response->get_per_page() === $request->per_page(),
    $response->get_per_page() . ' vs ' . $request->per_page());
test('…and last_page agrees with them', $response->last_page() === 10,
    (string) $response->last_page());

// A request whose paging was hostile: the response must inherit the *clamped*
// values, not the raw ones, or the two ends disagree about what page 1 is.
$hostile = ServerSideRequest::from_array(['page' => '-4', 'size' => '0']);
$answer  = ServerSideResponse::from_request([], 10, $hostile);

test('a clamped request produces a coherent response',
    $answer->get_page() >= 1 && $answer->get_per_page() >= 1 && $answer->last_page() >= 1,
    json_encode([$answer->get_page(), $answer->get_per_page(), $answer->last_page()]));

// -----------------------------------------------------------------------------
section('JSON');

$json = (new ServerSideResponse($rows, 2, 1, 25))->to_json();

test('it is valid JSON', json_decode($json, true) !== null, substr($json, 0, 120));
test('…and decodes to what to_array() said',
    json_decode($json, true) === (new ServerSideResponse($rows, 2, 1, 25))->to_array());

test('an empty result set is an empty array, not an object',
    strpos((new ServerSideResponse([], 0, 1, 25))->to_json(), '"data":[]') !== false,
    (new ServerSideResponse([], 0, 1, 25))->to_json());

// UTF-8 out of a database is the ordinary case, not the exotic one.
$unicode = (new ServerSideResponse([['title' => 'città — naïve — 日本']], 1, 1, 25))->to_json();

test('unicode survives the round trip',
    json_decode($unicode, true)['data'][0]['title'] === 'città — naïve — 日本');

// Invalid UTF-8 reaches here from a column somebody stored bytes in. Returning
// `false` from json_encode() and printing it would send an empty body with a
// 200, which the client reads as "no rows" — a silent, permanent empty table.
[$threw, $class_c] = $throws(static function (): void {
    (new ServerSideResponse([['title' => "\xB1\x31"]], 1, 1, 25))->to_json();
});

test('DATA THAT CANNOT BE ENCODED THROWS rather than sending an empty body',
    $threw, 'json_encode() returned false and it was sent as-is');
test('…as a RuntimeException, which a controller can catch',
    $class_c === 'RuntimeException', $class_c);

test('flags are passed through to json_encode',
    strpos((new ServerSideResponse([['t' => 'a/b']], 1))->to_json(JSON_UNESCAPED_SLASHES), 'a/b') !== false);

// -----------------------------------------------------------------------------
section('the accessors');

$response = new ServerSideResponse($rows, 57, 2, 25);

test('get_data()', $response->get_data() === $rows);
test('get_total()', $response->get_total() === 57);
test('the defaults are page 1 at 25', (new ServerSideResponse([], 0))->get_page() === 1
    && (new ServerSideResponse([], 0))->get_per_page() === 25);

exit(summary());
