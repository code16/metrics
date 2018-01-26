<?php

namespace Code16\Metrics\Tests;

use Code16\Metrics\Tests\Stubs\AcmeAction;

class HelperTest extends MetricTestCase {

    /** @test */
    public function we_can_call_metrics_function()
    {
        $metrics = metrics();
        $this->assertInstanceOf(\Code16\Metrics\Manager::class, $metrics);
    }

    /** @test */
    public function we_can_call_action_function()
    {
        metrics_action(new AcmeAction('test'));
        $this->assertTrue(true);
    }

    /** @test */
    public function we_can_call_anonymous_function()
    {
        $this->assertNull(metrics_is_anonymous());
    }    
}
