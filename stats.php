<?php

// die if this file is called directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die();
}

require_once "Read.class.php";

$q = DB::query("SELECT count(`id`) AS 'count',
                       DATE_FORMAT(FROM_UNIXTIME(`time`), '%H') AS 'hour',
                       DATE_FORMAT(FROM_UNIXTIME(`time`), '%a') AS 'day'
                  FROM `read`
                 WHERE `archived` = 1
              GROUP BY `day`, `hour`");
//var_dump($q);
$a = array(
    "Mon" => array(),
    "Tue" => array(),
    "Wed" => array(),
    "Thu" => array(),
    "Fri" => array(),
    "Sat" => array(),
    "Sun" => array()
);
foreach ($a as $day => $dayArr) {
    $a[$day] = array("00" => 0, "01" => 0, "02" => 0, "03" => 0, "04" => 0, "05" => 0, "06" => 0, "07" => 0, "08" => 0, "09" => 0, "10" => 0, "11" => 0, "12" => 0, "13" => 0, "14" => 0, "15" => 0, "16" => 0, "17" => 0, "18" => 0, "19" => 0, "20" => 0, "21" => 0, "22" => 0, "23" => 0);
};
foreach ($q as $q1) {
    //var_dump($q1);
    //echo "<br>";
    $day = $q1["day"];
    $hour = $q1["hour"];
    $a[$day][$hour] = $q1["count"];
}
//var_dump($a);
?>
<table>
<tr>
    <td></td><td>00</td><td>01</td><td>02</td><td>03</td><td>04</td><td>05</td><td>06</td><td>07</td><td>08</td><td>09</td><td>10</td><td>11</td><td>12</td><td>13</td><td>14</td><td>15</td><td>16</td><td>17</td><td>18</td><td>19</td><td>20</td><td>21</td><td>22</td><td>23</td>
</tr>
<?php
foreach ($a as $day => $dayVal) {
    echo "<tr>";
    echo "<td>$day</td>";
    foreach ($dayVal as $hour => $hourVal) {
        echo "<td style='background-color: rgba(0,0,0," . $hourVal / 100 . ")'>$hourVal</td>";
    }

    echo "</tr>";
}
?>
</table>
<?php

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
        // TODO make getArticlesPerTime return x, y, text
        // TODO find a way of styling tooltip thingy
        // TODO per day of week, per hour of day => punchcard-style (using GROUP BY dow, hod, then making sure there are no gaps)
        $articlesPerMonth = Read::getArticlesPerTime("months", "archived");
        $monthsUntilFirstArticle = date("n", Read::getFirstArticleTime());
        $x = range($monthsUntilFirstArticle, count($articlesPerMonth) + $monthsUntilFirstArticle);
        $y = $articlesPerMonth;
        $text = array_map(function($n) {return $n . " articles in " . date('F', mktime(0, 0, 0, $n, 10));}, $x); // TODO add year
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
</div>
