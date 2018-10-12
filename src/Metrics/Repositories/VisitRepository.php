<?php

namespace Code16\Metrics\Repositories;

use Carbon\Carbon;
use Code16\Metrics\Visit;
use Code16\Metrics\TimeInterval;

interface VisitRepository {

    public function all();

    public function store(Visit $visit);

    public function getTimeInterval(Carbon $start, Carbon $end);

    public function getByTimeInterval(TimeInterval $interval);

    public function first();

    public function last();

    public function visitsFromUser($userId);

    public function lastVisitFromUser($userId);

    public function lastVisitBySession($sessionId, Carbon $from = null);

    public function translateCookie($oldCookie, $newCookie);

    public function oldestVisitForCookie($cookie);

    public function countByTimeInterval(TimeInterval $interval);

    public function anonymizeUntil(Carbon $until);

    public function updateSessionId($cookie, $sessionId, Carbon $from, Carbon $to);
}
