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

// define colors
$gridColor = "rgba(128, 128, 128, 0.1)";
$fillcolor = "rgba(128, 128, 128, 0.25)";
$linecolor = "rgba(128, 128, 128, 0.35)";
$punchcardcolor = "rgba(128, 128, 128, 0.4)";

// for printing "top 10" tables (without actions!) or similar
// each element of the array must be an associative array with indices "text"
// (required), "left", "link", and "info" (all optional)
function printTable($array) {
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

// compute longest streak, i.e. largest range of days on which at least one
// article was read
function longestStreak($start, $end, $lull = false) {
    $articles = Read::getArticlesPerTime("days", "archived", false, $start, $end);
    $emptyStreak   = array("start" => 0, "end" => 0, "length" => 0, "count" => 0);
    $longestStreak = $emptyStreak;
    $currentStreak = $emptyStreak;
    $currentStreak["start"] = key($articles);

    foreach ($articles as $day => $count) {
        if (!$lull && $count == 0 || $lull && $count != 0) {
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

// articles per day
$days = Read::getArticlesPerTime("days", "archived", false, $start, $end);
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

// longest streak
$streak = longestStreak($start, $end);
$streakStart = TimeUnit::sFormatTimeVerbose("day", $streak["start"]);
$streakEnd = TimeUnit::sFormatTimeVerbose("day", $streak["end"]);
$streakLength = $streak["length"] . "-day";
$streakCount = $streak["count"] . " articles";
$streakText = "The $streakLength period from $streakStart to $streakEnd, with a total of $streakCount.";

// longest lull
$lull = longestStreak($start, $end, true);
if ($lull["start"] = 0) {
    $lullText = "No lull — that means you've read an article every day!";
} else {
    $lullStart = TimeUnit::sFormatTimeVerbose("day", $lull["start"]);
    $lullEnd = TimeUnit::sFormatTimeVerbose("day", $lull["end"]);
    $lullLength = $lull["length"] . "-day";
    $lullText = "The $lullLength period from $lullStart to $lullEnd.";
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

$daysSortedX = range(1, count($daysSorted));
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

// articles per week
$weeks = Read::getArticlesPerTime("weeks", "archived", false, $start, $end);
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
$months = Read::getArticlesPerTime("months", "archived", false, $start, $end);
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

?>
<div class="words herotext">
    <?php $t = new TimeUnit("day"); ?>
    You've read <?= array_sum($days) ?> articles <?= $t->sameTime($end, time()) ? "since $startText" : (($t->sameTime($start, Read::getFirstArticleTime())) ? "through $endText" : "between $startText and $endText") ?>.
    <!-- TODO improve navigation (e.g. have second-level nav bar in place of and with same color scheme as query bar, or something even fancier) -->
    <a id="changeintervallink" href="javascript:void(0)" onclick="document.getElementById('changeinterval').style.display = 'inline'; document.getElementById('changeintervallink').style.display = 'none';">Change...</a><br>
    <span id="changeinterval" style="display: none;">
        See statistics for the
        <?php if ($endText === "now") { ?>
            last
            <a href="index.php?state=stats&amp;start=<?= strtotime("-30 days", $end) ?>&amp;end=<?= $end ?>">30 days</a>,
            <a href="index.php?state=stats&amp;start=<?= strtotime("-90 days", $end) ?>&amp;end=<?= $end ?>">90 days</a> or
            <a href="index.php?state=stats&amp;start=<?= strtotime("-1 year", $end) ?>&amp;end=<?= $end ?>">year</a>.
        <?php } else { ?>
            previous
            <a href="index.php?state=stats&amp;start=<?= strtotime("-30 days", $start) ?>&amp;end=<?= $start ?>">30 days</a>,
            <a href="index.php?state=stats&amp;start=<?= strtotime("-90 days", $start) ?>&amp;end=<?= $start ?>">90 days</a> or
            <a href="index.php?state=stats&amp;start=<?= strtotime("-1 year", $start) ?>&amp;end=<?= $start ?>">year</a>,
            or the next
            <a href="index.php?state=stats&amp;start=<?= $end ?>&amp;end=<?= strtotime("+30 days", $end) ?>">30 days</a>,
            <a href="index.php?state=stats&amp;start=<?= $end ?>&amp;end=<?= strtotime("+90 days", $end) ?>">90 days</a> or
            <a href="index.php?state=stats&amp;start=<?= $end ?>&amp;end=<?= strtotime("+1 year", $end) ?>">year</a>.
            <!-- TODO only if start, end not already min, max based on day -->
        <?php } ?>
        <br>
    </span>
    On average, that's <?= round(array_sum($days) / (($end - $start) / (60*60*24))) ?> articles per day, <?= round(array_sum($days) / (($end - $start) / (60*60*24*30))) ?> articles per month, or <?= round(array_sum($days) / (($end - $start) / (60*60*24*365))) ?> articles per year. Keep it up!
</div>
<div class="words">Articles per day:</div>
<div class="graph" id="days"></div>

<div class="words">Longest streak: <?= $streakText ?><br>Longest lull: <?= $lullText ?></div>

<div class="words">Cumulative articles per day:</div>
<div class="graph" id="cumulativeDays"></div>

<div class="words">Sorted articles per day:</div>
<div class="graph" id="daysSorted"></div>

<div class="words">Top <?= min(10, count($daysTable)) ?> most productive days:</div>
<?php printTable($daysTable); ?>

<div class="words">Articles per week:</div>
<div class="graph" id="weeks"></div>

<div class="words">Articles per month:</div>
<div class="graph" id="months"></div>

<div class="words">Punch card:</div>
<div class="graph large" id="punchcard"></div>

<div class="words">Distribution of the <?= min(100, count($domainsX)) ?> most common websites:</div>
<div class="graph" id="domains"></div>
<div class="words">Top <?= min(10, count($domainsTable)) ?> most common websites:</div>
<?php printTable($domainsTable); ?>

<script src="lib/plotly-basic.min.js"></script>
<script>
    // articles per day
    <?php printGraph("days", $daysX, $daysY, $daysText) ?>

    // cumulative articles per day
    <?php printGraph("cumulativeDays", $cumulativeDaysX, $cumulativeDaysY, $cumulativeDaysText) ?>

    // most productive days
    <?php printGraph("daysSorted", $daysSortedX, $daysSortedY, $daysSortedText) ?>

    // articles per week
    <?php printGraph("weeks", $weeksX, $weeksY, $weeksText) ?>

    // articles per month
    <?php printGraph("months", $monthsX, $monthsY, $monthsText) ?>


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
    <?php printGraph("domains", $domainsX, $domainsY, $domainsText) ?>
</script>
<div class="words"><div class="info"><?= "This page was generated in " . round(1000 * (microtime(true) - $benchmarkStart)) . " milliseconds." ?></div></div>
