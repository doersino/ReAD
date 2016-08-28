<?php

// redirect if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Location: index.php?state=stats");
    exit;
}

require_once "Config.class.php";
require_once "Read.class.php";
$totalArticleCount = Read::getTotalArticleCount();

$gridcolor = "rgba(128, 128, 128, 0.1)";
$fillcolor = "rgba(128, 128, 128, 0.2)";
$linecolor = "rgba(128, 128, 128, 0.3)";

?>

<div class="stats">
    <div class="words herotext">
        You've read <?= $totalArticleCount["archived"] ?> articles since <?= date("F Y", Read::getFirstArticleTime()) ?>.<br>
        On average, that's <?= round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24))) ?> articles per day, or <?= round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24*30))) ?> articles per month, or <?= round($totalArticleCount["archived"] / ((time() - Read::getFirstArticleTime()) / (60*60*24*365))) ?> articles per year. Keep it up!
    </div>
    <div class="words">Articles per day:</div>
    <div class="graph" id="days"></div>

    <div class="words">Cumulative articles per day:</div>
    <div class="graph" id="cumulativeDays"></div>

    <div class="words">Articles per month:</div>
    <div class="graph" id="months"></div>

    <div class="words">Punch card:</div>
    <div class="graph large" id="punchcard"></div>

    <div class="words">Most common websites (log scale, only websites with more than five articles):</div>
    <div class="graph" id="domains"></div>

    <?php
        // TODO enable user to select time range for stats, should be trivial to add to queries
        // TODO simplify ReAD::getArticlesPerTime similar to original punch card code: first init array, then fill with values
        // TODO and/or: make getArticlesPerTime return x (as a date?), y, possibly text
        // TODO tooltip text: always number of artices and date!
        // TODO find a way of styling tooltip thingy
        // TODO see how https://github.com/plotly/plotly.js/issues/877 turns out, maybe use
        // TODO stretch goal: time period selection where query bar would be, only show stats for that time period with intro text changed accordingly, links to last month, year etc.
        // TODO y ticks dependant on (=> 10 per) order of magnitude of max

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

        // ---------------------------------------------------------------------

        // url to domain function from http://stackoverflow.com/a/37334570
        $domainsQuery = DB::query("SELECT count(`id`) AS 'count',
                                          SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(url, '/', 3), '://', -1), '/', 1), '?', 1) AS 'domain'
                                     FROM `read`
                                    WHERE `archived` = 1
                                 GROUP BY `domain`
                                   HAVING `count` > 5
                                 ORDER BY `count` DESC");
        /*echo "<table>";
        foreach ($domainsQuery as $domain) {
            $count = $domain["count"];
            $domain = $domain["domain"];
            echo "<tr><td>$count</td><td><a href='index.php?state=archived&s=$domain'>$domain</a></td></tr>";
        }
        echo "</table>";*/

        $domainsX = range(1, count($domainsQuery));
        $domainsY = array_map(function($d) {return $d["count"];}, $domainsQuery);
        $domainsText = array_map(
            function($d) {
                return $d["count"] . " articles from " . $d["domain"];
            },
            $domainsQuery
        );

        // TODO list 10 most common, with social media icons

    ?>
    <script src="lib/plotly-basic.min.js"></script>
    <script>
        // TODO define and use default layout with basic colors, possibly take from color settings

        var days = [{
            x: [<?= implode(",", $daysX) ?>],
            y: [<?= implode(",", $daysY) ?>],
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
                dtick: 365,
                gridcolor: '<?= $gridcolor ?>'
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
            x: [<?= implode(",", $cumulativeDaysX) ?>],
            y: [<?= implode(",", $cumulativeDaysY) ?>],
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
                dtick: 365,
                gridcolor: '<?= $gridcolor ?>'
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
            x: [<?= implode(",", $monthsX) ?>],
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
                dtick: 12,
                gridcolor: '<?= $gridcolor ?>'
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
            text: ['<?= implode("','", array_map(function($s) {return "$s " . (($s == 1) ? "article" : "articles");}, $punchcardSize)) ?>'],
            hoverinfo: 'text',
            mode: 'markers',
            type: 'scatter',
            marker: {
                color: 'rgba(128, 128, 128, 0.3)',
                sizemode: 'diameter',
                sizemin: 0,
                sizeref: <?= max($punchcardSize)/50 ?>, // larges diameter: 50px
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
            margin: {l: 60, r: 0, t: 0, b: 30, pad: 0},
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
                type: 'log',
                zeroline: false,
                dtick: 100,
                gridcolor: '<?= $gridcolor ?>'
            }
        };

        Plotly.newPlot('domains', domains, domainsLayout, {displayModeBar: false});
    </script>
</div>
