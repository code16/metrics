<?php

namespace Code16\Metrics\Tests;

use Carbon\Carbon;
use Code16\Metrics\Analyzers\UniqueVisitorAnalyzer;
use Code16\Metrics\Manager;
use Code16\Metrics\Metric;
use Code16\Metrics\Repositories\Eloquent\VisitModel;
use Code16\Metrics\Repositories\MetricRepository;
use Code16\Metrics\TimeInterval;
use Illuminate\Support\Collection;

class UpdaterTest extends MetricTestCase 
{
    protected $updater;

    protected $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->updater = $this->app->make(Manager::class)->getUpdater();
        $this->repository = $this->app->make(MetricRepository::class);
    }

    /** @test */
    public function we_have_correct_start_and_end_periods()
    {
        $this->createVisits(1000, '-1 year');
        $expectedStart = Carbon::now()->subYear()->startOfYear();
        $result = $this->updater->getPeriodStart();
        $this->assertEquals($expectedStart->timestamp, $result->timestamp);

        $expectedEnd = Carbon::now()->subHour()->minute(59)->second(59);
        $result = $this->updater->getPeriodEnd();
        $this->assertEquals($expectedEnd->timestamp, $result->timestamp);
    } 

    /** @test */
    public function we_can_get_complete_periods()
    {
        $referenceStart = Carbon::create(2014,1,1,0,0,0);
        $referenceEnd = Carbon::create(2016,2,3,5,59,59);

        $periods = $this->updater->getCompletePeriods($referenceStart, $referenceEnd);

        // -> 2014,2015, Jan 2016, 1st February, 2nd February, 3rd Feb. 0h, 1h, 2h, 3h, 4h
        $this->assertHasCompletePeriods($periods, 2, 1, 2, 5);

        $referenceStart = Carbon::create(2014,1,1,0,0,0);
        $referenceEnd = Carbon::create(2014,2,3,5,59,59);

        $periods = $this->updater->getCompletePeriods($referenceStart, $referenceEnd);
        $this->assertHasCompletePeriods($periods, 0, 1, 2, 5);

    }


    /**
     * Assert the collection has the expected content of TimeIntervals
     * 
     * @param  integer $years 
     * @param  integer $months
     * @param  integer $days 
     * @param  integer $hours
     * @return void
     */
    protected function assertHasCompletePeriods(Collection $intervals, $years, $months, $days, $hours)
    {
        $this->assertCount($years, $intervals->filter(function($item) {
            return $item->type() == Metric::YEARLY;
        }));
        $this->assertCount($months, $intervals->filter(function($item) {
            return $item->type() == Metric::MONTHLY;
        }));
        $this->assertCount($days, $intervals->filter(function($item) {
            return $item->type() == Metric::DAILY;
        }));
        $this->assertCount($hours, $intervals->filter(function($item) {
            return $item->type() == Metric::HOURLY;
        }));
    }

    /** @test */
    public function we_can_parse_for_missing_metrics()
    {
        $referenceStart = Carbon::create(2016,1,1,0,0,0);
        $referenceEnd = Carbon::create(2016,1,1,23,59,59);
        $this->createVisitsInEveryTimeInterval(new TimeInterval($referenceStart, $referenceEnd, Metric::DAILY), 1);
        $periods = $this->updater->getCompletePeriods($referenceStart, $referenceEnd);
        $missing = $this->updater->parseForMissingMetrics($periods);
        $this->assertCount(23, $missing);
    }
   
    /** @test */
    public function all_metrics_are_created_for_last_day_if_we_have_visits_in_every_hours()
    {
        Carbon::setTestNow(Carbon::createFromDate(2019,9,28));
        $interval = $this->getLastDay();
        $this->createVisitsInEveryTimeInterval($interval, 1);
        $this->updater->update();
        $this->assertCount(25, $this->repository->all());
    }

    /** @test */
    public function all_metrics_are_created_for_last_month_if_we_have_visits_in_every_hours()
    {
        $interval = $this->getLastMonth();
        $dayCount = count($interval->toDays());
        
        // Create 2 visits, each hour
        $this->createVisitsInEveryTimeInterval($interval, 2);
        $this->updater->update();

        
        $date = Carbon::now();
        $month = $date->format('m'); 

        // if current month is january, metrics will auto calculate yearly metrics, which
        // will result on an additionnal metric 
        $metricCount = $month == "01"
            ? $dayCount * 24 + $dayCount + 2
            : $dayCount * 24 + $dayCount + 1;

        $expectedPageView = count($interval->toHours()) * 2;
        $monthlyMetric = $this->repository->find($interval);

        $this->assertNotNull($monthlyMetric);
        $this->assertEquals($expectedPageView, $monthlyMetric->getCount());
        $this->assertCount($metricCount, $this->repository->all());
    }

    /** @test */
    public function all_metrics_are_created_for_last_year_if_we_have_visits_in_every_hours()
    {
        $interval = $this->getLastYear();

        $dayCount = count($interval->toDays());
        $hourCount = count($interval->toHours());

        $this->createVisitsInEveryTimeInterval($interval, 1);
        $this->updater->update();

        $metricCount =  $hourCount + $dayCount + 12 + 1;

        $expectedPageView = count($interval->toHours());
        $yearlyMetric = $this->repository->find($interval);
        $this->assertNotNull($yearlyMetric);
        $this->assertCount($metricCount, $this->repository->all());
    }

    /** @test */
    public function we_can_update_metrics_without_any_visit()
    {
        $this->updater->update();
        $this->assertTrue(true);
    }

    /** @test */
    public function we_get_exact_visit_count()
    {
        $start = Carbon::create(2016,1,1,0,0,0);
        $end = Carbon::create(2016,1,1,23,59,59);
        $this->createVisitsByDate(50, $start, $end);
        $this->updater->update();
        $metrics = $this->app->make(MetricRepository::class);
        $period = new TimeInterval($start, $end, Metric::DAILY);
        $metric = $metrics->find($period);
        $this->assertEquals(50, $metric->getCount());
    }
    
    /** @test */
    public function we_dont_create_metrics_for_periods_with_no_visits()
    {
        $start = Carbon::create(2016,1,2,0,0,0);
        $end = Carbon::create(2016,1,2,23,59,59);
        $this->createVisitsByDate(50, $start, $end);
        $this->updater->update();
        $start = Carbon::create(2016,1,1,0,0,0);
        $end = Carbon::create(2016,1,1,23,59,59);
        $period = new TimeInterval($start, $end, Metric::DAILY);
        $metrics = $this->app->make(MetricRepository::class);
        $metric = $metrics->find($period);
        $this->assertNull($metric);
    }

    /** @test */
    public function we_create_statistics_for_larger_periods_when_there_is_data()
    {
        $start = Carbon::create(2016,1,2,0,0,0);
        $end = Carbon::create(2016,1,2,23,59,59);
        $this->createVisitsByDate(50, $start, $end);
        $this->updater->update();
        $start = Carbon::create(2016,1,1,0,0,0);
        $end = Carbon::create(2016,12,31,23,59,59);
        $period = new TimeInterval($start, $end, Metric::YEARLY);
        $metrics = $this->app->make(MetricRepository::class);
        $metric = $metrics->find($period);
        $this->assertNotNull($metric);
    }

    /** @test */
    public function daily_statistics_are_consolidated_correctly()
    {
        $day = $this->getLastDay();
        $this->createVisitsInEveryTimeInterval($day, 3);
        
        $count = VisitModel::count();
        $this->assertEquals(count($day->toHours()) * 3, $count);
        $this->assertVisitsAreUnique();

        $this->updater->update();
        
        $dailyMetric = $this->repository->find($day);

        $this->assertNotNull($dailyMetric);
        $this->assertEquals($count, $dailyMetric->getStatisticsByKey(UniqueVisitorAnalyzer::class)['unique-visitors']);
    }

    /** @test */
    public function monthly_statistics_are_consolidated_correctly()
    {
        $month = $this->getLastMonth();
        $this->createVisitsInEveryTimeInterval($month, 3);
        
        $count = VisitModel::count();
        $this->assertEquals(count($month->toHours()) * 3, $count);
        $this->assertVisitsAreUnique();

        $this->updater->update();
        
        $monthMetric = $this->repository->find($month);

        $this->assertNotNull($monthMetric);
        $this->assertEquals($count, $monthMetric->getStatisticsByKey(UniqueVisitorAnalyzer::class)['unique-visitors']);
    }
       
}
