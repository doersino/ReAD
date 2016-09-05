<?php

require_once "lib/meekrodb.2.3.class.php";
require_once "Config.class.php";
require_once "Helper.class.php";
require_once "TimeUnit.class.php";

class Article {
    public static function add($url, $state = "unread", $source = false, $title = false) {

        // make sure article hasn't already been added
        $query = DB::queryFirstRow("SELECT `time_added`, `time`, `archived` FROM `read` WHERE `url` = %s", $url);
        if (!empty($query)) {
            $sameDay = TimeUnit::sFormatTime("day", $query["time_added"]) == TimeUnit::sFormatTime("day", $query["time"]);
            $error = "This article has already been added ";
            if ($query["archived"] == 1 && $sameDay) {
                $error .= "and archived ";
            }
            $error .= "on ";
            $error .= TimeUnit::sFormatTimeVerbose("day", $query["time_added"]);
            if ($query["archived"] == 1 && !$sameDay) {
                $error .= " and archived on ";
                $error .= TimeUnit::sFormatTimeVerbose("day", $query["time"]);
            }
            return $error;
        }

        // get soruce and extract title
        if (!$source)
            $source = Helper::getSource($url);
        if (!$title)
            $title = Helper::getTitle($source, $url);

        // add with given state
        if ($state === "unread")
            $query = DB::query("INSERT INTO `read` ( `url`, `title`, `time_added` ) VALUES (%s, %s, %s)", $url, $title, time());
        else if ($state === "archived")
            $query = DB::query("INSERT INTO `read` ( `url`, `title`, `time_added`, `time`, `archived` ) VALUES (%s, %s, %s, %s, %s)", $url, $title, time(), time(), 1);
        else if ($state === "starred")
            $query = DB::query("INSERT INTO `read` ( `url`, `title`, `time_added`, `time`, `archived`, `starred` ) VALUES (%s, %s, %s, %s, %s, %s)", $url, $title, time(), time(), 1, 1);
        else
            return false;

        // save source code for later use (e.g. in case article goes offline)
        $id = DB::insertId();
        $query = DB::query("INSERT INTO `read_sources` ( `id`, `source` ) VALUES (%s, %s)", $id, $source);

        return true;
    }

    public static function archive($id) {
        $query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `id` = %i", $id);
        if (empty($query))
            return "This article doesn't seem to exist, so it can't be archived";

        DB::query("UPDATE `read` SET `archived` = %i, `time` = %i WHERE `id` = %i", 1, time(), $id);
        return true;
    }

    public static function star($id) {
        $query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `id` = %i", $id);
        if (empty($query))
            return "This article doesn't seem to exist, so it can't be starred";

        DB::query("UPDATE `read` SET `starred` = %i WHERE `id` = %i", 1, $id);
        return true;
    }

    public static function unstar($id) {
        $query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `id` = %i", $id);
        if (empty($query))
            return "This article doesn't seem to exist, so it can't be unstarred";

        DB::query("UPDATE `read` SET `starred` = %i WHERE `id` = %i", 0, $id);
        return true;
    }

    public static function remove($id) {
        DB::query("DELETE FROM `read` WHERE `id` = %i", $id);

        // not necessary due to foreign key constraint (see import.sql), yay
        //DB::query("DELETE FROM `read_sources` WHERE `id` = %i", $id);

        return true;
    }
}
