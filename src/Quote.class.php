<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DB.class.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/TimeUnit.class.php";

class Quote {
    public static function add($quote, $id) {
        $query = DB::query("INSERT INTO `read_quotes` ( `quote`, `id`, `time` ) VALUES (%s, %s, %s)", $quote, $id, time());
        $id = DB::insertId();
        return $id;
    }

    public static function remove($quote_id) {
        DB::query("DELETE FROM `read_quotes` WHERE `quote_id` = %i", $quote_id);
        return true;
    }
}
