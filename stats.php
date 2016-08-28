<?php

// redirect if this file is called directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Location: index.php?state=stats");
    exit;
}

require_once "Read.class.php";

$totalArticleCount = Read::getTotalArticleCount();

$days = Read::getArticlesPerTime("days", "archived");
$offset = date("z", Read::getFirstArticleTime());
$daysX = range($offset, count($days) + $offset);
$daysY = $days;
// TODO text: date!

?>

<div class="stats">
    <script src="lib/plotly-basic.min.js"></script>
    <div class="words first">You've read <?php echo $totalArticleCount["archived"]; ?> articles since <?php echo date("F Y", Read::getFirstArticleTime()); ?>.
    On average, that's <?php echo round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24))); ?> articles per day. Here's how many you've actually read every single day:</div>
    <div id="days"></div>
    <script>
        var trace1 = {
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
        };

        var data = [trace1];

        var layout = {
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
            height: 280
        };

        Plotly.newPlot('days', data, layout, {displayModeBar: false});
    </script>
    <div class="words">Per month, that's <?php echo round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24*30))); ?>, or <?php echo round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24*365))); ?> per year. Keep it up!</div>
    <div id="articlespermonth"></div>
    <?php
        // TODO make getArticlesPerTime return x, y, possibly text
        // TODO find a way of styling tooltip thingy
        // TODO per day of week, per hour of day => punchcard-style (using GROUP BY dow, hod, then making sure there are no gaps)
        $articlesPerMonth = Read::getArticlesPerTime("months", "archived");
        $monthsUntilFirstArticle = date("n", Read::getFirstArticleTime());
        $x = range($monthsUntilFirstArticle, count($articlesPerMonth) + $monthsUntilFirstArticle);
        $y = $articlesPerMonth;
        $text = array_map(function($n) {return $n . " articles in " . date('F', mktime(0, 0, 0, $n, 10));}, $x); // TODO fix, add year
    ?>
    <script>
        var trace1 = {
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
        };

        var data = [trace1];

        var layout = {
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

        Plotly.newPlot('articlespermonth', data, layout, {displayModeBar: false});
    </script>
    <div class="words">Punch card:</div>
    <?php
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
    <div id="punchcard"></div>
    <script>
        var trace1 = {
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
        };

        var data = [trace1];

        var layout = {
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

        Plotly.newPlot('punchcard', data, layout, {displayModeBar: false});
    </script>
</div>
