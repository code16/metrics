<?php

namespace Code16\Metrics\Tests;

use Carbon\Carbon;
use Code16\Metrics\TimeInterval;
use Code16\Metrics\Repositories\Eloquent\VisitModel;
use Code16\Metrics\Visit;

class VisitRepositoryTest extends MetricTestCase
{
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $this->repository = $this->app->make(\Code16\Metrics\Repositories\VisitRepository::class);
    }

    /** @test */
    public function we_can_store_a_single_visit()
    {
        $visit = $this->makeVisit();
        $this->repository->store($visit);
        $this->seeInDatabase('metric_visits', ['ip' => $visit->getIp() ]);
    }

    /** @test */
    public function we_can_get_all_visits()
    {
        $this->createVisits(100);
        $this->assertEquals(100, count($this->repository->all()));
    }

    /** @test */
    public function we_can_query_visits_in_a_time_interval()
    {
        $this->createVisits(100, '-1 hour');

        $start = Carbon::now()->subMinutes(61);
        $end = Carbon::now();

        $this->assertEquals(100, count($this->repository->getTimeInterval($start,$end)));
    }

    /** @test */
    public function we_can_query_visits_by_time_interval()
    {
        $interval = $this->getLastDay();
        $this->createVisitsInTimeInterval($interval, 100);
        $this->assertEquals(100, count($this->repository->getByTimeInterval($interval)));
    }

    /** @test */
    public function we_can_query_for_first_visit()
    {
        $this->createVisits(23, '-1 hour');
        $this->createVisits(1, '-1 hour', ['date' => Carbon::now()->subDay()->startOfDay()]);
        $visit = $this->repository->first();
        $this->assertEquals(Carbon::now()->subDay()->startOfDay(), $visit->getDate());
    }

    /** @test */
    public function we_can_query_for_last_visit()
    {
        $this->createVisits(23, '-1 hour');
        $this->createVisits(1, '-1 hour', ['date' => Carbon::now()->addDay()->endOfDay()]);
        $visit = $this->repository->last();
        $this->assertEquals(Carbon::now()->addDay()->endOfDay(), $visit->getDate());
    }

    /** @test */
    public function we_can_query_for_oldest_cookie()
    {
        $this->createVisits(1, '-1 hour');
        $visit = VisitModel::first();
        $cookie = $visit->cookie;
        $visit = $this->repository->oldestVisitForCookie($cookie);
        $this->assertInstanceOf(Visit::class, $visit);
    }
}
