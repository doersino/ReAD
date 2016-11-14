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

    public static function getTotalArticleCount($state = false, $start = false, $end = false) {

        // make sure start and end are set
        if ($start === false) {
            $start = self::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        $totalArticleCount["unread"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = 0 AND `time_added` BETWEEN %s AND %s", $start, $end);
        $totalArticleCount["archived"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `archived` = 1 AND `time` BETWEEN %s AND %s", $start, $end);
        $totalArticleCount["starred"] = DB::queryFirstField("SELECT COUNT(1) AS 'count' FROM `read` WHERE `starred` = 1 AND `time` BETWEEN %s AND %s", $start, $end);

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
        $query = DB::queryFirstRow("SELECT `read`.`id`, `url`, `title`, `wordcount`, `time_added` AS 'time', `text`
                                      FROM `read`, `read_texts`
                                     WHERE `read`.`id` = `read_texts`.`id`
                                       AND `read`.`id` = %i", $id);

        if (empty($query)) {
            return false;
        }

        $query["url"] = htmlspecialchars($query["url"], ENT_QUOTES, "UTF-8");
        $query["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $query["title"]);
        if (empty($query["title"])) {
            $query["title"] = "<span class=\"notitle\">No title found.</span>";
        }
        //$query["text"] = str_replace("\n", "<br>", $query["text"]);
        return $query;
    }

    public static function getArticles($state, $offset = 0, $limit = 99999999) {
        if ($state === "unread")
            $query = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time_added` AS 'time', `starred` FROM `read` WHERE `archived` = %i ORDER BY `time_added` DESC LIMIT %i OFFSET %i", 0, $limit, $offset);
        else if ($state === "archived")
            $query = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 1, $limit, $offset);
        else if ($state === "starred")
            $query = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time`, `starred` FROM `read` WHERE `starred` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 1, $limit, $offset);
        else
            return false;

        for ($i = 0; $i < count($query); ++$i) {
            $query[$i]["url"] = htmlspecialchars($query[$i]["url"], ENT_QUOTES, "UTF-8");
            $query[$i]["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $query[$i]["title"]);
            if (empty($query[$i]["title"]))
                $query[$i]["title"] = "<span class=\"notitle\">No title found.</span>";
        }
        return $query;
    }

    public static function getSearchResults($state, $search) {
        if ($state === "unread")
            $query = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time_added` AS 'time', `starred` FROM `read` WHERE `archived` = %i ORDER BY `time_added` DESC", 0);
        else if ($state === "archived")
            $query = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC", 1);
        else if ($state === "starred")
            $query = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time`, `starred` FROM `read` WHERE `starred` = %i ORDER BY `time` DESC", 1);
        else
            return false;

        $rows = array();
        foreach ($query as $row) {
            $row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
            $relevant = stripos($row["title"], $search) !== false || stripos(htmlspecialchars($row["title"], ENT_QUOTES, "UTF-8"), $search) !== false || Config::SEARCH_IN_URLS && stripos($row["url"], $search) !== false || stripos(Helper::getHost($row["url"]), $search) !== false;
            $row["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $row["title"]);
            if ($relevant) {
                if (empty($row["title"]))
                    $row["title"] = "<span class=\"notitle\">No title found.</span>";
                $rows[] = $row;
            } else
                continue;
        }
        return $rows;
    }
}
