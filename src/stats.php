<?php

$benchmarkStart = microtime(true);

// redirect if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Location: ../index.php?state=stats");
    exit;
}

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DB.class.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/Read.class.php";
require_once __DIR__ . "/Statistics.class.php";
require_once __DIR__ . "/TimeUnit.class.php";

// define colors
$gridColor      = "rgba(128, 128, 128, 0.10)";
$fillcolor      = "rgba(128, 128, 128, 0.45)";
$linecolor      = "rgba(128, 128, 128, 0.65)";
$fillcolorRed   = "rgba(170,   0,   0, 0.45)";
$linecolorRed   = "rgba(170,   0,   0, 0.55)";
$fillcolorGreen = "rgba(  0, 160,   0, 0.35)";
$linecolorGreen = "rgba(  0, 160,   0, 0.45)";
$fillcolorBlue  = "rgba(  0,   0, 192, 0.45)";
$linecolorBlue  = "rgba(  0,   0, 192, 0.60)";
$punchcardcolor = "rgba(128, 128, 128, 0.60)";

$gray  = array("fillcolor" => $fillcolor, "linecolor" => $linecolor);
$red   = array("fillcolor" => $fillcolorRed, "linecolor" => $linecolorRed);
$green = array("fillcolor" => $fillcolorGreen, "linecolor" => $linecolorGreen);
$blue  = array("fillcolor" => $fillcolorBlue, "linecolor" => $linecolorBlue);

// articles per day
// also part of: added vs. archived per day
$days = Statistics::articlesPerTime("days", "archived", false, $start, $end);
$daysX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($days)
);
$daysY = $days;
$daysText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $daysY,
    array_keys($days)
);

// longest and current streak
$longestStreak = Statistics::longestStreak($start, $end);
$longestStreakStart = TimeUnit::sFormatTimeVerbose("day", $longestStreak["start"]);
$longestStreakEnd = TimeUnit::sFormatTimeVerbose("day", $longestStreak["end"]);
$longestStreakLength = $longestStreak["length"] . "-day";
$longestStreakCount = $longestStreak["count"] . " articles";
$longestStreakText = "The $longestStreakLength period from $longestStreakStart to $longestStreakEnd, with a total of $longestStreakCount.";
if ($longestStreakEnd == TimeUnit::sFormatTimeVerbose("day", $time)) {
    $streakText = "Longest (and current) streak: $longestStreakText";
} else {
    $streakText = "Longest streak: $longestStreakText";
    if ($start <= $time && $time <= $end) {
        $currentStreak = Statistics::currentStreak($start, $time);
        if ($currentStreak["length"] > 0) {
            $currentStreakLength = ($currentStreak["length"] == 1) ? "Just today" : "The last " . $currentStreak["length"] . " days";
            $currentStreakCount = $currentStreak["count"] . " articles";
            $streakText .= "<br>Current streak: $currentStreakLength, with a total of $currentStreakCount.";
        }
    }
}

// cumulative artices per day (based on articles per day)
$cumulativeDaysX = $daysX;
$cumulativeDaysY = array();
$accum = 0;
foreach ($daysY as $day) {
    $accum += $day;
    $cumulativeDaysY[] = $accum;
}
$cumulativeDaysText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s through " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $cumulativeDaysY,
    array_keys($days)
);

// most productive days
$daysSorted = $days;
arsort($daysSorted);

$daysSortedX = $daysX; //range(1, count($daysSorted));
$daysSortedY = $daysSorted;
$daysSortedText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $daysSortedY,
    array_keys($daysSorted)
);

// top 10 most productive days
$daysTable = array();
foreach (array_slice($daysSorted, 0, 10, true) as $ts => $count) {
    $text = TimeUnit::sFormatTimeVerbose("day", $ts);
    $s = ($count == 1) ? "" : "s";
    $info = "$count article$s";
    $daysTable[] = array("text" => $text, "info" => $info);
}

// estimated reading time per day
$daysERT = Statistics::wordcountPerTime("days", "archived", false, $start, $end);
$daysERTX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($daysERT)
);
$daysERTY = array_map(
    function($wordcount) {
        return TextExtractor::computeErt($wordcount);
    },
    $daysERT
);
$daysERTText = array_map(
    function($n, $ts) {
        $n = Helper::makeTimeHumanReadable($n, false, false, false, 2);
        return "$n on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $daysERTY,
    array_keys($daysERT)
);

// average article length per day
$daysAvgLen = array_map(
    function($n, $wordcount) {
        if ($n == 0) {
            return 0;
        }
        return $wordcount / $n;
    },
    $days,
    $daysERT
);
$daysAvgLenX = $daysX;
$daysAvgLenY = $daysAvgLen;
$daysAvgLenText = array_map(
    function($n, $ts) {
        $n = round($n);
        return "$n words on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $daysAvgLenY,
    array_keys($days)
);

// top 10 longest articles
// TODO merge this with getArticles()/getSearchResults()/code in index.php
// TODO also good idea: use showTable() for main table in index.php etc.
$ertQuery = DB::query("SELECT `id`, `url`, `title`, `wordcount`, `time`
                         FROM `read`
                        WHERE `archived` = 1
                          AND `time` BETWEEN %s AND %s
                     ORDER BY `wordcount` DESC
                        LIMIT 10", $start, $end);

$ertTable = array();
foreach ($ertQuery as $article) {

    $article["url"] = htmlspecialchars($article["url"], ENT_QUOTES, "UTF-8");
    if (empty($article["title"])) {
        $article["title"] = "<span class=\"notitle\">No title found.</span>";
    } else {
        $article["title"] = str_replace(array("<", ">"), array("&lt;", "&gt;"), $article["title"]);
    }

    $left = Helper::ago($article["time"], true);
    $text = $article["title"];
    $link = $article["url"];
    $info = "<a href=\"index.php?state=archived&amp;s=" . rawurlencode(Helper::getHost($article["url"])) . "\">" . Helper::getHost($article["url"]) . "</a> · <abbr class=\"ertlabel\">ERT</abbr> <abbr title=\"" . $article["wordcount"] . " words\">" . Helper::makeTimeHumanReadable(TextExtractor::computeErt($article["wordcount"]), true, "minute", "minute") . "</abbr>";
    $ertTable[] = array("left" => $left,
                            "text" => $text,
                            "link" => $link,
                            "info" => $info);
}

// unread articles per day
$unread = Statistics::articlesPerTime("days", "unread", false, $start, $end);
$unreadX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($unread)
);
$unreadY = $unread;
$unreadText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $unreadY,
    array_keys($unread)
);

// TODO implement Statistics::quotesPerTime
/*
// quotes added per day
$quotes = Statistics::quotesPerTime("days", "unread", false, $start, $end);
$quotesX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($unread)
);
$quotesY = $quotes;
$quotesText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n quote$s on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $quotesY,
    array_keys($quotes)
);
*/

// added vs. archived per day
$daysAdded = Statistics::addedPerTime("days", "all", false, $start, $end);
$daysAddedX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($daysAdded)
);
$daysAddedY = $daysAdded;
$daysAddedText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s on " . TimeUnit::sFormatTimeVerbose("day", $ts);
    },
    $daysAddedY,
    array_keys($daysAdded)
);

// articles per week
$weeks = Statistics::articlesPerTime("weeks", "archived", false, $start, $end);
$weeksX = array_map(
    function($ts) {
        // "day" because plot.js doesn't understand yyyy-ww
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($weeks)
);
$weeksY = $weeks;
$weeksText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s in " . TimeUnit::sFormatTimeVerbose("week", $ts);
    },
    $weeksY,
    array_keys($weeks)
);

// articles per month
$months = Statistics::articlesPerTime("months", "archived", false, $start, $end);
$monthsX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($months)
);
$monthsY = $months;
$monthsText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s in " . TimeUnit::sFormatTimeVerbose("month", $ts);
    },
    $monthsY,
    array_keys($months)
);

// starred articles per month
$starred = Statistics::articlesPerTime("months", "starred", false, $start, $end);
$starredX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("day", $ts);
    },
    array_keys($starred)
);
$starredY = $starred;
$starredText = array_map(
    function($n, $ts) {
        $s = ($n == 1) ? "" : "s";
        return "$n article$s in " . TimeUnit::sFormatTimeVerbose("month", $ts);
    },
    $starredY,
    array_keys($starred)
);

// punch card
$punchcardQuery = DB::query("SELECT count(`id`) AS 'count',
                                    DATE_FORMAT(FROM_UNIXTIME(`time`), '%H') AS 'hour',
                                    DATE_FORMAT(FROM_UNIXTIME(`time`), '%a') AS 'day'
                              FROM `read`
                             WHERE `archived` = 1
                               AND `time` BETWEEN %s AND %s
                          GROUP BY `hour`, `day`", $start, $end);

$dowMap = array(
    'Mon' => 7,
    'Tue' => 6,
    'Wed' => 5,
    'Thu' => 4,
    'Fri' => 3,
    'Sat' => 2,
    'Sun' => 1
);
if (Config::START_OF_WEEK === "sun") { // shift accordingly
    $dowMap = array_map(function($x) {return $x % 7 + 1;}, $dowMap);
}
$dowVals = $dowMap;
$dowText = array_flip($dowVals);

if (Config::HOUR_FORMAT == 24) {
    $hourText = array("00:00", "01:00", "02:00", "03:00", "04:00", "05:00", "06:00", "07:00", "08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", "18:00", "19:00", "20:00", "21:00", "22:00", "23:00");
} else {
    $hourText = array("12 AM", "1 AM", "2 AM", "3 AM", "4 AM", "5 AM", "6 AM", "7 AM", "8 AM", "9 AM", "10 AM", "11 AM", "12 PM", "1 PM", "2 PM", "3 PM", "4 PM", "5 PM", "6 PM", "7 PM", "8 PM", "9 PM", "10 PM", "11 PM");
}
$hourVals = array_flip($hourText);

$dowMap2 = array(
    'Mon' => 'Monday',
    'Tue' => 'Tuesday',
    'Wed' => 'Wednesday',
    'Thu' => 'Thursday',
    'Fri' => 'Friday',
    'Sat' => 'Saturday',
    'Sun' => 'Sunday'
);

$punchcardX = array();
$punchcardY = array();
$punchcardSize = array();
$punchcardText = array();
foreach ($punchcardQuery as $q) {
    $punchcardX[] = intval($q["hour"]);
    $punchcardY[] = $dowMap[$q["day"]];
    $punchcardSize[] = $q["count"];
    $s = ($q["count"] == 1) ? "" : "s";
    $punchcardText[] = $q["count"] . " article$s on " . $dowMap2[$q["day"]] . "s at " . $hourText[intval($q["hour"])];
}

// most common websites
// url to domain function from http://stackoverflow.com/a/37334570
$domainsQuery = DB::query("SELECT count(`id`) AS 'count',
                                  SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(url, '/', 3), '://', -1), '/', 1), '?', 1) AS 'domain'
                             FROM `read`
                            WHERE `archived` = 1
                              AND `time` BETWEEN %s AND %s
                         GROUP BY `domain`
                         ORDER BY `count` DESC
                            LIMIT 100", $start, $end);

$domainsX = range(1, count($domainsQuery));
$domainsY = array_map(function($d) {return $d["count"];}, $domainsQuery);
$domainsText = array_map(
    function($d) {
        $s = ($d["count"] == 1) ? "" : "s";
        return $d["count"] . " article$s from " . $d["domain"];
    },
    $domainsQuery
);

// top 10 most common websites
$domainsTable = array();
foreach (array_slice($domainsQuery, 0, 10) as $domain) {
    $text = $domain["domain"];
    $link = "index.php?state=archived&s=" . $domain["domain"];
    $info = $domain["count"] . " articles";
    $domainsTable[] = array("text" => $text, "link" => $link, "info" => $info);
}

// statistics for hero text
$totalTimeSpent = Statistics::totalTimeSpent($start, $end);
$totalTime = Helper::makeTimeHumanReadable($totalTimeSpent, false, false, "day", 2);
$totalArticles = Read::getTotalArticleCount("archived", false, $start, $end);
$averageTimePerDay = Helper::makeTimeHumanReadable($totalTimeSpent / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24)), false, "second", "minute");
$averageArticlesPerDay = round(array_sum($days) / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24)));
$averageTimePerMonth = Helper::makeTimeHumanReadable($totalTimeSpent / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*30)), false, "minute", "hour");
$averageArticlesPerMonth = round(array_sum($days) / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*30)));
$averageTimePerYear = Helper::makeTimeHumanReadable($totalTimeSpent / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*365)), false, "hour", "day");
$averageArticlesPerYear = round(array_sum($days) / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*365)));
$wakingTimeReading = round(1000 * ($totalTimeSpent / ((min($time, $end) - $start) * ((24 - Config::HOURS_OF_SLEEP) / 24)))) / 10;


?>
<div class="words herotext">
    <?php $t = new TimeUnit("day"); ?>
    You've spent about <strong><?= $totalTime ?></strong> reading <strong><?= $totalArticles ?> articles</strong> <?= $periodText ?>.
    <p>On average, that's <?= $averageArticlesPerDay ?> articles or <?= $averageTimePerDay ?> per day, <?= $averageArticlesPerMonth ?> articles or <?= $averageTimePerMonth ?> per month, and <?= $averageArticlesPerYear ?> articles or <?= $averageTimePerYear ?> per year. That means you've spent about <?= $wakingTimeReading ?>% of your waking time reading. Keep it up!</p>
</div>
<div class="words">Articles per day:</div>
<div class="graph" id="days"></div>

<div class="words"><?= $streakText ?></div>

<div class="words">Cumulative articles per day:</div>
<div class="graph" id="cumulativeDays"></div>

<div class="words">Sorted articles per day:</div>
<div class="graph" id="daysSorted"></div>

<div class="words">Top <?= min(10, count($daysTable)) ?> most productive days by articles:</div>
<?php Statistics::printTable($daysTable); ?>

<div class="words">Estimated reading time per day:</div>
<div class="graph" id="daysERT"></div>

<div class="words">Average article length per day:</div>
<div class="graph" id="daysAvgLen"></div>

<div class="words">Top <?= min(10, count($ertTable)) ?> longest articles:</div>
<?php Statistics::printTable($ertTable); ?>

<div class="words">Unread articles per day:</div>
<div class="graph" id="unread"></div>

<div class="words"><span style="color: red;">Added</span> vs. <span style="color: green">archived</span> articles per day:</div>
<div class="graph" id="addedvsarchived"></div>

<div class="words">Articles per week:</div>
<div class="graph" id="weeks"></div>

<div class="words">Articles per month:</div>
<div class="graph" id="months"></div>

<div class="words">Starred articles per month:</div>
<div class="graph" id="starred"></div>

<div class="words">Punch card:</div>
<div class="graph large" id="punchcard"></div>

<div class="words">Distribution of the <?= min(100, count($domainsX)) ?> most common websites:</div>
<div class="graph" id="domains"></div>
<div class="words">Top <?= min(10, count($domainsTable)) ?> most common websites:</div>
<?php Statistics::printTable($domainsTable); ?>

<script src="deps/plotly-basic.min.js"></script>
<script>
    // via https://plot.ly/javascript/filled-area-plots/
    function stackedArea(traces) {
        for(var i=1; i<traces.length; i++) {
            for(var j=0; j<(Math.min(traces[i]['y'].length, traces[i-1]['y'].length)); j++) {
                traces[i]['y'][j] += traces[i-1]['y'][j];
            }
        }
        return traces;
    }

    // articles per day
    <?php Statistics::printGraph("days", $gray, $daysX, $daysY, $daysText) ?>

    // cumulative articles per day
    <?php Statistics::printGraph("cumulativeDays", $gray, $cumulativeDaysX, $cumulativeDaysY, $cumulativeDaysText) ?>

    // sorted articles per day
    <?php Statistics::printGraph("daysSorted", $gray, $daysSortedX, $daysSortedY, $daysSortedText) ?>

    // estimated reading time per day
    <?php Statistics::printGraph("daysERT", $gray, $daysERTX, $daysERTY, $daysERTText) ?>

    // average article length per day
    <?php Statistics::printGraph("daysAvgLen", $gray, $daysAvgLenX, $daysAvgLenY, $daysAvgLenText) ?>

    // unread articles per day
    <?php Statistics::printGraph("unread", $gray, $unreadX, $unreadY, $unreadText) ?>

    // added vs. archived per day
    <?php Statistics::printGraph(
        "addedvsarchived",
        array($red, $green),
        array($daysAddedX, $daysX),
        array($daysAddedY, $daysY),
        array($daysAddedText, $daysText),
        false
    ) ?>

    /*
    // quotes created per day
    <?php Statistics::printGraph("quotes", $gray, $quotesX, $quotesY, $quotesText) ?>
    */

    // articles per week
    <?php Statistics::printGraph("weeks", $gray, $weeksX, $weeksY, $weeksText) ?>

    // articles per month
    <?php Statistics::printGraph("months", $gray, $monthsX, $monthsY, $monthsText) ?>

    // starred articles per month
    <?php Statistics::printGraph("starred", $gray, $starredX, $starredY, $starredText) ?>

    // punch card
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    var mobile = width <= 720;
    if (mobile) {
        var punchcardMargin = {l: 55, r: 0, t: 0, b: 40, pad: 0};
    } else {
        var punchcardMargin = {l: 60, r: 0, t: 0, b: 25, pad: 0};
    }

    var punchcard = [{
        type: 'scatter',
        mode: 'markers',
        x: [<?= implode(",", $punchcardX) ?>],
        y: [<?= implode(",", $punchcardY) ?>],
        text: ['<?= implode("','", $punchcardText) ?>'],
        hoverinfo: 'text',
        marker: {
            color: '<?= $punchcardcolor ?>',
            line: {color: '<?= $linecolor ?>'},
            sizemode: 'diameter',
            sizemin: 0,
            sizeref: <?= max($punchcardSize) / 50 ?> * (1440 / width), // responsive
            size: [<?= implode(",", $punchcardSize) ?>],
        },
    }];

    var punchcardLayout = {
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        margin: punchcardMargin,
        xaxis: {
            showgrid: false,
            zeroline: false,
            gridcolor: '<?= $gridColor ?>',
            tickvals: [<?= implode(",", $hourVals) ?>],
            ticktext: ['<?= implode("','", $hourText) ?>'],
            tickfont: {family: 'Helvetica, Arial, sans-serif'},
        },
        yaxis: {
            showgrid: true,
            zeroline: false,
            gridcolor: '<?= $gridColor ?>',
            tickvals: [<?= implode(",", $dowVals) ?>],
            ticktext: ['<?= implode(" ','", $dowText) ?> '],
            tickfont: {family: 'Helvetica, Arial, sans-serif'},
        },
    };

    Plotly.newPlot('punchcard', punchcard, punchcardLayout, {displayModeBar: false});

    // most common websites
    <?php Statistics::printGraph("domains", $gray, $domainsX, $domainsY, $domainsText) ?>
</script>

<!-- Runtime (for debugging performance): <?= round(1000 * (microtime(true) - $benchmarkStart)) . "ms" ?> -->
