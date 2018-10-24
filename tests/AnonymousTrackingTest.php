<?php

namespace Code16\Metrics\Tests;

use Code16\Metrics\Manager;
use Code16\Metrics\TimeMachine;
use Mockery;

class AnonymousTrackingTest extends MetricTestCase
{
    protected $baseUrl = '/';

    public function setUp() 
    {
        parent::setUp();

        $this->app['config']->set('app.cipher', 'AES-256-CBC');
        $this->app['config']->set('app.key', str_random(32));
    }

    /** @test */
    public function we_set_an_anonymous_cookie_if_set_anonymous_is_called_during_the_request()
    {
        $manager = $this->app->make(Manager::class);

        $router = $this->app->make('router');
        $router->get('anonymous', function(Manager $manager) {
            $manager->setAnonymous();
        });
        $response = $this->visit("anonymous");
        $this->assertTrue($manager->visit()->isAnonymous());
        $response->assertCookie($this->app['config']->get('metrics.anonymous_cookie_name'));
    }

    /** @test */
    public function we_set_an_non_anonymous_cookie_if_set_anonymous_is_called_with_falseduring_the_request()
    {
        $manager = $this->app->make(Manager::class);
        $router = $this->app->make('router');
        $router->get('anonymous', function(Manager $manager) {
            $manager->setAnonymous(false);
        });
        $response = $this->visit("/anonymous");
        $this->assertFalse($manager->visit()->isAnonymous());
        $response->asserTCookie($this->app['config']->get('metrics.cookie_name'));
    }

    /** @test */
    public function calling_set_anonymous_with_a_non_anonymous_cookie_value_will_change_it()
    {
        $manager = $this->app->make(Manager::class);

        $router = $this->app->make('router');
        $router->get('anonymous', function(Manager $manager) {
            $manager->setAnonymous();
        });
        $cookieName = $this->app['config']->get('metrics.cookie_name');
        $cookies = [
            $cookieName => str_random(32),
        ];
        $result = $this->call('GET', '/anonymous', [], $cookies);
        $this->assertTrue($manager->visit()->isAnonymous());
        $this->assertNotEquals($manager->visit()->getCookie(), $cookies[$cookieName]);
        $result->assertCookie($this->app['config']->get('metrics.anonymous_cookie_name'));
        
        $this->dontSeeInDatabase('metric_visits', [
            'cookie' => $cookies[$cookieName],
        ]);
        $this->seeInDatabase('metric_visits', [
            'cookie' => $manager->visit()->getCookie(),
        ]);
    }

    /** @test */
    public function calling_set_anonymous_false_with_an_anonymous_cookie_value_will_change_it()
    {
        $manager = $this->app->make(Manager::class);

        $router = $this->app->make('router');
        $router->get('anonymous', function(Manager $manager) {
            $manager->setAnonymous(false);
        });
        $cookieName = $this->app['config']->get('metrics.anonymous_cookie_name');
        $cookies = [
            $cookieName => str_random(32),
        ];
        $result = $this->call('GET', 'anonymous', [], $cookies);
        $this->assertFalse($manager->visit()->isAnonymous());
        $this->assertNotEquals($manager->visit()->getCookie(), $cookies[$cookieName]);
        $result->assertCookie($this->app['config']->get('metrics.cookie_name'));
        $this->dontSeeInDatabase('metric_visits', [
            'cookie' => $cookies[$cookieName],
        ]);
        $this->seeInDatabase('metric_visits', [
            'cookie' => $manager->visit()->getCookie(),
        ]);
    }

    /** @test */
    public function anonymously_login_the_user_don_t_store_user_id()
    {   
        $user = $this->createTestUser();
        $timeMachine = Mockery::mock(TimeMachine::class);
        $this->app->bind(TimeMachine::class, function ($app) use ($timeMachine) {
            return $timeMachine;
        });

        $data = [
            'email' => 'test@example.net',
            'password' => 'test',
        ];
        $anonymousCookieName = $this->app['config']->get('metrics.anonymous_cookie_name');

        $cookies = [
            $anonymousCookieName => str_random(32),
        ];

        $result = $this->call('POST', 'auth', $data, $cookies);
        $manager = $this->app->make(Manager::class);

        $this->seeInDatabase('metric_visits', [
            "cookie" =>  $cookies[$anonymousCookieName],
            "user_id" => null,
            "anonymous" => true,
        ]);
    }


}
