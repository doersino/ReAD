<?php

require_once "Config.class.php";
require_once "Helper.class.php";
require_once "Article.class.php";
require_once "Read.class.php";
require_once "TimeUnit.class.php";
require_once "Icons.class.php";

// some websites really dislike empty user agent strings
ini_set("user_agent", "Mozilla/5.0 (compatible; ReAD/1.0; +https://github.com/doersino/ReAD)");

// make sure we're always in a valid state
if (!array_key_exists("state", $_GET) || !in_array($_GET["state"], array("unread", "archived", "starred", "stats"))) {
    header("Location: index.php?state=unread");
    exit;
} else {
    $state = $_GET["state"];
}

// get article count for each state for display in header
$totalArticleCount = Read::getTotalArticleCount();

if ($state === "stats") {

    // handle start and end
    $start = Read::getFirstArticleTime();
    $startText = TimeUnit::sFormatTimeVerbose("month", $start);

    $end = time();
    $endText = "now";

    if (array_key_exists("start", $_GET)) {
        $getStart = $_GET["start"];
        if (!Helper::isTimestamp($getStart)) {
            $getStart = strtotime($getStart);
            echo "test";
        }
        if ($getStart === false) {
            $error = "Couldn't parse start time \"" . $_GET["start"] . "\"";
        } else {
            if ($getStart < $start) { // clamp to available range
                $getStart = $start;
            }
            $start = $getStart;
            $startText = TimeUnit::sFormatTimeVerbose("day", $start);
        }
    }
    if (array_key_exists("end", $_GET)) {
        $getEnd = $_GET["end"];
        if (!Helper::isTimestamp($getEnd)) {
            $getEnd = strtotime($getEnd);
        }
        if ($getEnd === false) {
            $error = "Couldn't parse end time \"" . $_GET["end"] . "\"";
        } else {
            if ($getEnd > $end) { // clamp to available range
                $getEnd = $end;
            }
            $end = $getEnd;
            $endText = TimeUnit::sFormatTimeVerbose("day", $end);
        }
    }

    // make sure start time is before end time
    if ($start > $end) {
        $start = TimeUnit::sFormatTimeVerbose("iso", $start);
        $end = TimeUnit::sFormatTimeVerbose("iso", $end);
        $error = "The selected start time \"$start\" is not before the selected end time \"$end\"";
    }

    // TODO change title depending on selected range, similar to hero text
    $title = "Statistics";

} else {

    // handle search, offset and errors
    if (!empty($_GET["s"]))
        $search = htmlspecialchars($_GET["s"], ENT_QUOTES, "UTF-8");
    $offset = 0;
    if (!empty($_GET["offset"]))
        $offset = intval($_GET["offset"]);
    if (!empty($_GET["error"]))
        $error = $_GET["error"];

    // handle user actions/changes to the database
    if (isset($_POST["archive"]) && isset($_POST["id"]))
        $return = Article::archive($_POST["id"]);
    if (isset($_POST["star"]) && isset($_POST["id"]))
        $return = Article::star($_POST["id"]);
    if (isset($_POST["unstar"]) && isset($_POST["id"]))
        $return = Article::unstar($_POST["id"]);
    if (isset($_POST["remove"]) && isset($_POST["id"]))
        $return = Article::remove($_POST["id"]);
    if (isset($_REQUEST["search"]) && isset($_REQUEST["query"])) {
        if (empty($_REQUEST["query"]))
            $return = true;
        else if (Helper::isUrl($_REQUEST["query"]))
            $return = Article::add($_REQUEST["query"], $state);
        else {
            header("Location: index.php?state=$state&s=" . rawurlencode($_REQUEST["query"]));
            exit;
        }
    }
    if (isset($return)) {
        header("Location: index.php?state=" . $state . ((isset($search)) ? "&s=" . rawurlencode($_GET["s"]) : "") . (($offset > 0) ? "&offset=$offset" : "") . (($return !== true) ? "&error=" . rawurlencode($return) : ""));
        exit;
    }

    // get articles and build title
    if (isset($search)) {
        $articles = Read::getSearchResults($state, $search);
        $title = count($articles) . " $state article" . ((count($articles) == 1) ? "" : "s") . " matching \"$search\"";
    } else {
        $articles = Read::getArticles($state, $offset, Config::$maxArticlesPerPage);

        if ($state === "unread" && empty($articles))
            $title = "Inbox Zero";
        else
            $title = $totalArticleCount[$state] . " $state article" . ((count($articles) == 1) ? "" : "s");
    }

    // get graph data depending on current state
    if (Config::$showArticlesPerTimeGraph) {
        if ($state === "unread") {
            $articlesPerTime = Read::getArticlesPerTime(Config::$articlesPerTimeGraphTimeStepSize, "archived");
        } else if (isset($search) && !empty($articles)) {
            $articlesPerTime = Read::getArticlesPerTime(Config::$articlesPerTimeGraphTimeStepSize, $state, $search);
        } else {
            $articlesPerTime = Read::getArticlesPerTime(Config::$articlesPerTimeGraphTimeStepSize, $state);
        }

        $x = range(0, count($articlesPerTime));
        $y = $articlesPerTime;
    }
}

if (isset($error)) {
    $title = "Error: $error";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> - ReAD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000">
    <link rel="shortcut icon" href="favicon.gif">
    <link rel="stylesheet" href="lib/elusive-icons-2.0.0/css/elusive-icons.min.css">
    <link rel="stylesheet" href="style.php">
    <?php if ($state !== "stats") { ?>
        <script>
            document.addEventListener("DOMContentLoaded", function(event) {

                // remove autofocus from query bar on mobile
                if (window.matchMedia("(min-width: 720px)").matches) {
                    document.getElementById("query").focus();
                }
            });

            function isUrl(s) { <?php /* should mirror Helper::isUrl() */ ?>
                return s.substr(0, 7).toLowerCase() == 'http://' || s.substr(0, 8).toLowerCase() == 'https://';
            }

            function updateQueryIcons() {
                var query        = document.getElementById('query');
                var submitbutton = document.getElementById('submitbutton');
                var clearbutton  = document.getElementById('clearbutton');

                if (query.value != '<?php if (isset($search)) echo $search ?>') {
                    submitbutton.style.display = 'block';
                    if (isUrl(query.value)) {
                        submitbutton.innerHTML = '<?= Icons::ACTION_ADD ?>'
                    } else {
                        submitbutton.innerHTML = '<?= Icons::ACTION_SEARCH ?>'
                    }

                    <?php if (isset($search)) { ?>
                        clearbutton.style.display = 'none';
                    <?php } ?>
                } else {
                    submitbutton.style.display = 'none';

                    <?php if (isset($search)) { ?>
                        clearbutton.style.display = 'block';
                    <?php } ?>
                }
            }
        </script>
    <?php } ?>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="read"><strong>ReAD</strong></a>
            <a href="index.php?state=unread<?php if (Config::$keepSearchingWhenChangingState && isset($search)) echo "&amp;s=" . rawurlencode($_GET["s"]) ?>"<?php if ($state === "unread") echo " class=\"current\"" ?> title="Unread"><span class="icon"><?= Icons::TAB_UNREAD ?></span> <?= $totalArticleCount["unread"] ?></a>
            <a href="index.php?state=archived<?php if (Config::$keepSearchingWhenChangingState && isset($search)) echo "&amp;s=" . rawurlencode($_GET["s"]) ?>"<?php if ($state === "archived") echo " class=\"current\"" ?> title="Archived"><span class="icon"><?= Icons::TAB_ARCHIVED ?></span> <?= $totalArticleCount["archived"] ?></a>
            <a href="index.php?state=starred<?php if (Config::$keepSearchingWhenChangingState && isset($search)) echo "&amp;s=" . rawurlencode($_GET["s"]) ?>"<?php if ($state === "starred") echo " class=\"current\"" ?> title="Starred"><span class="icon"><?= Icons::TAB_STARRED ?></span> <?= $totalArticleCount["starred"] ?></a>
        </nav>
        <nav class="pages">
            <?php if ($state !== "stats" && !isset($search) && $totalArticleCount[$state] > $offset) { ?>
                <?php if ($offset != 0) { ?>
                    <a href="index.php?state=<?= $state; if ($offset - Config::$maxArticlesPerPage > 0) echo "&amp;offset=" . ($offset - Config::$maxArticlesPerPage) ?>" class="icon" title="Newer"><?= Icons::TAB_NEWER ?></a>
                <?php } if (!isset($search) && $totalArticleCount[$state] > $offset + Config::$maxArticlesPerPage) { ?>
                    <a href="index.php?state=<?= $state . "&amp;offset=" . ($offset + Config::$maxArticlesPerPage) ?>" class="icon" title="Older"><?= Icons::TAB_OLDER ?></a>
                <?php } ?>
            <?php } ?>
            <a href="index.php?state=stats" class="icon<?php if ($state === "stats") echo " current" ?>" title="Statistics"><?= Icons::TAB_STATS ?></a>
        </nav>
        <?php if ($state === "stats") { ?>
            <hr>
        <?php } else { ?>
        <form action="index.php?state=<?= $state ?>" method="post">
            <?php if (isset($search)) { ?>
                <a href="index.php?state=<?= $state ?>" class="clearbutton icon" id="clearbutton"><?= Icons::ACTION_CLEAR ?></a>
            <?php } ?>
            <a href="javascript:document.getElementById('submit').click();" class="submitbutton icon" id="submitbutton"><?= Icons::ACTION_ADD ?></a>
            <input type="text" name="query" class="query" id="query" value="<?php if (isset($search)) echo $search ?>" placeholder="Add or Search <?= ucfirst($state) ?> Articles" oninput="updateQueryIcons()">
            <input type="submit" name="search" class="submit" id="submit">
        </form>
        <?php } ?>
    </header>
    <main>
        <?php if (isset($error)) { ?>
            <div class="words"><?= $title ?>. Try going back to the previous page.</div>
        <?php } else if ($state === "stats") { ?>
            <div class="stats">
                <?php include("stats.php") ?>
            </div>
        <?php } else if (empty($articles)) { ?>
            <div class="words"><?= (isset($search) || $state !== "unread") ? "Found $title." : $title ?></div>
        <?php } else { ?>
            <table>
                <?php foreach ($articles as $article) { ?>
                    <tr>
                        <td class="left"><abbr title="<?= TimeUnit::sFormatTimeVerbose("iso", $article["time"]) ?>"><?= Helper::ago($article["time"], true) ?></abbr></td>
                        <td class="middle">
                            <a href="<?= $article["url"] ?>" class="text"><?php if (isset($search)) echo Helper::highlight($article["title"], $search); else echo $article["title"] ?></a>
                            <a href="index.php?state=<?= "$state&amp;s=" . rawurlencode(Helper::getHost($article["url"])) ?>" class="info"><?= Helper::getIcon($article["url"]) ?><?php if (isset($search)) echo Helper::highlight(Helper::getHost($article["url"]), $search); else echo Helper::getHost($article["url"]) ?></a>
                            <div class="actions">
                                <form action="index.php?state=<?= $state . ((isset($search)) ? "&s=" . rawurlencode($_GET["s"]) : "") . (($offset > 0) ? "&offset=$offset" : "") ?>" method="post">
                                    <input type="hidden" name="id" value="<?= $article["id"] ?>">
                                    <?php if ($state === "unread") { ?>
                                        <input type="submit" name="archive" value="<?= Icons::ACTION_ARCHIVE ?>">
                                    <?php } else { ?>
                                        <input type="submit" name="<?= ($article["starred"] == 1) ? "unstar" : "star" ?>" value="<?= ($article["starred"] == 1) ? Icons::ACTION_UNSTAR : Icons::ACTION_STAR ?>">
                                    <?php } ?>
                                    <input type="submit" name="remove" value="<?= Icons::ACTION_REMOVE ?>">
                                </form>
                            </div>
                        </td>
                        <td class="actions">
                            <form action="index.php?state=<?= $state . ((isset($search)) ? "&s=" . rawurlencode($_GET["s"]) : "") . (($offset > 0) ? "&offset=$offset" : "") ?>" method="post">
                                <input type="hidden" name="id" value="<?= $article["id"] ?>">
                                <?php if ($state === "unread") { ?>
                                    <input type="submit" name="archive" value="<?= Icons::ACTION_ARCHIVE ?>">
                                <?php } else { ?>
                                    <input type="submit" name="<?= ($article["starred"] == 1) ? "unstar" : "star" ?>" value="<?= ($article["starred"] == 1) ? Icons::ACTION_UNSTAR : Icons::ACTION_STAR ?>">
                                <?php } ?>
                                <input type="submit" name="remove" value="<?= Icons::ACTION_REMOVE ?>">
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </main>
    <?php if (Config::$showArticlesPerTimeGraph && $state !== "stats") { ?>
        <div id="articlespertimegraph" class="articlespertimegraph"></div>
        <script src="lib/plotly-basic.min.js"></script>
        <script>
            var trace1 = {
                x: [<?= implode(",", $x) ?>],
                y: [<?= implode(",", $y) ?>],
                mode: 'lines',
                type: 'scatter',
                fillcolor: 'rgba(128, 128, 128, 0.14)',
                fill: 'tozeroy',
                line: {
                    color: 'rgba(128, 128, 128, 0.17)',
                    width: 1
                }
            };

            var data = [trace1];

            var layout = {
                xaxis: {
                    range: [0, <?= max($x) ?>]
                },
                yaxis: {
                    range: [0, <?= max($y) ?>]
                },
                plot_bgcolor: 'rgba(0,0,0,0)',
                paper_bgcolor: 'rgba(0,0,0,0)',
                margin: {l: 0, r: 0, t: 0, b: 0, pad: 0},
                xaxis: {
                    showgrid: false,
                    zeroline: false
                },
                yaxis: {
                    showgrid: false,
                    zeroline: false,
                    dtick: 10,
                    gridcolor: 'rgba(128, 128, 128, 0.07)'
                },
                height: 280
            };

            Plotly.newPlot('articlespertimegraph', data, layout, {staticPlot: true});
        </script>
    <?php } ?>
</body>
</html>
