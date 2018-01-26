<?php

namespace Code16\Metrics\Tests;

use Illuminate\Support\Collection;
use Code16\Metrics\Metric;
use Code16\Metrics\TimeInterval;
use Code16\Metrics\Repositories\VisitRepository;
use Code16\Metrics\Analyzers\UniqueVisitorAnalyzer;

class UniqueVisitorAnalyzerTest extends MetricTestCase
{
    protected $visits;

    public function setUp()
    {
        parent::setUp();
        $this->visits = $this->app->make(VisitRepository::class);
    }

    /** @test */
    public function test_unique_visitor_analyzer()
    {
        $analyzer = new UniqueVisitorAnalyzer();
        $interval = $this->getLastDay();
        $this->createVisitsInEveryTimeInterval($interval, 5);
        $this->assertVisitsAreUnique();
        $visits = $this->visits->getByTimeInterval($interval);
        $count = count($interval->toHours()) * 5;
        $this->assertEquals($count, count($visits));
        $result = $analyzer->compile($visits, $interval);
        $this->assertEquals($count, $result["unique-visitors"]);
    }

    /** @test */
    public function test_unique_visitor_consolider()
    {
        $analyzer = new UniqueVisitorAnalyzer();
        $interval = $this->getLastMonth();
        // Generate a collection of metrics from visits and an analyzer
        $metrics = $this->createMetrics($analyzer, $interval, 10000);
        $stats = $analyzer->consolidate($metrics, $interval);
        $this->assertEquals(10000, $stats["unique-visitors"]);

        // We'll add an empty record to the collection to ensure it works correctly
        $metrics->push(Metric::create($interval, [], 0));
        $stats = $analyzer->consolidate($metrics, $interval);
        $this->assertEquals(10000, $stats["unique-visitors"]);
    }

}
