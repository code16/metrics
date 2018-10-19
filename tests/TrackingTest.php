<?php

namespace Code16\Metrics\Tests;

use Code16\Metrics\Repositories\VisitRepository;
use Illuminate\Support\Facades\Route;
use Mockery;
use Code16\Metrics\Tests\Stubs\AcmeAction;
use Code16\Metrics\Tests\Stubs\AcmeProvider;
use Code16\Metrics\Manager;
use Code16\Metrics\Visit;
use Code16\Metrics\Repositories\Eloquent\VisitModel;
use Illuminate\Auth\Events\Login;
use Code16\Metrics\TimeMachine;

class TrackingTest extends MetricTestCase
{
    protected $baseUrl = '/';

    public function setUp() 
    {
        parent::setUp();
        $this->app['config']->set('app.cipher', 'AES-256-CBC');
        $this->app['config']->set('app.key', str_random(32));
    }

    public function test_has_cookie_helper()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $this->visit("");
        $this->assertFalse(metrics_has_cookie());
    }

    /** @test */
    public function we_dont_log_a_visit_if_defaults_config_is_to_false()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $response = $this->visit("");
        $manager = $this->app->make(Manager::class);
        $visit = $manager->visit();
        $this->assertFalse($manager->isRequestTracked());
        $this->dontSeeCookie($this->app['config']->get('metrics.cookie_name'), $response);
        $this->dontSeeInDatabase('metric_visits', [
            'cookie' => $visit->getCookie(),
        ]);
    }

    /** @test */
    public function we_log_a_visit_if_tracking_is_manually_during_request()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $router = $this->app->make('router');
        $router->get('log', function(Manager $manager) {
            $manager->setTrackingOn();
        });
        $response = $this->visit('/log');
        $manager = $this->app->make(Manager::class);
        $visit = $manager->visit();
        $this->assertNotNull($visit);
        $this->assertTrue($manager->isRequestTracked());
        $response->assertCookie($this->app['config']->get('metrics.cookie_name'));
        $this->seeInDatabase('metric_visits', [
            "cookie" => $visit->getCookie(),
        ]);
    }

    /** @test */
    public function we_log_a_visitor_who_already_have_a_metric_cookie()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $cookieName = $this->app['config']->get('metrics.cookie_name');
        $cookies = [
            $cookieName => str_random(32),
        ];
        $result = $this->call('GET', '/', [], $cookies);
        $manager = $this->app->make(Manager::class);
        $visit = $manager->visit();
        $this->seeInDatabase('metric_visits', [
            "cookie" => $visit->getCookie(),
        ]);
        $result->assertCookie($this->app['config']->get('metrics.cookie_name'));
    }

    /** @test */
    public function we_log_a_visitor_who_already_have_an_anonymous_cookie()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $cookieName = $this->app['config']->get('metrics.anonymous_cookie_name');
        $cookies = [
            $cookieName => str_random(32),
        ];
        $result = $this->call('GET', '/', [], $cookies);
        $manager = $this->app->make(Manager::class);
        $visit = $manager->visit();
        $this->seeInDatabase('metric_visits', [
            "cookie" => $visit->getCookie(),
        ]);
        $result->assertCookie($this->app['config']->get('metrics.anonymous_cookie_name'));
    }

    /** @test */
    public function we_log_a_visit_if_defaults_config_is_to_true()
    {
        $this->app['config']->set('metrics.auto_place_cookie', true);
        $response = $this->visit("");
        $manager = $this->app->make(Manager::class);
        $this->assertInstanceOf(Visit::class, $manager->visit());
        $response->assertCookie($this->app['config']->get('metrics.cookie_name'));
    }

    /** @test */
    public function we_can_attach_an_action()
    {
        // Just to make sure Visit object is instantiated
        $this->visit("");
        $manager = $this->app->make(Manager::class);
        $manager->action(new AcmeAction('test'));
        $this->assertCount(1, $manager->visit()->actions());
    }

    /** @test */
    public function we_can_add_custom_data_providers_as_closure()
    {
        $this->visit("");
        $manager = $this->app->make(Manager::class);
        $manager->addDataProvider(function($visit) {
            $visit->setCustomValue('test', 'test');
        });
        $manager->processDataProviders();
        $this->assertTrue($manager->visit()->hasCustomValue('test'));
    }

    /** @test */
    public function we_can_add_custom_data_providers_as_class()
    {
        $this->visit("");
        $manager = $this->app->make(Manager::class);
        $manager->addDataProvider(AcmeProvider::class);
        $manager->processDataProviders();
        $this->assertTrue($manager->visit()->hasCustomValue('test'));
    }

    /** @test */
    public function calling_tracking_methods_wont_fail_if_tracking_is_off()
    {
        $manager = $this->app->make(Manager::class);
        $manager->setTrackingOff();
        $manager->action(new AcmeAction('test'));
        $manager->markPreviousUserVisits(1);
        $this->assertTrue(true);
    }

    /** @test */
    public function login_the_user_triggers_the_time_machine()
    {   
        $user = $this->createTestUser();
        $timeMachine = Mockery::mock(TimeMachine::class);
        $timeMachine->shouldReceive('updatePreviousSessions')->andReturn(true);
        $timeMachine->shouldReceive('setCurrentVisit');
        $timeMachine->shouldReceive('lookup')->once()->with($user->id);
        $this->app->bind(TimeMachine::class, function ($app) use ($timeMachine) {
            return $timeMachine;
        });

        $data = [
            'email' => 'test@example.net',
            'password' => 'test',
        ];
        //$this->expectsEvents(Login::class);
        $result = $this->post('auth', $data);
        $result->assertStatus(200);
    }

    /** @test */
    public function we_dont_track_the_user_when_do_not_track_header_is_set()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $headers=['HTTP_DNT' => 1];
        $result = $this->get('/', $headers);
        $this->assertEquals(0, VisitModel::count());
    }

    /** @test */
    public function time_machine_wont_break_when_do_not_track_header_is_set()
    {
        $this->app['config']->set('metrics.auto_place_cookie', false);
        $headers=['HTTP_DNT' => 1];
        $user = $this->createTestUser();
        $data = [
            'email' => 'test@example.net',
            'password' => 'test',
        ];
        $result = $this->post('auth', $data, $headers);
        $this->assertEquals(0, VisitModel::count());
    }

    /** @test */
    public function it_filters_urls_in_config()
    {
        $this->app['config']->set('metrics.filtered_urls', [
            'admin*',
            'backend/somepage',
        ]);
        
        $router = $this->app->make('router');
        $router->get('admin', function() {
            return response();
        });
        
        $router->get('admin/articles', function() {
            return response();
        });

        // Query url and sub url and check it's not logged
        $result = $this->get('/admin/asokoas/askosa');
        $this->assertEquals(0, VisitModel::count());

        $result = $this->get('/admin');
        $this->assertEquals(0, VisitModel::count());

        $result = $this->get('backend/somepage');
        $this->assertEquals(0, VisitModel::count());

        $result = $this->get('backend/somepage/subpage');
        $this->assertEquals(1, VisitModel::count());
    }

    /** @test */
    public function we_can_track_request_that_send_json_responses()
    {
        $router = $this->app->make('router');
        $router->get('json', function() {
            return response()->json(['test' => 'test']);
        });
        $result = $this->get('/json');
        $result->assertStatus(200);
        $this->assertEquals(1, VisitModel::count());
    }

    /** @test */
    public function time_machine_update_previous_session_id_on_login()
    {
        $this->visit("");
        $visit = VisitModel::first();
        $cookie = $visit->cookie;
        $this->createVisits(20, '-2 hours', [
            'cookie' => $cookie,
            'session_id' => '1234',
        ]);
        $user = $this->createTestUser();
        $data = [
            'email' => 'test@example.net',
            'password' => 'test',
        ];
        $cookieName = $this->app['config']->get('metrics.cookie_name');
        $headers = [
            $cookieName => $cookie,
        ];
        $result = $this->post('auth', $data, $headers);

        $lifeTime = config('session.lifetime');

        $count = VisitModel::whereSessionId("1234")->count();
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function time_machine_dont_fails_if_no_previous_visists_on_login()
    {
        $user = $this->createTestUser();
        $data = [
            'email' => 'test@example.net',
            'password' => 'test',
        ];
        $result = $this->post('auth', $data);

        $this->assertEquals(1, VisitModel::count());
    }

    /** @test */
    public function we_can_add_custom_filters()
    {
        $manager = $this->app->make(Manager::class);
        $manager->addFilter(function ($visit) {
            return $visit->getStatusCode() != '200';
        });

        $router = $this->app->make('router');
        $router->get('some_url', function() {
            return 'ok';
        });

        // Query an url that will trigger a 404
        $this->get('/some_non_existing_url');
        $this->assertEquals(0, VisitModel::count());

        // Query an actual URL
        $this->get('/some_url');
        $this->assertEquals(1, VisitModel::count());
    }

    /** @test */
    public function it_adds_utm_fields_to_custom_fields_if_set()
    {
        Route::get('/', [
            'as' => 'home',
            'uses' => function() {
                return 'ok';
            }
        ]);

        $utmFields = [
            "utm_source" => "source",
            "utm_content" => "content",
            "utm_medium" => "medium",
            "utm_campaign" => "campaign",
            "utm_term" => "term"
        ];

        $result = $this->get(route("home", $utmFields));
        $result->assertStatus(200);

        $visit = app(VisitRepository::class)->first();

        foreach($utmFields as $utm => $value) {
            $this->assertEquals($value, $visit->getCustomValue($utm));
        }

        $result->assertSessionHas("metrics.utm_fields", $utmFields);
    }
}
