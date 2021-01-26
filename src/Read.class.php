<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DB.class.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/TimeUnit.class.php";

class Read {
    public static function getFirstArticleTime() {
        $query = DB::queryFirstField("SELECT `time` FROM `read` WHERE `archived` = 1 ORDER BY `time` ASC LIMIT 1");
        if (empty($query))
            return time();

        return $query;
    }

    public static function getTotalArticleCount($state = false, $search = false, $start = false, $end = false) {

        // make sure start and end are set
        if ($start === false) {
            $start = self::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        if ($search === false) {
            $totalArticleCount["unread"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = 0 AND `time_added` BETWEEN %s AND %s", $start, $end);
            $totalArticleCount["archived"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = 1 AND `time` BETWEEN %s AND %s", $start, $end);
            $totalArticleCount["starred"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `starred` = 1 AND `time` BETWEEN %s AND %s", $start, $end);
        } else {
            $totalArticleCount["unread"] = count(self::getSearchResults("unread", $search, $start, $end));
            $totalArticleCount["archived"] = count(self::getSearchResults("archived", $search, $start, $end));
            $totalArticleCount["starred"] = count(self::getSearchResults("starred", $search, $start, $end));
        }

        if ($state === "unread")
            return $totalArticleCount["unread"];
        else if ($state === "archived")
            return $totalArticleCount["archived"];
        else if ($state === "starred")
            return $totalArticleCount["starred"];
        else
            return $totalArticleCount;
    }

    public static function getArticle($id) {
        $query = DB::queryFirstRow("
            SELECT `read`.`id`, `url`, `title`, `wordcount`, CASE WHEN `archived` = 0 THEN `time_added` ELSE `read`.`time` END AS 'time', `text`, `archived`, `starred`, count(`quote_id`) AS 'quote_count'
            FROM `read` LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`, `read_texts`
            WHERE `read`.`id` = `read_texts`.`id`
            AND `read`.`id` = %i
            GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `time_added`, `read`.`time`, `text`, `archived`, `starred`
            ", $id);

        if (empty($query)) {
            return false;
        }

        $query["url"] = htmlspecialchars($query["url"], ENT_QUOTES, "UTF-8");
        if (empty($query["title"])) {
            $query["title"] = "<span class=\"notitle\">No title found.</span>";
        } else {
            $query["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $query["title"]);
        }

        // get quotes if available
        if ($query["quote_count"] > 0) {
            $quotes = DB::query("SELECT `quote_id`, `quote`, `time`
                                   FROM `read_quotes`
                                  WHERE `id` = %i
                                 ORDER BY `time`", $id);
            $query["quotes"] = $quotes;
        }

        // TODO in quotes, do i need to replace < and > as for titles?

        return $query;
    }

    public static function getArticles($state, $offset = 0, $limit = 99999999) {
        if ($state === "unread")
            $query = DB::query("
                SELECT `read`.`id`, `url`, `title`, `wordcount`, `time_added` AS 'time', `starred`, count(`quote_id`) AS 'quote_count'
                FROM `read`
                LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`
                WHERE `archived` = 0
                GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `time_added`, `starred`
                ORDER BY `time_added` DESC
                LIMIT %i
                OFFSET %i
                ", $limit, $offset);
        else if ($state === "archived")
            $query = DB::query("
                SELECT `read`.`id`, `url`, `title`, `wordcount`, `read`.`time`, `starred`, count(`quote_id`) AS 'quote_count'
                FROM `read`
                LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`
                WHERE `archived` = 1
                GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `time`, `starred`
                ORDER BY `time` DESC
                LIMIT %i
                OFFSET %i
                ", $limit, $offset);
        else if ($state === "starred")
            $query = DB::query("
                SELECT `read`.`id`, `url`, `title`, `wordcount`, `read`.`time`, `starred`, count(`quote_id`) AS 'quote_count'
                FROM `read`
                LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`
                WHERE `starred` = 1
                GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `time`, `starred`
                ORDER BY `time` DESC
                LIMIT %i
                OFFSET %i
                ", $limit, $offset);
        else
            return false;

        for ($i = 0; $i < count($query); ++$i) {
            $query[$i]["url"] = htmlspecialchars($query[$i]["url"], ENT_QUOTES, "UTF-8");
            if (empty($query[$i]["title"])) {
                $query[$i]["title"] = "<span class=\"notitle\">No title found.</span>";
            } else {
                $query[$i]["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $query[$i]["title"]);
            }

            if ($query[$i]["quote_count"] > 0) {
                $quotes = DB::query("SELECT `quote_id`, `quote`, `time`
                                       FROM `read_quotes`
                                      WHERE `id` = %i
                                     ORDER BY `time`", $query[$i]["id"]);
                $query[$i]["quotes"] = $quotes;
            }
        }
        return $query;
    }

    public static function getSearchResults($state, $search, $start = false, $end = false) {

        // make sure start and end are set
        if ($start === false) {
            $start = self::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        // TODO remove duplication between these queries (also for non-search)
        if ($state === "unread")
            $query = DB::query("
                SELECT `read`.`id`, `url`, `title`, `wordcount`, `time_added` AS 'time', `starred`, count(`quote_id`) AS 'quote_count'
                FROM `read`
                LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`
                WHERE `archived` = 0
                AND `time_added` BETWEEN %s AND %s
                GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `time_added`, `starred`
                ORDER BY `time_added` DESC
                ", $start, $end);
        else if ($state === "archived")
            $query = DB::query("
                SELECT `read`.`id`, `url`, `title`, `wordcount`, `read`.`time`, `starred`, count(`quote_id`) AS 'quote_count'
                FROM `read`
                LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`
                WHERE `archived` = 1
                AND `read`.`time` BETWEEN %s AND %s
                GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `read`.`time`, `starred`
                ORDER BY `read`.`time` DESC
                ", $start, $end);
        else if ($state === "starred")
            $query = DB::query("
                SELECT `read`.`id`, `url`, `title`, `wordcount`, `read`.`time`, `starred`, count(`quote_id`) AS 'quote_count'
                FROM `read`
                LEFT JOIN `read_quotes` ON `read`.`id` = `read_quotes`.`id`
                WHERE `starred` = 1
                AND `read`.`time` BETWEEN %s AND %s
                GROUP BY `read`.`id`, `url`, `title`, `wordcount`, `read`.`time`, `starred`
                ORDER BY `read`.`time` DESC
                ", $start, $end);
        else
            return false;

        $rows = array();
        foreach ($query as $row) {
            $row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");

            $relevantQuote = false;
            if (Config::SEARCH_IN_QUOTES && $row["quote_count"] > 0) {
                $quotes = DB::query("SELECT `quote_id`, `quote`, `time`
                                       FROM `read_quotes`
                                      WHERE `id` = %i
                                     ORDER BY `time`", $row["id"]);
                $row["quotes"] = $quotes;
                foreach ($quotes as $quote) {
                    if (stripos(htmlspecialchars($quote["quote"]), $search) !== false) {  // TODO also non-htmlspecialchars-variant needed? investigate! probably depends on js code that adds quotes? idk?
                        // TODO also investigate why searching "&" doesn't highlight the "&" in titles and quotes and etc.
                        $relevantQuote = true;
                        break;
                    }
                }
            }

            $relevantTitle = stripos($row["title"], $search) !== false;  // kept around for finding stuff ~pre-2015 in my instance (i belive, anyway)
            $relevantTitle2 = stripos(htmlspecialchars($row["title"], ENT_QUOTES, "UTF-8"), $search) !== false;
            $relevantUrl = Config::SEARCH_IN_URLS && stripos($row["url"], $search) !== false;
            $relevantHost = stripos(Helper::getHost($row["url"]), $search) !== false;
            $relevant = $relevantQuote || $relevantTitle || $relevantTitle2 || $relevantUrl || $relevantHost;
            if ($relevant) {
                if (empty($row["title"])) {
                    $row["title"] = "<span class=\"notitle\">No title found.</span>";
                } else {
                    $row["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $row["title"]);
                }
                $rows[] = $row;
            } else {
                continue;
            }
        }
        return $rows;
    }

    public static function getRandomOldUnreadArticleId() {
        return DB::queryFirstField("
            SELECT `id`
            FROM  `read`
            WHERE `archived` = 0
            AND   `time_added` < %i  -- must be old
            AND   (SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = 0) > 100  -- must have enough articles
            AND   `title` <> ''  -- let's pass over title-less ones
            ORDER BY RAND()
            LIMIT 1", strtotime("-1 month")
        );
    }
}
