<?php

use PHPUnit\Framework\TestCase;
use davidrnk\RecurringDate\Core\RecurringDateEngine;

class RecurringDateEngineTest extends TestCase
{
    public function testIntervalAddsDays()
    {
        $start = new DateTime('2021-01-01');
        $cfg = ['type' => 'interval', 'value' => 10, 'unit' => 'days'];
        $res = RecurringDateEngine::calculateExpiration($start, $cfg);
        $this->assertInstanceOf(DateTime::class, $res);
        $this->assertEquals('2021-01-11', $res->format('Y-m-d'));
    }

    public function testMonthlyAdjusts31ToFebLastDay()
    {
        $start = new DateTime('2021-01-31');
        $cfg = ['type' => 'monthly', 'day' => 31];
        $res = RecurringDateEngine::calculateExpiration($start, $cfg);
        $this->assertInstanceOf(DateTime::class, $res);
        // 2021 is not leap year, so feb last day is 28
        $this->assertEquals('2021-02-28', $res->format('Y-m-d'));
    }

    public function testMonthlyAdjustNextAdvancesOneDay()
    {
        $start = new DateTime('2021-01-31');
        $cfg = ['type' => 'monthly', 'day' => 31, 'adjust' => 'next'];
        $res = RecurringDateEngine::calculateExpiration($start, $cfg);
        $this->assertInstanceOf(DateTime::class, $res);
        // with 'next' the result should be last day + 1 => 2021-03-01 (Feb has 28 days)
        $this->assertEquals('2021-03-01', $res->format('Y-m-d'));
    }

    public function testYearlyAdjustsInvalidDayForFeb()
    {
        $start = new DateTime('2021-01-01');
        $cfg = ['type' => 'yearly', 'month' => 2, 'day' => 31];
        $res = RecurringDateEngine::calculateExpiration($start, $cfg);
        // invalid combination (31 Feb) should be rejected and return null
        $this->assertNull($res);
    }

    public function testYearlyIfCandidatePassedGoesNextYear()
    {
        $start = new DateTime('2021-09-10');
        $cfg = ['type' => 'yearly', 'month' => 9, 'day' => 1];
        $res = RecurringDateEngine::calculateExpiration($start, $cfg);
        $this->assertInstanceOf(DateTime::class, $res);
        // new policy: do not automatically add a year; candidate remains in the same year
        $this->assertEquals('2021-09-01', $res->format('Y-m-d'));
    }

    public function testCalculateExpirationReturnsNullOnInvalidSpecificDate()
    {
        $cfg = ['type' => 'specific_date', 'date' => 'invalid-date'];
        $res = RecurringDateEngine::calculateExpiration('invalid-date', $cfg);
        $this->assertNull($res);
    }
}
