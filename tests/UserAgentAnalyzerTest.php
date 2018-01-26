<?php

namespace Code16\Metrics\Tests;

use Carbon\Carbon;
use Code16\Metrics\TimeInterval;
use Code16\Metrics\Metric;
use Illuminate\Support\Collection;
use Code16\Metrics\Analyzers\UrlAnalyzer;
use Code16\Metrics\Analyzers\UserAgentAnalyzer;

class UserAgentAnalyzerTest extends MetricTestCase
{
    /** @test */
    public function test_user_agent_analyzer()
    {
        $analyzer = new UserAgentAnalyzer();
        $visits = $this->generateVisits(100);
        $result = $analyzer->compile($visits, $this->getDummyTimeInterval());
        $this->assertEquals(100, array_sum($result['categories'])); 
    }

    /** @test */
    public function test_user_agent_consolider()
    {
        $analyzer = new UserAgentAnalyzer();
        $visits = $this->generateVisits(50);
        $resultA = [UserAgentAnalyzer::class => $analyzer->compile($visits, $this->getDummyTimeInterval())];
        $visits = $this->generateVisits(50);
        $resultB = [UserAgentAnalyzer::class => $analyzer->compile($visits, $this->getDummyTimeInterval())];
        $metrics = new Collection([
            $this->generateMetric($resultA, 50),
            $this->generateMetric($resultB, 50),
        ]);
        $result = $analyzer->consolidate($metrics, $this->getDummyTimeInterval());
        $this->assertEquals(100, array_sum($result['categories']));
    }

    /** @test */
    public function test_add_array_values_method()
    {
        $analyzer = new UserAgentAnalyzer();
        $a = [
            'foo' => [
                "val1" => 2,
                "val2" => 3,
            ],
            'bar' => [
                "val3" => 1,
            ]
        ];
        $b = [
            'foo' => [
                "val1" => 2,
                "val4" => 3,
            ],
            'bar' => [
            ]
        ];

        $c = $analyzer->addArrayValues($a,$b);
        $this->assertEquals([
            'foo' => [
                "val1" => 4,
                "val2" => 3,
                "val4" => 3,
            ],
            'bar' => [
                "val3" => 1,
            ]
        ], $c);
    }

    /** @test */
    public function add_array_preserve_keys_if_no_values()
    {
        $analyzer = new UserAgentAnalyzer();
        $a = [
            'foo' => [
            ],
            'bar' => [
            ]
        ];
        $b = [
            'foo' => [
            ],
            'bar' => [
            ]
        ];
        $c = $analyzer->addArrayValues($a,$b);
        $this->assertEquals($b,$c);
    }

    protected function getDummyTimeInterval()
    {
        return new TimeInterval(Carbon::now(), Carbon::now());
    }

    /**
     * Generate Metric Object
     * 
     * @param  array  $results
     * @return Metric
     */
    protected function generateMetric(array $results, $count)
    {
        return Metric::create(
            $this->getDummyTimeInterval(),
            $results,
            $count
        );
    }

}
