<?php

namespace Code16\Metrics;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

class Visit implements Arrayable
{
    /**
     * id of the record in database
     * 
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $user_id;
   
    /**
     * IP of the visit
     * 
     * @var string
     */
    protected $ip;

    /**
     * Full user agent of the visit
     * 
     * @var string
     */
    protected $user_agent;

    /**
     * The tracking cookie, if set
     * 
     * @var string
     */
    protected $cookie;

    /**
     * The full url of the visit (without query string)
     * 
     * @var string
     */
    protected $url;

    /**
     * The referer for current visit
     * 
     * @var string
     */
    protected $referer;

    /**
     * Actions objets
     * 
     * @var Collection
     */
    protected $actions;

    /**
     * Custom data to be added 
     * 
     * @var array
     */
    protected $custom = [];

    /**
     * DateTime of the visit
     *
     * @var Carbon
     */
    protected $date;

    /**
     * Anonymous flag
     * 
     * @var boolean
     */
    protected $anonymous;

    /**
     * Laravel's session id
     * 
     * @var string
     */
    protected $session_id;

    /**
     * Response status code
     *
     * @var string
     */
    protected $status_code;

    public function __construct()
    {
        $this->actions = new Collection;
    }

    /**
     * Create a Visit instance from an array. We'll use this Essentially
     * to reconstruct a Visit object from a database row.
     * 
     * @param  array  $data
     * @return Visit
     */
    public static function createFromArray(array $data)
    {
        $visit = new Static;
        if (isset($data['id'])) {
            $visit->id = $data['id'];    
        }
        $visit->ip = $data['ip'];
        $visit->user_agent = $data['user_agent'];
        $visit->user_id = $data['user_id'];
        $visit->custom = $data['custom'];
        $visit->url = $data['url'];
        $visit->referer = $data['referer'];
        $visit->date = $data['date'];
        $visit->cookie = $data['cookie'];
        $visit->anonymous = $data['anonymous'];
        $visit->session_id = $data['session_id'];
        $visit->status_code = $data['status_code'];
        foreach($data['actions'] as $action) {
            $visit->addAction(unserialize($action));
        }
        return $visit;
    }

    /**
     * Set anonymous flag
     * 
     * @param boolean $anonymous 
     */
    public function setAnonymous($anonymous = true)
    {
        $this->anonymous = $anonymous;
    }

    /**
     * Is Visit anonymous ?
     * 
     * @return boolean
     */
    public function isAnonymous()
    {
        return $this->anonymous;
    }

    /**
     * Set the user id for this record
     * 
     * @param  int $userId
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    /**
     * Get the user id
     *
     * @return string
     */
    public function userId()
    {
        return $this->user_id;
    }

    /**
     * Get the visited url
     * 
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the Url
     * 
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Set referer
     * 
     * @param string $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    /**
     * Get request IP
     * 
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set IP Address
     * 
     * @param string $ip
     * @return void
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * Return laravel cookie
     * 
     * @return string
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * Set cookie
     * 
     * @param string $cookie
     */
    public function setCookie($cookie = null)
    {
        if($cookie) {
            $this->cookie = $cookie;
        }
        else {
            $this->cookie = str_random(32);
        }
    }

    /**
     * Return date
     * 
     * @return Carbon\Carbon
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set date
     * 
     * @param Carbon $date [description]
     */
    public function setDate(Carbon $date)
    {   
        $this->date = $date;
    }

    /**
     * Return user agent 
     * 
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set User Agent
     *
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->user_agent = $userAgent;
    }

    /**
     * Set Session id
     * 
     * @param string  $sessionId 
     */
    public function setSessionId($sessionId)
    {
        $this->session_id = $sessionId;
    }

    /**
     * Get session id
     * 
     * @return string
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Set status code
     * 
     * @param string  $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->status_code = $statusCode;
    }

    /**
     * Get status code
     * 
     * @return string
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * Get actions on this visit
     * 
     * @return ActionCollection
     */
    public function actions()
    {
        return $this->actions;
    }

    /**
     * Attach an action to the visit
     * 
     * @param Action $action [description]
     * @return Visit
     */
    public function addAction(Action $action)
    {
        $this->actions->push($action);
        return $this;
    }

    /**
     * Get the action from the given class
     * 
     * @param  string $actionClass
     * @return  Action | null
     */
    public function getAction($actionClass)
    {
        foreach($this->actions as $action) {
            if(get_class($action) == $actionClass) {
                return $action;
            }
        }
        return null;
    }

    /**
     * Return true if the Visit has an action of the given class
     * 
     * @param  string  $actionClass
     * @return boolean        
     */
    public function hasAction($actionClass)
    {
        foreach($this->actions as $action) {
            if(get_class($action) == $actionClass) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add a custom tracking value to the object
     * 
     * @param string $key 
     * @param mixed $value
     */
    public function setCustomValue($key, $value)
    {   
        $this->custom[$key] = $value;
    }

    /**
     * Get a custom value from the object
     * 
     * @param  string $key
     * @return mixed
     */
    public function getCustomValue($key)
    {
        return $this->custom[$key];
    }

    /**
     * Check if a custom value exists
     * 
     * @param  string  $key 
     * @return boolean
     */
    public function hasCustomValue($key)
    {
        return array_key_exists($key, $this->custom);
    }

    /**
     * Convert object to array, including serialisation of actions
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'ip' => $this->ip,
            'user_id' => $this->user_id,
            'user_agent' =>  $this->user_agent,
            'actions' => $this->getSerializedActions(),
            'custom' => $this->custom,
            'cookie' => $this->cookie,
            'url' => $this->url,
            'referer' => $this->referer,
            'date' => $this->date,
            'anonymous' => $this->anonymous,
            'session_id' => $this->session_id,
            'status_code' => $this->status_code,
        ];
    }

    /**
     * Get actions as serialized objects
     * 
     * @return array
     */
    protected function getSerializedActions()
    {
        $actions = [];

        foreach($this->actions as $action) {
            $actions[] = serialize($action);
        }

        return $actions;
    }

    /**
     * Magic getter for object's properties
     * 
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->$key;
    }
}
