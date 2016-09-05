<?php

require_once("Config.class.php");

class InvalidTimeUnitException extends Exception {}

class TimeUnit {
    private $unit;

    public function __construct($unit) {
        if (!in_array($unit, array("day", "week", "month", "year"))) {
            throw new InvalidTimeUnitException();
        }
        $this->unit = $unit;
    }

    public function getUnit() {
        return $this->unit;
    }

    public function incrementTime($timestamp, $n = 1) {

        // special case for months because they have different lengths, e.g.
        // adding one month to january 31 will land you at february 31, which
        // makes little sense and is automatically changed to the corresponding
        // day in early march, essentially skipping a month
        // solution: use fixed day of month
        // note: a similar workaround might be necessary for leap years
        if ($this->unit == "month") {
            $month = date("n", $timestamp);
            $year = date("Y", $timestamp);
            return mktime(0, 0, 0, ($month + 1), 15, $year);
        }
        return strtotime("+$n $this->unit", $timestamp);
    }

    public function sameTime($timestamp1, $timestamp2) {
        return $this->formatTime($timestamp1) == $this->formatTime($timestamp2);
    }

    public function formatTime($timestamp) {
        return $this->sFormatTime($this->unit, $timestamp);
    }

    public function formatTimeVerbose($timestamp) {
        return $this->sFormatTimeVerbose($this->unit, $timestamp);
    }

    public static function sFormatTime($unit, $timestamp) {
        switch ($unit) {
            case "day":
                return date("Y-m-d", $timestamp);
            case "week":
                if (Config::START_OF_WEEK === "sun") {

                    // via https://weeknumber.net/how-to/php
                    $timestamp = strtotime("+1 day", $timestamp);
                }
                return strftime('%G-%V', $timestamp);
            case "month":
                return date("Y-m", $timestamp);
            case "year":
                return date("Y", $timestamp);
            case "iso":
                return date("c", $timestamp);
        }
    }

    public static function sFormatTimeVerbose($unit, $timestamp) {
        switch ($unit) {
            case "day":
                return date("l, F d, Y", $timestamp);
            case "week":
                if (Config::START_OF_WEEK === "sun") {

                    // via https://weeknumber.net/how-to/php
                    $timestamp = strtotime("+1 day", $timestamp);
                }
                return "Week " . strftime('%V, %G', $timestamp);
            case "month":
                return date("F Y", $timestamp);
            case "year":
                return date("Y", $timestamp);
            case "iso":
                return date("Y-m-d H:i:s", $timestamp);
        }
    }
}
