<?php

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

    public function formatTime($timestamp) {
        switch ($this->unit) {
            case "day":
                return date("Y-m-d", $timestamp);
            case "week":
                if (Config::$startOfWeek === "sun") {

                    // via https://weeknumber.net/how-to/php
                    $timestamp = strtotime("+1 day", $timestamp);
                }
                return strftime('%G-%V', $timestamp);
            case "month":
                return date("Y-m", $timestamp);
            case "year":
                return date("Y", $timestamp);
        }
    }

    public function formatTimeVerbose($timestamp) {
        switch ($this->unit) {
            case "day":
                return date("l, F d, Y", $timestamp);
            case "week":
                if (Config::$startOfWeek === "sun") {

                    // via https://weeknumber.net/how-to/php
                    $timestamp = strtotime("+1 day", $timestamp);
                }
                return "Week " . strftime('%V, %G', $timestamp);
            case "month":
                return date("F Y", $timestamp);
            case "year":
                return date("Y", $timestamp);
        }
    }

    public function incrementTime($timestamp, $n = 1) {
        return strtotime("+$n $this->unit", $timestamp);
    }

    public function sameTime($timestamp1, $timestamp2) {
        return $this->formatTime($timestamp1) == $this->formatTime($timestamp2);
    }
}

?>
