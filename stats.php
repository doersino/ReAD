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
    <div class="graph" id="months"></div>

    <div class="words">Punch card:</div>
    <div class="graph large" id="punchcard"></div>
    <?php
        // TODO text: always date and number of artices!
        // TODO make getArticlesPerTime return x (as a date?), y, possibly text
        // TODO find a way of styling tooltip thingy
        // TODO most frequent domains
        // TODO see how https://github.com/plotly/plotly.js/issues/877 turns out, maybe use

        $days = Read::getArticlesPerTime("days", "archived");
        $daysOffset = date("z", Read::getFirstArticleTime());
        $daysX = range($daysOffset, count($days) + $daysOffset);
        $daysY = $days;

        // ---------------------------------------------------------------------

        $cumulativeDays = Read::getArticlesPerTime("days", "archived");
        $cumulativeDaysOffset = $daysOffset;
        $cumulativeDaysX = $daysX;
        $cumulativeDaysY = array();
        $accum = 0;
        foreach ($daysY as $day) {
            $accum += $day;
            $cumulativeDaysY[] = $accum;
        }

        // ---------------------------------------------------------------------

        $months = Read::getArticlesPerTime("months", "archived");
        $monthsOffset = date("n", Read::getFirstArticleTime());
        $monthsX = range($monthsOffset, count($months) + $monthsOffset);
        $monthsY = $months;
        $monthsText = array_map(
            function($month, $num) {
                $startYear = date("Y", Read::getFirstArticleTime());
                $monthAndYear = date("F Y", mktime(0, 0, 0, $month, 10, $startYear));
                return "$num articles in $monthAndYear";
            },
            $monthsX,
            $monthsY
        );

        // ---------------------------------------------------------------------

        $punchcardQuery = DB::query("SELECT count(`id`) AS 'count',
                               DATE_FORMAT(FROM_UNIXTIME(`time`), '%H') AS 'hour',
                               DATE_FORMAT(FROM_UNIXTIME(`time`), '%a') AS 'day'
                          FROM `read`
                         WHERE `archived` = 1
                      GROUP BY `hour`, `day`");

        $dowMap = array(
            'Mon' => 7,
            'Tue' => 6,
            'Wed' => 5,
            'Thu' => 4,
            'Fri' => 3,
            'Sat' => 2,
            'Sun' => 1
        );
        if (Config::$startOfWeek === "sun") {
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

        $punchcardX = array();
        $punchcardY = array();
        $punchcardSize = array();

        foreach ($punchcardQuery as $q) {
            $punchcardX[] = intval($q["hour"]);
            $punchcardY[] = $dowMap[$q["day"]];
            $punchcardSize[] = $q["count"];
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

        var months = [{
            x: [<?php echo implode(",", $monthsX); ?>],
            y: [<?php echo implode(",", $monthsY); ?>],
            text: ['<?php echo implode("','", $monthsText); ?>'],
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

        var monthsLayout = {
            xaxis: {
                range: [<?php echo min($monthsX) . "," . max($monthsX); ?>]
            },
            yaxis: {
                range: [0, <?php echo max($monthsY); ?>]
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

        Plotly.newPlot('months', months, monthsLayout, {displayModeBar: false});

        // ---------------------------------------------------------------------

        var punchcard = [{
            x: [<?php echo implode(",", $punchcardX); ?>],
            y: [<?php echo implode(",", $punchcardY); ?>],
            text: ['<?php echo implode("','", array_map(function($s) {return "$s " . (($s == 1) ? "article" : "articles");}, $punchcardSize)); ?>'],
            hoverinfo: 'text',
            mode: 'markers',
            type: 'scatter',
            marker: {
                color: 'rgba(128, 128, 128, 0.8)',
                sizemode: 'diameter',
                sizemin: 0,
                sizeref: <?php echo max($punchcardSize)/50 ?>, // larges diameter: 50px
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
            margin: {l: 60, r: 0, t: 0, b: 30, pad: 0},
            xaxis: {
                showgrid: false,
                zeroline: false,
                gridcolor: 'rgba(128, 128, 128, 0.1)',
                tickvals: [<?php echo implode(",", $hourVals); ?>],
                ticktext: ['<?php echo implode("','", $hourText); ?>'],
                tickfont: {family: 'Helvetica, Arial, sans-serif'}
            },
            yaxis: {
                showgrid: true,
                zeroline: false,
                gridcolor: 'rgba(128, 128, 128, 0.1)',
                tickvals: [<?php echo implode(",", $dowVals); ?>],
                ticktext: ['<?php echo implode(" ','", $dowText); ?> '],
                tickfont: {family: 'Helvetica, Arial, sans-serif'}
            }
        };

        Plotly.newPlot('punchcard', punchcard, punchcardLayout, {displayModeBar: false});
    </script>
</div>
