<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\ServerSideRequest;
use Italix\DataSets\ServerSideResponse;
use PHPUnit\Framework\TestCase;

class ServerSideResponseTest extends TestCase
{
    public function test_basic_response(): void
    {
        $data = [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']];
        $response = new ServerSideResponse($data, 100, 1, 25);

        $this->assertSame($data, $response->get_data());
        $this->assertSame(100, $response->get_total());
        $this->assertSame(1, $response->get_page());
        $this->assertSame(25, $response->get_per_page());
    }

    public function test_last_page(): void
    {
        $response = new ServerSideResponse([], 100, 1, 25);
        $this->assertSame(4, $response->last_page());

        $response = new ServerSideResponse([], 101, 1, 25);
        $this->assertSame(5, $response->last_page());

        $response = new ServerSideResponse([], 0, 1, 25);
        $this->assertSame(1, $response->last_page());
    }

    public function test_to_array(): void
    {
        $data = [['id' => 1]];
        $response = new ServerSideResponse($data, 50, 2, 10);

        $arr = $response->to_array();

        $this->assertSame($data, $arr['data']);
        $this->assertSame(5, $arr['last_page']);
        $this->assertSame(2, $arr['page']);
        $this->assertSame(10, $arr['per_page']);
        $this->assertSame(50, $arr['total']);
    }

    public function test_to_json(): void
    {
        $response = new ServerSideResponse([['id' => 1]], 1, 1, 25);
        $json = $response->to_json();

        $decoded = json_decode($json, true);
        $this->assertSame([['id' => 1]], $decoded['data']);
        $this->assertSame(1, $decoded['total']);
    }

    public function test_from_request(): void
    {
        $request = ServerSideRequest::from_array(['page' => '3', 'per_page' => '20']);
        $response = ServerSideResponse::from_request([['id' => 1]], 100, $request);

        $this->assertSame(3, $response->get_page());
        $this->assertSame(20, $response->get_per_page());
        $this->assertSame(5, $response->last_page());
    }

    public function test_minimum_page_and_per_page(): void
    {
        $response = new ServerSideResponse([], 10, -1, 0);
        $this->assertSame(1, $response->get_page());
        $this->assertSame(1, $response->get_per_page());
    }
}
