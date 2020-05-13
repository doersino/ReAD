<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DB.class.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/TextExtractor.class.php";
require_once __DIR__ . "/TimeUnit.class.php";

// some websites really dislike empty user agent strings
ini_set("user_agent", Config::USER_AGENT);

class Article {
    public static function add($url, $state = "unread", $source = false, $title = false) {

        // remove potential, possibly repeated "calshow:<timestamp>" bits from
        // beginning of url; ios sometimes includes this when sharing articles
        // via shortcuts
        $url = preg_replace('/^(calshow:[0-9]+\s)+/', "", $url);

        // check if article has already been added
        $query = DB::queryFirstRow("SELECT `id`, `time_added`, `time`, `archived` FROM `read` WHERE `url` = %s", $url);

        if (!empty($query)) {

            // if the article is still unread and it's now being re-added as
            // already-archived, just archive it. (note that this ignores
            // $source and $title, but they're probably not set anyway)
            if ($query["archived"] == 0 && $state == "archived") {
                return Article::archive($query["id"]);
            }

            // otherwise, construct meaningful error message
            $formattedToday     = "on " . TimeUnit::sFormatTimeVerbose("day", time());
            $formattedYesterday = "on " . TimeUnit::sFormatTimeVerbose("day", strtotime("-1 day", time()));

            $formattedTimeAdded = "on " . TimeUnit::sFormatTimeVerbose("day", $query["time_added"]);
            if ($formattedTimeAdded == $formattedToday) {
                $formattedTimeAdded = "today";
            } else if ($formattedTimeAdded == $formattedYesterday) {
                $formattedTimeAdded = "yesterday";
            }

            $formattedTime      = "on " . TimeUnit::sFormatTimeVerbose("day", $query["time"]);
            if ($formattedTime == $formattedToday) {
                $formattedTime = "today";
            } else if ($formattedTime == $formattedYesterday) {
                $formattedTime = "yesterday";
            }

            $sameDay = $formattedTimeAdded == $formattedTime;
            $error = "This article has already been added ";
            if ($query["archived"] == 1 && $sameDay) {
                $error .= "and archived ";
            }
            $error .= $formattedTimeAdded;
            if ($query["archived"] == 1 && !$sameDay) {
                $error .= " and archived ";
                $error .= $formattedTime;
            }
            return $error;
        }

        // get source and extract title
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
            return "Invalid state";

        // save source code for later use (e.g. in case article goes offline)
        $id = DB::insertId();
        DB::query("INSERT INTO `read_sources` ( `id`, `source` ) VALUES (%s, %s)", $id, $source);

        // extract text and word count
        $text = TextExtractor::extractText($source);
        DB::query("INSERT INTO `read_texts` ( `id`, `text` ) VALUES (%s, %s)", $id, $text);
        $wordcount = TextExtractor::countWords($text);
        DB::query("UPDATE `read` SET `wordcount` = %i WHERE `id` = %i", $wordcount, $id);

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

        // not necessary due to foreign key constraints (see import.sql), yay
        //DB::query("DELETE FROM `read_sources` WHERE `id` = %i", $id);
        //DB::query("DELETE FROM `read_texts` WHERE `id` = %i", $id);

        return true;
    }
}
