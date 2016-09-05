<?php

require_once "lib/meekrodb.2.3.class.php";
require_once "Config.class.php";
require_once "Helper.class.php";
require_once "TimeUnit.class.php";

class Read {
    public static function getFirstArticleTime() {
        $query = DB::queryFirstField("SELECT `time` FROM `read` WHERE `archived` = 1 ORDER BY `time` ASC LIMIT 1");
        if (empty($query))
            return time();

        return $query;
    }

    public static function getTotalArticleCount($state = false) {
        $totalArticleCount["unread"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = %i", 0);
        $totalArticleCount["archived"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = %i", 1);
        $totalArticleCount["starred"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `starred` = %i", 1);

        if ($state === "unread")
            return $totalArticleCount["unread"];
        else if ($state === "archived")
            return $totalArticleCount["archived"];
        else if ($state === "starred")
            return $totalArticleCount["starred"];
        else
            return $totalArticleCount;
    }

    public static function getArticles($state, $offset, $limit) {
        if ($state === "unread")
            $query = DB::query("SELECT `id`, `url`, `title`, `time_added` AS 'time', `starred` FROM `read` WHERE `archived` = %i ORDER BY `time_added` DESC LIMIT %i OFFSET %i", 0, $limit, $offset);
        else if ($state === "archived")
            $query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 1, $limit, $offset);
        else if ($state === "starred")
            $query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `starred` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 1, $limit, $offset);
        else
            return false;

        for ($i = 0; $i < count($query); ++$i) {
            $query[$i]["url"] = htmlspecialchars($query[$i]["url"], ENT_QUOTES, "UTF-8");
            if (empty($query[$i]["title"]))
                $query[$i]["title"] = "<span class=\"notitle\">No title found.</span>";
        }
        return $query;
    }

    public static function getSearchResults($state, $search) {
        if ($state === "unread")
            $query = DB::query("SELECT `id`, `url`, `title`, `time_added` AS 'time', `starred` FROM `read` WHERE `archived` = %i ORDER BY `time_added` DESC", 0);
        else if ($state === "archived")
            $query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC", 1);
        else if ($state === "starred")
            $query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `starred` = %i ORDER BY `time` DESC", 1);
        else
            return false;

        $rows = array();
        foreach ($query as $row) {
            $row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
            $relevant = stripos($row["title"], $search) !== false || stripos(htmlspecialchars($row["title"], ENT_QUOTES, "UTF-8"), $search) !== false || Config::SEARCH_IN_URLS && stripos($row["url"], $search) !== false || stripos(Helper::getHost($row["url"]), $search) !== false;
            if ($relevant) {
                if (empty($row["title"]))
                    $row["title"] = "<span class=\"notitle\">No title found.</span>";
                $rows[] = $row;
            } else
                continue;
        }
        return $rows;
    }

    public static function getArticlesPerTime($stepsize, $state, $search = false, $start = false, $end = false) {

        // make sure start and end are set
        if ($start === false) {
            $start = self::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        // get data
        if ($state === "unread") {
            $query = DB::query("SELECT `url`, `title`, `time_added` AS 'time'
                                  FROM `read`
                                 WHERE `archived` = 0
                                   AND `time_added` BETWEEN %s AND %s
                              ORDER BY `time_added` ASC", $start, $end);
        } else if ($state === "archived") {
            $query = DB::query("SELECT `url`, `title`, `time`
                                  FROM `read`
                                 WHERE `archived` = 1
                                   AND `time` BETWEEN %s AND %s
                              ORDER BY `time` ASC", $start, $end);
        } else if ($state === "starred") {
            $query = DB::query("SELECT `url`, `title`, `time`
                                  FROM `read`
                                 WHERE `starred` = 1
                                   AND `time` BETWEEN %s AND %s
                              ORDER BY `time` ASC", $start, $end);
        } else {
            return false;
        }

        // create helper object with time unit
        $unit = substr($stepsize, 0, -1); // plural -> singular
        $t = new TimeUnit($unit);

        // initialize state and output
        $currentTime = $start;
        $times = array();
        $times[$currentTime] = 0;

        foreach ($query as $row) {
            $row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
            $relevant = !$search || $search && (stripos($row["title"], $search) !== false || stripos(htmlspecialchars($row["title"], ENT_QUOTES, "UTF-8"), $search) !== false || Config::SEARCH_IN_URLS && stripos($row["url"], $search) !== false || stripos(Helper::getHost($row["url"]), $search) !== false);

            // more articles for same day/week/...
            if ($t->sameTime($row["time"], $currentTime)) {
                if ($relevant)
                    $times[$currentTime]++;

            // new day/week/...
            } else {
                $currentTime = $t->incrementTime($currentTime);

                // days/weeks/... with no articles
                while (!$t->sameTime($row["time"], $currentTime)) {
                    $times[$currentTime] = 0;
                    $currentTime = $t->incrementTime($currentTime);
                }

                // first article afterwards
                $times[$currentTime] = $relevant ? 1 : 0;
            }
        }

        // days/weeks/... after latest article
        while (!$t->sameTime($end, $currentTime)) {
            $currentTime = $t->incrementTime($currentTime);
            $times[$currentTime] = 0;
        }

        return $times;
    }
}

?>
