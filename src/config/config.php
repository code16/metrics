<?php 

return [
    
    /*
    |--------------------------------------------------------------------------
    | Custom database connection
    |--------------------------------------------------------------------------
    |
    | Use a custom database connection for metrics tables / models. If set to
    | null, will use the default laravel connection.
    |
    */
    'connection' => env('METRICS_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Enable tracking
    |--------------------------------------------------------------------------
    |
    | This option globally enable / disable tracking.
    |
    */
    'enable' => env('METRICS_TRACKING', true),

    /*
    |--------------------------------------------------------------------------
    | Auto Place Cookie
    |--------------------------------------------------------------------------
    |
    | This option will tell Metrics to automatically place cookie & log visits. 
    | This flag is useful to comply with EU legislation wich require the user's
    | consent to place a cookie on their device.
    |
    */
    'auto_place_cookie' => true,

    /*
    |--------------------------------------------------------------------------
    | Anonymous tracking
    |--------------------------------------------------------------------------
    |
    | By default, anonymous will link harversted data to a logged user. Set it
    | to true, will not link user_id to metric data.
    |
    */
    'anonymous' => false,

    /*
    |--------------------------------------------------------------------------
    | Cookie Name
    |--------------------------------------------------------------------------
    |
    | Metrics will use a cookie to track user's visits weither they are logged
    | in or not. 
    |
    */
    'cookie_name' => 'metrics_tracker',
    
    /*
    |--------------------------------------------------------------------------
    | Anonymous Cookie Name
    |--------------------------------------------------------------------------
    |
    | Set cookie name, for anonymous tracking, will track user without linking
    | harvested data to a user id.
    |
    */
    'anonymous_cookie_name' => 'metrics_anonymous_tracker',

    /*
    |--------------------------------------------------------------------------
    | Do Not Track Cookie Name
    |--------------------------------------------------------------------------
    |
    | This cookie allow a user to opt-out tracking, in a similar fashion that
    | if he had 'HTTP_DNT' enabled in his browser. 
    |
    */
    'do_not_track_cookie_name'=> 'metrics_do_not_track', 

   /*
    |--------------------------------------------------------------------------
    | Cookie Lifetime
    |--------------------------------------------------------------------------
    |
    | Life time of a cookie placed on a machine. When lifetime is reached, 
    | metrics will generate a new cookie for the user's browser. 
    | Defaults to 13 months, which is the current legit lifetime in the EU
    |
    */
    'cookie_lifetime' => '13 months',

    /*
    |--------------------------------------------------------------------------
    | Visits retention time
    |--------------------------------------------------------------------------
    |
    | This option tells the package how much time it preserves the visits in the 
    | database. It has to be greater or equal to the smallest analyzer period. 
    |
    */
    'visits_retention_time' => env('METRICS_VISITS_RETENTION_TIME', '1 month'),
    
    /*
    |--------------------------------------------------------------------------
    | Analyzers & Consoliders
    |--------------------------------------------------------------------------
    |
    | Here you can fine tune which analyzers will be run and at which time interval  
    |
    */
   'analyzers' => [
        'hourly' => [
            Code16\Metrics\Analyzers\VisitorAnalyzer::class,
            Code16\Metrics\Analyzers\UrlAnalyzer::class,
            Code16\Metrics\Analyzers\UserAgentAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\SourceAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\CampaignAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\MediaAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\TermAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\ContentAnalyzer::class,
        ],
        'daily' => [
            Code16\Metrics\Analyzers\UniqueVisitorAnalyzer::class,
        ],
        'monthly' => [],
        'yearly' => [],
   ],

   'consoliders' => [
         
        'daily' => [
            Code16\Metrics\Analyzers\VisitorAnalyzer::class,
            Code16\Metrics\Analyzers\UrlAnalyzer::class,
            Code16\Metrics\Analyzers\UserAgentAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\SourceAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\CampaignAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\MediaAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\TermAnalyzer::class,
            Code16\Metrics\Analyzers\UTM\ContentAnalyzer::class,
        ],
        'monthly' => [
            Code16\Metrics\Analyzers\VisitorAnalyzer::class,
            Code16\Metrics\Analyzers\UniqueVisitorAnalyzer::class,
            Code16\Metrics\Analyzers\UrlAnalyzer::class,
            Code16\Metrics\Analyzers\UserAgentAnalyzer::class,
        ],
        'yearly' => [
            Code16\Metrics\Analyzers\VisitorAnalyzer::class,
            Code16\Metrics\Analyzers\UniqueVisitorAnalyzer::class,
            Code16\Metrics\Analyzers\UrlAnalyzer::class,
            Code16\Metrics\Analyzers\UserAgentAnalyzer::class,
        ],
   ],

    /*
    |--------------------------------------------------------------------------
    | Filtered URLs
    |--------------------------------------------------------------------------
    |
    | Some parts of the Application, such as an admin dashboard, may not be
    | relevent to log into Metrics, you can specify them here. 
    |
    */
    'filtered_urls' => [],
   
    /*
    |--------------------------------------------------------------------------
    | User Logging listener
    |--------------------------------------------------------------------------
    | If set to true, metrics will listen for UserLogged event, and associate
    | all previous visited pages to this user.
    |
    */
    'logging' => env('METRICS_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Enable UTM Tracking
    |--------------------------------------------------------------------------
    | If set to true, metrics will analyze standard UTM tracking fields in query
    |
    */
   'enable_utm_tracking' => env('METRICS_UTM_TRACKING', true),

   /*
    |--------------------------------------------------------------------------
    | UTM Field mapping
    |--------------------------------------------------------------------------
    | Customize UTM fields to your preferences here. Note that they will be 
    | always stored in DB using the standardized format, so changing them
    | in production won't affect meaning/content.
    |
    */
   'utm_fields_mapping' => [
        'utm_source' => 'utm_source',
        'utm_medium' => 'utm_medium',
        'utm_campaign' => 'utm_campaign',
        'utm_term' => 'utm_term',
        'utm_content' => 'utm_content',
   ],

];
