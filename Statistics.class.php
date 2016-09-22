<?php

require_once "deps/meekrodb.2.3.class.php";
require_once "Config.class.php";
require_once "Helper.class.php";
require_once "Read.class.php";
require_once "TextExtractor.class.php";

class Statistics {
    public static function articlesPerTime($stepsize, $state, $search = false, $start = false, $end = false) {

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

    public static function totalTimeSpent($start = false, $end = false) {
        if ($start === false) {
            $start = Read::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        $wordcount = DB::queryFirstField("SELECT sum(`wordcount`) FROM `read` WHERE `archived` = 1 AND `time_added` BETWEEN %s AND %s", $start, $end);

        return TextExtractor::computeErt($wordcount);
    }

    // compute longest streak (largest range of days on which at least one article
    // was read)
    function longestStreak($start, $end) {
        $articles = self::articlesPerTime("days", "archived", false, $start, $end);
        $emptyStreak   = array("start" => 0, "end" => 0, "length" => 0, "count" => 0);
        $longestStreak = $emptyStreak;
        $currentStreak = $emptyStreak;
        $currentStreak["start"] = key($articles);

        foreach ($articles as $day => $count) {
            if ($count == 0) {
                if ($currentStreak["length"] > $longestStreak["length"]) {
                    $longestStreak = $currentStreak;
                }
                $currentStreak = $emptyStreak;
                $currentStreak["start"] = strtotime("+1 day", $day);
            } else {
                $currentStreak["end"] = $day;
                $currentStreak["length"] += 1;
                $currentStreak["count"] += $count;
            }
        }

        if ($currentStreak["length"] > $longestStreak["length"]) {
            $longestStreak = $currentStreak;
        }

        return $longestStreak;
    }

    function currentStreak($start, $time) {
        $articles = Statistics::articlesPerTime("days", "archived", false, $start, $time);
        $currentStreak = array("length" => 0, "count" => 0);

        $articles = array_reverse($articles);
        foreach ($articles as $count) {
            if ($count == 0) {
                break;
            }
            $currentStreak["length"] += 1;
            $currentStreak["count"] += $count;
        }

        return $currentStreak;
    }

    // for printing "top 10" tables (without actions!) or similar, each element of
    // the array must be an associative array with indices "text" (required),
    // "left", "link", and "info" (all optional)
    public static function printTable($array) {
        echo "<table>";
        $n = 0;
        foreach ($array as $a) {
            echo "<tr>";
            echo "<td class=\"left\">";
            if (array_key_exists("left", $a)) {
                echo $a["left"];
            } else {
                $n++;
                echo $n;
            }
            echo "</td>";
            echo "<td class=\"middle\">";

            $text = $a["text"];
            if (array_key_exists("link", $a)) {
                $link = $a["link"];
                echo "<a href=\"$link\" class=\"text\">$text</a>";
            } else {
                echo "<span class=\"text\">$text</span>";
            }

            if (array_key_exists("info", $a)) {
                $info = $a["info"];
                echo " <span class=\"info\">$info</span>";
            }

            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // for printing the js code for most of the graphs
    function printGraph($id, $x, $y, $text) {
        global $gridColor, $linecolor, $fillcolor;

        $x = implode("','", $x);
        $y = implode(",", $y);
        $text = implode("','", $text);

        echo <<<EOF

var $id = [{
    type: 'scatter',
    mode: 'lines',
    x: ['$x'],
    y: [$y],
    text: ['$text'],
    hoverinfo: 'text',
    fillcolor: '$fillcolor',
    fill: 'tozeroy',
    line: {
        color: '$linecolor',
        width: 1,
    }
}];
var {$id}Layout = {
    plot_bgcolor: 'rgba(0,0,0,0)',
    paper_bgcolor: 'rgba(0,0,0,0)',
    margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
    xaxis: {
        type: 'date',
        zeroline: false,
        gridcolor: '$gridColor',
    },
    yaxis: {
        zeroline: false,
        gridcolor: '$gridColor',
        //dtick: 10,
    },
};
Plotly.newPlot('$id', $id, {$id}Layout, {displayModeBar: false});

EOF;
    }
}
