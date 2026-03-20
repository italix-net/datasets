<?php

declare(strict_types=1);

namespace Italix\DataSets\Tests;

use Italix\DataSets\ServerSideRequest;
use PHPUnit\Framework\TestCase;

class ServerSideRequestTest extends TestCase
{
    public function test_page_defaults_to_1(): void
    {
        $req = new ServerSideRequest([]);
        $this->assertSame(1, $req->page());
    }

    public function test_page_from_params(): void
    {
        $req = new ServerSideRequest(['page' => '3']);
        $this->assertSame(3, $req->page());
    }

    public function test_page_minimum_is_1(): void
    {
        $req = new ServerSideRequest(['page' => '-5']);
        $this->assertSame(1, $req->page());
    }

    public function test_per_page_default(): void
    {
        $req = new ServerSideRequest([]);
        $this->assertSame(25, $req->per_page());
    }

    public function test_per_page_from_params(): void
    {
        $req = new ServerSideRequest(['per_page' => '50']);
        $this->assertSame(50, $req->per_page());
    }

    public function test_per_page_from_size_param(): void
    {
        $req = new ServerSideRequest(['size' => '100']);
        $this->assertSame(100, $req->per_page());
    }

    public function test_per_page_custom_default(): void
    {
        $req = new ServerSideRequest([]);
        $this->assertSame(10, $req->per_page(10));
    }

    public function test_offset(): void
    {
        $req = new ServerSideRequest(['page' => '3', 'per_page' => '20']);
        $this->assertSame(40, $req->offset());
    }

    public function test_offset_page_1(): void
    {
        $req = new ServerSideRequest(['page' => '1']);
        $this->assertSame(0, $req->offset());
    }

    public function test_sort_column_default(): void
    {
        $req = new ServerSideRequest([]);
        $this->assertNull($req->sort_column());
        $this->assertSame('name', $req->sort_column('name'));
    }

    public function test_sort_column_from_params(): void
    {
        $req = new ServerSideRequest(['sort' => 'email']);
        $this->assertSame('email', $req->sort_column());
    }

    public function test_sort_column_from_sort_column_param(): void
    {
        $req = new ServerSideRequest(['sort_column' => 'created_at']);
        $this->assertSame('created_at', $req->sort_column());
    }

    public function test_sort_direction_default(): void
    {
        $req = new ServerSideRequest([]);
        $this->assertSame('asc', $req->sort_direction());
    }

    public function test_sort_direction_from_params(): void
    {
        $req = new ServerSideRequest(['sort_dir' => 'DESC']);
        $this->assertSame('desc', $req->sort_direction());
    }

    public function test_sort_direction_invalid_falls_back(): void
    {
        $req = new ServerSideRequest(['sort_dir' => 'invalid']);
        $this->assertSame('asc', $req->sort_direction());
    }

    public function test_search_null_when_empty(): void
    {
        $req = new ServerSideRequest([]);
        $this->assertNull($req->search());
    }

    public function test_search_null_when_blank(): void
    {
        $req = new ServerSideRequest(['search' => '']);
        $this->assertNull($req->search());
    }

    public function test_search_from_params(): void
    {
        $req = new ServerSideRequest(['search' => 'john']);
        $this->assertSame('john', $req->search());
    }

    public function test_search_from_q_param(): void
    {
        $req = new ServerSideRequest(['q' => 'doe']);
        $this->assertSame('doe', $req->search());
    }

    public function test_filters(): void
    {
        $req = new ServerSideRequest([
            'filters' => ['status' => 'active', 'role' => 'admin', 'empty' => ''],
        ]);

        $filters = $req->filters();
        $this->assertSame(['status' => 'active', 'role' => 'admin'], $filters);
    }

    public function test_filter_single(): void
    {
        $req = new ServerSideRequest([
            'filters' => ['status' => 'active'],
        ]);

        $this->assertSame('active', $req->filter('status'));
        $this->assertNull($req->filter('nonexistent'));
    }

    public function test_filters_empty_when_not_array(): void
    {
        $req = new ServerSideRequest(['filters' => 'invalid']);
        $this->assertSame([], $req->filters());
    }

    public function test_get_raw_param(): void
    {
        $req = new ServerSideRequest(['custom' => 'value']);
        $this->assertSame('value', $req->get('custom'));
        $this->assertSame('fallback', $req->get('missing', 'fallback'));
    }

    public function test_all_returns_raw_params(): void
    {
        $params = ['page' => '1', 'sort' => 'name'];
        $req = new ServerSideRequest($params);
        $this->assertSame($params, $req->all());
    }

    public function test_from_array(): void
    {
        $req = ServerSideRequest::from_array(['page' => '2']);
        $this->assertSame(2, $req->page());
    }
}
