<?php

// redirect if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Location: index.php?state=stats");
    exit;
}

require_once "Read.class.php";
$totalArticleCount = Read::getTotalArticleCount();

?>

<div class="stats">
    <div class="words">You've read <?php echo $totalArticleCount["archived"]; ?> articles since <?php echo date("F Y", Read::getFirstArticleTime()); ?>.
    On average, that's <?php echo round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24))); ?> articles per day. Here's how many you've actually read every single day:</div>
    <div class="graph" id="days"></div>

    <div class="words">Cumulatively, that looks like so:</div>
    <div class="graph" id="cumulativeDays"></div>

    <div class="words">Per month, that's <?php echo round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24*30))); ?>, or <?php echo round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24*365))); ?> per year. Keep it up!</div>
    <div class="graph" id="articlespermonth"></div>

    <div class="words">Punch card:</div>
    <div class="graph large" id="punchcard"></div>
    <?php
        // TODO text: always date and number of artices!
        // TODO make getArticlesPerTime return x (as a date?), y, possibly text
        // TODO find a way of styling tooltip thingy

        $days = Read::getArticlesPerTime("days", "archived");
        $offset = date("z", Read::getFirstArticleTime());
        $daysX = range($offset, count($days) + $offset);
        $daysY = $days;

        // ---------------------------------------------------------------------

        $cumulativeDays = Read::getArticlesPerTime("days", "archived");
        $cumulativeDaysOffset = $offset;
        $cumulativeDaysX = $daysX;
        $cumulativeDaysY = array();
        $accum = 0;
        foreach ($days as $day) {
            $accum += $day;
            $cumulativeDaysY[] = $accum;
        }

        // ---------------------------------------------------------------------

        // TODO rename to match naming scheme
        $articlesPerMonth = Read::getArticlesPerTime("months", "archived");
        $monthsUntilFirstArticle = date("n", Read::getFirstArticleTime());
        $x = range($monthsUntilFirstArticle, count($articlesPerMonth) + $monthsUntilFirstArticle);
        $y = $articlesPerMonth;
        $text = array_map(function($n) {return $n . " articles in " . date('F', mktime(0, 0, 0, $n, 10));}, $x); // TODO fix, add year

        // ---------------------------------------------------------------------

        $q = DB::query("SELECT count(`id`) AS 'count',
                               DATE_FORMAT(FROM_UNIXTIME(`time`), '%H') AS 'hour',
                               DATE_FORMAT(FROM_UNIXTIME(`time`), '%a') AS 'day'
                          FROM `read`
                         WHERE `archived` = 1
                      GROUP BY `day`, `hour`");

        // TODO make sure to follow beginning of week setting
        $dowMap = array('Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7);

        $punchcardX = array();
        $punchcardY = array();
        $punchcardSize = array();
        foreach ($q as $q1) {
            $punchcardX[] = intval($q1["hour"]);
            $punchcardY[] = $dowMap[$q1["day"]];
            $punchcardSize[] = $q1["count"];
        }

    ?>
    <script src="lib/plotly-basic.min.js"></script>
    <script>
        // TODO define and use default layout with basic colors, possibly take from color settings

        var days = [{
            x: [<?php echo implode(",", $daysX); ?>],
            y: [<?php echo implode(",", $daysY); ?>],
            mode: 'lines',
            type: 'scatter',
            fillcolor: 'rgba(128, 128, 128, 0.2)',
            fill: 'tozeroy',
            line: {
                color: 'rgba(128, 128, 128, 0.3)',
                width: 1
            }
        }];

        var daysLayout = {
            xaxis: {
                range: [<?php echo min($daysX) . "," . max($daysX); ?>]
            },
            yaxis: {
                range: [0, <?php echo max($daysY); ?>]
            },
            plot_bgcolor: 'rgba(0,0,0,0)',
            paper_bgcolor: 'rgba(0,0,0,0)',
            margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
            xaxis: {
                //showgrid: false,
                zeroline: false,
                dtick: 365,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            yaxis: {
                //showgrid: false,
                zeroline: false,
                dtick: 10,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            //height: 280
        };

        Plotly.newPlot('days', days, daysLayout, {displayModeBar: false});

        // ---------------------------------------------------------------------

        var cumulativeDays = [{
            x: [<?php echo implode(",", $cumulativeDaysX); ?>],
            y: [<?php echo implode(",", $cumulativeDaysY); ?>],
            mode: 'lines',
            type: 'scatter',
            fillcolor: 'rgba(128, 128, 128, 0.2)',
            fill: 'tozeroy',
            line: {
                color: 'rgba(128, 128, 128, 0.3)',
                width: 1
            }
        }];

        var cumulativeDaysLayout = {
            xaxis: {
                range: [<?php echo min($cumulativeDaysX) . "," . max($cumulativeDaysX); ?>]
            },
            yaxis: {
                range: [0, <?php echo max($cumulativeDaysY); ?>]
            },
            plot_bgcolor: 'rgba(0,0,0,0)',
            paper_bgcolor: 'rgba(0,0,0,0)',
            margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
            xaxis: {
                //showgrid: false,
                zeroline: false,
                dtick: 365,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            yaxis: {
                //showgrid: false,
                zeroline: false,
                dtick: 1000,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            //height: 280
        };

        Plotly.newPlot('cumulativeDays', cumulativeDays, cumulativeDaysLayout, {displayModeBar: false});

        // ---------------------------------------------------------------------

        var articlespermonth = [{
            x: [<?php echo implode(",", $x); ?>],
            y: [<?php echo implode(",", $y); ?>],
            text: ['<?php echo implode("','", $text); ?>'],
            hoverinfo: 'text',
            mode: 'lines',
            type: 'scatter',
            fillcolor: 'rgba(128, 128, 128, 0.2)',
            fill: 'tozeroy',
            line: {
                color: 'rgba(128, 128, 128, 0.3)',
                width: 1
            }
        }];

        var articlespermonthLayout = {
            xaxis: {
                range: [<?php echo min($x) . "," . max($x); ?>]
            },
            yaxis: {
                range: [0, <?php echo max($y); ?>]
            },
            plot_bgcolor: 'rgba(0,0,0,0)',
            paper_bgcolor: 'rgba(0,0,0,0)',
            margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
            xaxis: {
                //showgrid: false,
                zeroline: false,
                dtick: 12,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            yaxis: {
                //showgrid: false,
                zeroline: false,
                dtick: 100,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            height: 280
        };

        Plotly.newPlot('articlespermonth', articlespermonth, articlespermonthLayout, {displayModeBar: false});

        // ---------------------------------------------------------------------

        var punchcard = [{
            x: [<?php echo implode(",", $punchcardX); ?>],
            y: [<?php echo implode(",", $punchcardY); ?>],
            mode: 'markers',
            type: 'scatter',
            marker: {
                color: 'rgba(128, 128, 128, 0.8)',
                sizemode: 'diameter',
                sizemin: 0,
                sizeref: <?php echo max($punchcardSize)/50 ?>,
                size: [<?php echo implode(",", $punchcardSize); ?>],
            }
        }];

        var punchcardLayout = {
            xaxis: {
                range: [<?php echo min($punchcardX) . "," . max($punchcardX); ?>]
            },
            yaxis: {
                range: [<?php echo min($punchcardY) . "," . max($punchcardY); ?>]
            },
            plot_bgcolor: 'rgba(0,0,0,0)',
            paper_bgcolor: 'rgba(0,0,0,0)',
            margin: {l: 20, r: 20, t: 20, b: 20, pad: 0},
            xaxis: {
                showgrid: false,
                zeroline: false,
                dtick: 1,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            yaxis: {
                showgrid: false,
                zeroline: false,
                //dtick: 10,
                gridcolor: 'rgba(128, 128, 128, 0.1)'
            },
            height: 420
        };

        Plotly.newPlot('punchcard', punchcard, punchcardLayout, {displayModeBar: false});
    </script>
</div>
