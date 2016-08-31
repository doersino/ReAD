<?php

$benchmarkStart = microtime(true);

// TODO y ticks dependant on (=> 10 per) order of magnitude of max
// TODO stretch goal: time period (i.e. two different dates, not timestamp) selection where query bar would be, links to last month, year etc.

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
$gridcolor = "rgba(128, 128, 128, 0.1)";
$fillcolor = "rgba(128, 128, 128, 0.25)";
$linecolor = "rgba(128, 128, 128, 0.35)";
$punchcardcolor = "rgba(128, 128, 128, 0.4)";

// for printing "top 10" tables or similar
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

$daysSorted = $days;
arsort($daysSorted);
$daysTable = array();
foreach (array_slice($daysSorted, 0, 10, true) as $ts => $count) {
    $text = TimeUnit::sFormatTimeVerbose("day", $ts);
    $s = ($count == 1) ? "" : "s";
    $info = "$count article$s";
    $daysTable[] = array("text" => $text, "info" => $info);
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

// articles per month
$months = Read::getArticlesPerTime("months", "archived", false, $start, $end);
$monthsX = array_map(
    function($ts) {
        return TimeUnit::sFormatTime("month", $ts);
    },
    array_keys($months)
);
$monthsY = $months;
$monthsText = array_map(
    function($ts, $n) {
        $monthAndYear = TimeUnit::sFormatTimeVerbose("month", $ts);
        $s = ($n == 1) ? "" : "s";
        return "$n article$s in $monthAndYear";
    },
    array_keys($months),
    $monthsY
);
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
if (Config::$startOfWeek === "sun") { // shift accordingly
    $dowMap = array_map(function($x) {return $x % 7 + 1;}, $dowMap);
}
$dowVals = $dowMap;
$dowText = array_flip($dowVals);

if (Config::$hourFormat == 24) {
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

$domainsTable = array();
foreach (array_slice($domainsQuery, 0, 10) as $domain) {
    $text = Helper::getIcon($domain["domain"]) . " " . $domain["domain"];
    $link = "index.php?state=archived&s=" . $domain["domain"];
    $info = $domain["count"] . " articles";
    $domainsTable[] = array("text" => $text, "link" => $link, "info" => $info);
}

?>
<div class="words herotext">
    You've read <?= array_sum($days) ?> articles <?= ($endText === "now") ? "since $startText" : (($start === Read::getFirstArticleTime()) ? "through $endText" : "between $startText and $endText") ?>.<br>
    On average, that's <?= round(array_sum($days) / (($end - $start) / (60*60*24))) ?> articles per day, <?= round(array_sum($days) / (($end - $start) / (60*60*24*30))) ?> articles per month, or <?= round(array_sum($days) / (($end - $start) / (60*60*24*365))) ?> articles per year. Keep it up!
</div>
<div class="words">Articles per day:</div>
<div class="graph" id="days"></div>

<div class="words">Cumulative articles per day:</div>
<div class="graph" id="cumulativeDays"></div>

<div class="words">Most "productive" days:</div>
<?php printTable($daysTable); ?>

<div class="words">Articles per month:</div>
<div class="graph" id="months"></div>

<div class="words">Punch card:</div>
<div class="graph large" id="punchcard"></div>

<div class="words">Distribution of the 100 most common websites:</div>
<div class="graph" id="domains"></div>
<div class="words">Top 10 most common websites:</div>
<?php printTable($domainsTable); ?>

<script src="lib/plotly-basic.min.js"></script>
<script>
    // TODO define and use default layout with basic colors, possibly take from color settings
    var width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    var mobile = width <= 720;
    if (mobile) {
        var punchcardMargin = {l: 55, r: 0, t: 0, b: 40, pad: 0};
    } else {
        var punchcardMargin = {l: 60, r: 0, t: 0, b: 25, pad: 0};
    }

    var days = [{
        x: ['<?= implode("','", $daysX) ?>'],
        y: [<?= implode(",", $daysY) ?>],
        text: ['<?= implode("','", $daysText) ?>'],
        hoverinfo: 'text',
        mode: 'lines',
        type: 'scatter',
        fillcolor: '<?= $fillcolor ?>',
        fill: 'tozeroy',
        line: {
            color: '<?= $linecolor ?>',
            width: 1
        }
    }];

    var daysLayout = {
        xaxis: {
            range: [<?= min($daysX) . "," . max($daysX) ?>]
        },
        yaxis: {
            range: [0, <?= max($daysY) ?>]
        },
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
        xaxis: {
            //showgrid: false,
            zeroline: false,
            //dtick: 60*60*24*365.24,
            //tick0: ,
            gridcolor: '<?= $gridcolor ?>',
            type: 'date',
        },
        yaxis: {
            //showgrid: false,
            zeroline: false,
            dtick: 10,
            gridcolor: '<?= $gridcolor ?>'
        }
    };

    Plotly.newPlot('days', days, daysLayout, {displayModeBar: false});

    // ---------------------------------------------------------------------

    var cumulativeDays = [{
        x: ['<?= implode("','", $cumulativeDaysX) ?>'],
        y: [<?= implode(",", $cumulativeDaysY) ?>],
        text: ['<?= implode("','", $cumulativeDaysText) ?>'],
        hoverinfo: 'text',
        mode: 'lines',
        type: 'scatter',
        fillcolor: '<?= $fillcolor ?>',
        fill: 'tozeroy',
        line: {
            color: '<?= $linecolor ?>',
            width: 1
        }
    }];

    var cumulativeDaysLayout = {
        xaxis: {
            range: [<?= min($cumulativeDaysX) . "," . max($cumulativeDaysX) ?>]
        },
        yaxis: {
            range: [0, <?= max($cumulativeDaysY) ?>]
        },
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
        xaxis: {
            //showgrid: false,
            zeroline: false,
            //dtick: 365,
            gridcolor: '<?= $gridcolor ?>',
            type: 'date',
        },
        yaxis: {
            //showgrid: false,
            zeroline: false,
            dtick: 1000,
            gridcolor: '<?= $gridcolor ?>'
        }
    };

    Plotly.newPlot('cumulativeDays', cumulativeDays, cumulativeDaysLayout, {displayModeBar: false});

    // ---------------------------------------------------------------------

    var months = [{
        x: ['<?= implode("','", $monthsX) ?>'],
        y: [<?= implode(",", $monthsY) ?>],
        text: ['<?= implode("','", $monthsText) ?>'],
        hoverinfo: 'text',
        mode: 'lines',
        type: 'scatter',
        fillcolor: '<?= $fillcolor ?>',
        fill: 'tozeroy',
        line: {
            color: '<?= $linecolor ?>',
            width: 1
        }
    }];

    var monthsLayout = {
        xaxis: {
            range: [<?= min($monthsX) . "," . max($monthsX) ?>]
        },
        yaxis: {
            range: [0, <?= max($monthsY) ?>]
        },
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
        xaxis: {
            //showgrid: false,
            zeroline: false,
            //dtick: 12,
            gridcolor: '<?= $gridcolor ?>',
            type: 'date',
        },
        yaxis: {
            //showgrid: false,
            zeroline: false,
            dtick: 100,
            gridcolor: '<?= $gridcolor ?>'
        }
    };

    Plotly.newPlot('months', months, monthsLayout, {displayModeBar: false});

    // ---------------------------------------------------------------------

    var punchcard = [{
        x: [<?= implode(",", $punchcardX) ?>],
        y: [<?= implode(",", $punchcardY) ?>],
        text: ['<?= implode("','", $punchcardText) ?>'],
        hoverinfo: 'text',
        mode: 'markers',
        type: 'scatter',
        marker: {
            color: '<?= $punchcardcolor ?>',
            line: {color: '<?= $linecolor ?>'},
            sizemode: 'diameter',
            sizemin: 0,
            sizeref: <?= max($punchcardSize) / 50 ?> * (1440 / width), // responsive
            size: [<?= implode(",", $punchcardSize) ?>],
        }
    }];

    var punchcardLayout = {
        xaxis: {
            range: [<?= min($punchcardX) . "," . max($punchcardX) ?>]
        },
        yaxis: {
            range: [<?= min($punchcardY) . "," . max($punchcardY) ?>]
        },
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        margin: punchcardMargin,
        xaxis: {
            showgrid: false,
            zeroline: false,
            gridcolor: '<?= $gridcolor ?>',
            tickvals: [<?= implode(",", $hourVals) ?>],
            ticktext: ['<?= implode("','", $hourText) ?>'],
            tickfont: {family: 'Helvetica, Arial, sans-serif'}
        },
        yaxis: {
            showgrid: true,
            zeroline: false,
            gridcolor: '<?= $gridcolor ?>',
            tickvals: [<?= implode(",", $dowVals) ?>],
            ticktext: ['<?= implode(" ','", $dowText) ?> '],
            tickfont: {family: 'Helvetica, Arial, sans-serif'}
        }
    };

    Plotly.newPlot('punchcard', punchcard, punchcardLayout, {displayModeBar: false});

    // ---------------------------------------------------------------------

    var domains = [{
        x: [<?= implode(",", $domainsX) ?>],
        y: [<?= implode(",", $domainsY) ?>],
        text: ['<?= implode("','", $domainsText) ?>'],
        hoverinfo: 'text',
        mode: 'lines',
        type: 'scatter',
        fillcolor: '<?= $fillcolor ?>',
        fill: 'tozeroy',
        line: {
            color: '<?= $linecolor ?>',
            width: 1
        }
    }];

    var domainsLayout = {
        xaxis: {
            range: [<?= min($domainsX) . "," . max($domainsX) ?>]
        },
        yaxis: {
            range: [0, <?= max($domainsY) ?>]
        },
        plot_bgcolor: 'rgba(0,0,0,0)',
        paper_bgcolor: 'rgba(0,0,0,0)',
        margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
        xaxis: {
            //showgrid: false,
            zeroline: false,
            dtick: 10,
            gridcolor: '<?= $gridcolor ?>'
        },
        yaxis: {
            //showgrid: false,
            //type: 'log',
            zeroline: false,
            dtick: 100,
            gridcolor: '<?= $gridcolor ?>'
        }
    };

    Plotly.newPlot('domains', domains, domainsLayout, {displayModeBar: false});
</script>
<div class="words"><?= "This page was generated in " . round(1000 * (microtime(true) - $benchmarkStart)) . " milliseconds." ?></div>
