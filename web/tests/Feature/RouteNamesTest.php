<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteNamesTest extends TestCase
{
    public function test_web_report_routes_point_to_the_web_pages(): void
    {
        $this->assertSame(url('/reports'), route('reports.index'));
        $this->assertSame(url('/reports/1'), route('reports.show', 1));
        $this->assertSame(url('/api/reports'), route('api.reports.index'));
        $this->assertSame(url('/api/reports/1'), route('api.reports.show', 1));
    }

    public function test_no_route_name_is_used_twice(): void
    {
        // When two routes share a name, route() silently returns only one of them.
        $names = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter();

        $this->assertSame([], $names->duplicates()->values()->all());
    }
}
