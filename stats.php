<?php

$benchmarkStart = microtime(true);

// redirect if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Location: index.php?state=stats");
    exit;
}

require_once "Config.class.php";
require_once "Read.class.php";
require_once "TimeUnit.class.php";
require_once "Helper.class.php";
require_once "Statistics.class.php";

// define colors
$gridColor = "rgba(128, 128, 128, 0.1)";
$fillcolor = "rgba(128, 128, 128, 0.25)";
$linecolor = "rgba(128, 128, 128, 0.35)";
$punchcardcolor = "rgba(128, 128, 128, 0.4)";

// articles per day
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
            $currentStreakLength = $currentStreak["length"] . " days";
            $currentStreakCount = $currentStreak["count"] . " articles";
            $streakText .= "<br>Current streak: The last $currentStreakLength, with a total of $currentStreakCount.";
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
                           /*HAVING `count` > 5*/
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
    $text = Helper::getIcon($domain["domain"]) . " " . $domain["domain"];
    $link = "index.php?state=archived&s=" . $domain["domain"];
    $info = $domain["count"] . " articles";
    $domainsTable[] = array("text" => $text, "link" => $link, "info" => $info);
}

// statistics for hero text
$totalTimeSpent = Statistics::totalTimeSpent($start, $end);
$totalTime = Helper::makeTimeHumanReadable($totalTimeSpent, false, false, "day", 2);
$totalArticles = Read::getTotalArticleCount("archived", $start, $end);
$averageTimePerDay = Helper::makeTimeHumanReadable($totalTimeSpent / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24)), false, "second", "minute");
$averageArticlesPerDay = round(array_sum($days) / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24)));
$averageTimePerMonth = Helper::makeTimeHumanReadable($totalTimeSpent / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*30)), false, "minute", "hour");
$averageArticlesPerMonth = round(array_sum($days) / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*30)));
$averageTimePerYear = Helper::makeTimeHumanReadable($totalTimeSpent / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*365)), false, "hour", "day");
$averageArticlesPerYear = round(array_sum($days) / ((min($time, $end) - max(Read::getFirstArticleTime(), $start)) / (60*60*24*365)));


?>
<div class="words herotext">
    <?php $t = new TimeUnit("day"); ?>
    You've spent about <strong><?= $totalTime ?></strong> reading <strong><?= $totalArticles ?> articles</strong> <?= $periodText ?>.
    <p>On average, that's <?= $averageTimePerDay ?> (<?= $averageArticlesPerDay ?> articles) per day, <?= $averageTimePerMonth ?> (<?= $averageArticlesPerMonth ?> articles) per month, or <?= $averageTimePerYear ?> (<?= $averageArticlesPerYear ?> articles) per year. Keep it up! <?php if (Config::ICON_FONT == "emoji") { echo "💯"; } ?></p>
</div>
<div class="words">Articles per day:</div>
<div class="graph" id="days"></div>

<div class="words"><?= $streakText ?></div>

<div class="words">Cumulative articles per day:</div>
<div class="graph" id="cumulativeDays"></div>

<div class="words">Sorted articles per day:</div>
<div class="graph" id="daysSorted"></div>

<div class="words">Top <?= min(10, count($daysTable)) ?> most productive days:</div>
<?php Statistics::printTable($daysTable); ?>

<div class="words">Unread articles per day:</div>
<div class="graph" id="unread"></div>

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
    // articles per day
    <?php Statistics::printGraph("days", $daysX, $daysY, $daysText) ?>

    // cumulative articles per day
    <?php Statistics::printGraph("cumulativeDays", $cumulativeDaysX, $cumulativeDaysY, $cumulativeDaysText) ?>

    // most productive days
    <?php Statistics::printGraph("daysSorted", $daysSortedX, $daysSortedY, $daysSortedText) ?>

    // unread articles per day
    <?php Statistics::printGraph("unread", $unreadX, $unreadY, $unreadText) ?>

    // articles per week
    <?php Statistics::printGraph("weeks", $weeksX, $weeksY, $weeksText) ?>

    // articles per month
    <?php Statistics::printGraph("months", $monthsX, $monthsY, $monthsText) ?>

    // starred articles per month
    <?php Statistics::printGraph("starred", $starredX, $starredY, $starredText) ?>


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
    <?php Statistics::printGraph("domains", $domainsX, $domainsY, $domainsText) ?>
</script>
<div class="words"><div class="info"><?= "This page was generated in " . round(1000 * (microtime(true) - $benchmarkStart)) . " milliseconds." ?></div></div>
