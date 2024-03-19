<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DB.class.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/Read.class.php";
require_once __DIR__ . "/TextExtractor.class.php";

class Statistics {
    public static function perTime($what, $stepsize, $state, $search = false, $start = false, $end = false) {

        // make sure start and end are set
        if ($start === false) {
            $start = Read::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        // required AND clause for each state
        $andState = array(
            "all"      => "",
            "unread"   => "AND `archived` = 0",
            "archived" => "AND `archived` = 1",
            "starred"  => "AND `starred` = 1"
        );

        // get data
        if ($what == "added" || $state === "unread") {
            // basically same query because unread = added and not yet archived
            $query = DB::query("SELECT `url`, `title`, `wordcount`, `time_added` AS 'time'
                                  FROM `read`
                                 WHERE `time_added` BETWEEN %s AND %s
                                       $andState[$state]
                              ORDER BY `time_added` ASC", $start, $end);
        } else if ($what === "deleted") {

            // much more limited in information, only use this for added vs. archived vs. deleted graph
            $query = DB::query("SELECT `time_deleted` AS 'time', '' AS 'url'
                                  FROM `read_deletions`
                                 WHERE `time_deleted` BETWEEN %s AND %s
                              ORDER BY `time_deleted` ASC", $start, $end);
        } else if ($state === "archived" || $state === "starred" || $state === "all") {
            $query = DB::query("SELECT `url`, `title`, `wordcount`, `time`
                                  FROM `read`
                                 WHERE `time` BETWEEN %s AND %s
                                       $andState[$state]
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

        if ($what == "articles" || $what == "added" || $what == "deleted") {
            $increment = 1;
        }

        foreach ($query as $row) {
            if ($what == "wordcount") {
                $increment = $row["wordcount"];
            }

            $row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
            $relevant = true;
            if ($search) {
                $relevantQuote = false;  // TODO implement
                $relevantTitle = stripos($row["title"], $search) !== false;  // kept around for finding stuff ~pre-2015 in my instance (i belive, anyway)
                $relevantTitle2 = stripos(htmlspecialchars($row["title"], ENT_QUOTES, "UTF-8"), $search) !== false;
                $relevantUrl = Config::SEARCH_IN_URLS && stripos($row["url"], $search) !== false;
                $relevantHost = stripos(Helper::getHost($row["url"]), $search) !== false;
                $relevant = $relevantQuote || $relevantTitle || $relevantTitle2 || $relevantUrl || $relevantHost;
            }

            // more articles for same day/week/...
            if ($t->sameTime($row["time"], $currentTime)) {
                if ($relevant)
                    $times[$currentTime] += $increment;

            // new day/week/...
            } else {
                $currentTime = $t->incrementTime($currentTime);

                // days/weeks/... with no articles
                while (!$t->sameTime($row["time"], $currentTime)) {
                    $times[$currentTime] = 0;
                    $currentTime = $t->incrementTime($currentTime);
                }

                // first article afterwards
                $times[$currentTime] = $relevant ? $increment : 0;
            }
        }

        // days/weeks/... after latest article
        while (!$t->sameTime($end, $currentTime)) {
            $currentTime = $t->incrementTime($currentTime);
            $times[$currentTime] = 0;
        }

        return $times;
    }

    public static function articlesPerTime($stepsize, $state, $search = false, $start = false, $end = false) {
        return self::perTime("articles", $stepsize, $state, $search, $start, $end);
    }

    public static function wordcountPerTime($stepsize, $state, $search = false, $start = false, $end = false) {
        return self::perTime("wordcount", $stepsize, $state, $search, $start, $end);
    }

    public static function addedPerTime($stepsize, $state, $search = false, $start = false, $end = false) {
        return self::perTime("added", $stepsize, $state, $search, $start, $end);
    }

    public static function deletedPerTime($stepsize, $state, $search = false, $start = false, $end = false) {
        if ($search) {
            die("deletedPerTime doesn't support searching!");
        }
        return self::perTime("deleted", $stepsize, $state, $search, $start, $end);
    }

    public static function totalTimeSpent($start = false, $end = false) {
        if ($start === false) {
            $start = Read::getFirstArticleTime();
        }
        if ($end === false) {
            $end = time();
        }

        $wordcount = DB::queryFirstField("SELECT sum(`wordcount`) FROM `read` WHERE `archived` = 1 AND `time` BETWEEN %s AND %s", $start, $end);

        return TextExtractor::computeErt($wordcount);
    }

    // compute longest streak (largest range of days on which at least one article
    // was read)
    public static function longestStreak($start, $end) {
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

    public static function currentStreak($start, $time) {
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
    // "left" (e.g. added how long ago?), "link", and "info" (all optional)
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

    // for generating the js code for most of the graphs
    public static function printGraph($id, $colors, $xs, $ys, $texts, $stacked = false) {
        global $gridColor;

        // wrap $colors, $xs, $ys and $texts in arrays if not already wrapped
        if (!is_array(current($colors))) {
            $colors = array($colors);
        }
        if (!is_array(current($xs))) {
            $xs = array($xs);
        }
        if (!is_array(current($ys))) {
            $ys = array($ys);
        }
        if (!is_array(current($texts))) {
            $texts = array($texts);
        }

        // prepare for output
        $xs = array_map(
            function($x) {
                return implode("','", $x);
            },
            $xs
        );
        $ys = array_map(
            function($y) {
                return implode(",", $y);
            },
            $ys
        );
        $texts = array_map(
            function($text) {
                return implode("','", $text);
            },
            $texts
        );

        // for each data set (i.e. index of the input arrays), output js code
        $data = "";
        for ($i = 0; $i < sizeof($colors); $i++) {
            $fillcolor = $colors[$i]["fillcolor"];
            $linecolor = $colors[$i]["linecolor"];
            $x = $xs[$i];
            $y = $ys[$i];
            $text = $texts[$i];

            if ($stacked && $i > 0) {
                $fill = "tonexty";
            } else {
                $fill = "tozeroy";
            }

            echo <<<EOF

var $id$i = {
    type: 'scatter',
    mode: 'lines',
    x: ['$x'],
    y: [$y],
    text: ['$text'],
    hoverinfo: 'text',
    fillcolor: '$fillcolor',
    fill: '$fill',
    line: {
        color: '$linecolor',
        width: 1,
    }
};

EOF;

            $data .= "$id$i, ";
        }

        echo "var {$id}Data = [$data];";

        // output layout
        echo <<<EOF

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
    showlegend: false,
};

EOF;

        // create plot
        $plotly = "Plotly.newPlot('$id', ";
        if ($stacked) {
            $plotly .= "stackedArea({$id}Data)";
        } else {
            $plotly .= "{$id}Data";
        }
        $plotly .= ", {$id}Layout, {displayModeBar: false});";
        echo $plotly;
    }
}
