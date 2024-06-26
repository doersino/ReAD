<?php

$runtimeStart = microtime(true);

// redirect if this file is accessed directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Article.class.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/Icons.class.php";
require_once __DIR__ . "/Read.class.php";
require_once __DIR__ . "/Statistics.class.php";
require_once __DIR__ . "/TextExtractor.class.php";
require_once __DIR__ . "/TimeUnit.class.php";

if (Config::SHOW_ALL_ERRORS) {
    ini_set("display_errors", 1);
    error_reporting(~0);
}

// helper function (used in a few places to avoid multiple redirects from index.php to index.php?state=unread to index.php?state=unread&suggestion=... which negatively impact load time)
function redirectToUnreadWithSuggestion($redirectToUnreadEvenIfNoSuggestion = true) {
    $readingSuggestion = Read::getRandomOldUnreadArticleId();

    // the suggestion can come up empty if there aren't enough articles or none that are old enough
    if (empty($readingSuggestion)) {
        if ($redirectToUnreadEvenIfNoSuggestion) {
            header("Location: index.php?state=unread");
            exit;
        }  // else do nothing
    } else {
        header("Location: index.php?state=unread&suggestion=" . $readingSuggestion);
        exit;
    }
}

// effectively invalidate style.css cache when it changes
$styleQueryString = substr(md5(filemtime("style.css")), 0, 5);

// handle login stuff
require_once __DIR__ . "/login.php";

// make sure we're always in a valid state
$states = array("unread", "archived", "starred", "stats", "view");
$listStates = array("unread", "archived", "starred");
if (!array_key_exists("state", $_GET) || !in_array($_GET["state"], $states)) {
    redirectToUnreadWithSuggestion();
} else {
    $state = $_GET["state"];
}

// get article count for each state for display in header
$totalArticleCount = Read::getTotalArticleCount();

if ($state === "stats") {
    if (!array_key_exists("period", $_GET) && !array_key_exists("start", $_GET) && !array_key_exists("end", $_GET)) {
        header("Location: index.php?state=stats&period=" . Config::STATS_DEFAULT_PERIOD);
    }

    // in case a new second starts between different time() calls
    $time = time();

    // handle period
    if (array_key_exists("period", $_GET) && $_GET["period"] !== "custom") {
        $period = $_GET["period"];

        if (!in_array($period, array("alltime", "month", "year", "decade", "30d", "90d", "365d", "1000d"))) {
            $error = "The selected period \"$period\" is invalid";
        } else {

            // get end time
            $end = $time;
            if (array_key_exists("end", $_GET)) {
                $end = $_GET["end"];
                if (!Helper::isTimestamp($end)) {
                    $end = strtotime($end);
                }
                if ($end === false) {
                    $error = "Couldn't parse end time \"" . $_GET["end"] . "\"";
                }
            }

            // compute start times for all periods based on end time
            if (!isset($error)) {
                if ($period === "alltime") {
                    $start = Read::getFirstArticleTime();
                    $end = $time;

                    // older, newer don't make much sense here
                } else if ($period === "month") {
                    $t = new TimeUnit("month");

                    // start of month
                    $start = strtotime($t->formatTime($end));

                    // end of month: 1 second before start of next month
                    $end = strtotime($t->formatTime($t->incrementTime($end))) - 1;

                    $older = $t->decrementTime($end);
                    $newer = $t->incrementTime($end);
                } else if ($period === "year") {
                    $t = new TimeUnit("year");

                    // start of year (the "-01" is a fix for strtotime parsing YYYY as HH:MM)
                    $start = strtotime($t->formatTime($end) . "-01");

                    // end of year: 1 second before start of next year
                    $end = strtotime($t->formatTime($t->incrementTime($end)) . "-01") - 1;

                    $older = $t->decrementTime($end);
                    $newer = $t->incrementTime($end);
                } else if ($period === "decade") {
                    $t = new TimeUnit("decade");

                    // start of decade (the "-01" is a fix for strtotime parsing YYYY as HH:MM)
                    $start = strtotime($t->formatTime($end) . "-01");

                    // end of decade: 1 second before start of next decade
                    $end = strtotime($t->formatTime($t->incrementTime($end)) . "-01") - 1;

                    $older = $t->decrementTime($end);
                    $newer = $t->incrementTime($end);
                } else { // 30d, 90d, 365d or 1000d
                    $n = rtrim($period, "d");
                    $t = new TimeUnit("day");

                    // last second of day corresponding to end timestamp
                    $end = strtotime("tomorrow", $end) - 1;

                    // n days plus 1 second earlier
                    $start = strtotime("tomorrow", $t->decrementTime($end, $n));

                    $older = $t->decrementTime($end, $n);
                    $newer = $t->incrementTime($end, $n);
                }
            }
        }
    } else { // start and/or end seem to be set
        $period = "custom";

        // handle start and/or end
        $start = Read::getFirstArticleTime();
        $end = $time;

        if (array_key_exists("start", $_GET)) {
            $start = $_GET["start"];
            if (!Helper::isTimestamp($start)) {
                $start = strtotime($start);
            }
            if ($start === false) {
                $error = "Couldn't parse start time \"" . $_GET["start"] . "\"";
            }
        }
        if (array_key_exists("end", $_GET)) {
            $end = $_GET["end"];
            if (!Helper::isTimestamp($end)) {
                $end = strtotime($end);
            }
            if ($end === false) {
                if (isset($error)) {
                    $error .= "and end time \"" . $_GET["end"] . "\"";
                } else {
                    $error = "Couldn't parse end time \"" . $_GET["end"] . "\"";
                }
            }
        }

        // make sure start time is before end time
        if (!isset($error) && $start > $end) {
            $start = TimeUnit::sFormatTimeVerbose("iso", $start);
            $end = TimeUnit::sFormatTimeVerbose("iso", $end);
            $error = "The selected start time \"$start\" is not before the selected end time \"$end\"";
        }
    }

    // generate time range description shown in the hero text
    if (!isset($error)) {
        if ($period === "custom") {
            $t = new TimeUnit("day");
            if ($t->sameTime($end, $time)) {
                $periodText = "since " . $t->formatTimeVerbose($start);
            } else if ($t->sameTime($start, Read::getFirstArticleTime())) {
                $periodText = "through " . $t->formatTimeVerbose($end);
            } else {
                $periodText = "between " . $t->formatTimeVerbose($start);
                $periodText .= " and " . $t->formatTimeVerbose($end);
            }
        } else if ($period === "alltime") {
            $periodText = "since " . TimeUnit::sFormatTimeVerbose("month", $start);
        } else if ($period === "month" || $period === "year" || $period === "decade") {
            $t = new TimeUnit($period);
            $periodText = "in " . $t->formatTimeVerbose($start);
        } else { // 30d, 90d, 365d or 1000d
            $t = new TimeUnit("day");
            $n = rtrim($period, "d");
            $periodText = "in the $n-day period";
            $periodText .= " from " . $t->formatTimeVerbose($start);
            $periodText .= " to " . $t->formatTimeVerbose($end);
        }

        // let's end on a encouraging note
        if ($end > $time) {
            $periodText .= " so far";
        }
    }

    $title = Read::getTotalArticleCount("archived", false, $start, $end) . " articles " . $periodText;

} else if ($state === "view") {
    if (empty($_GET["id"])) {
        $error = "No article ID given";
    } else {
        $id = intval($_GET["id"]);
        $article = Read::getArticle($id);

        if ($article == false) {
            $error = "Found no article text for the given ID $id";
        } else {
            $title = $article["title"];
        }
    }
} else {

    // handle search, offset and errors
    if (!empty($_GET["s"])) {
        $search = htmlspecialchars($_GET["s"], ENT_QUOTES, "UTF-8");
        $rawSearch = rawurlencode($_GET["s"]);
    }
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
        if (empty($_REQUEST["query"])) {
            $return = true;
        } else if (Helper::isUrl($_REQUEST["query"])) {
            $return = Article::add($_REQUEST["query"], $state);
        } else {
            header("Location: index.php?state=$state&s=" . rawurlencode($_REQUEST["query"]));
            exit;
        }
    }
    if (isset($return)) {
        header("Location: index.php?state=$state" . ((isset($search)) ? "&s=" . $rawSearch : "") . (($offset > 0) ? "&offset=$offset" : "") . (($return !== true) ? "&error=" . rawurlencode($return) : ""));
        exit;
    }

    // handle reading suggestions: if the app is in the correct state and there is no suggestion yet, come up with one and effectively add it to the query string to persist it across reloads (which sometimes happen, depending on the browser, when opening an article in order to read it and then returning here to mark it as read – it would be annoying if the article wasn't in the suggestion box anymore at that time)
    if ($state === "unread" && $offset == 0 && !isset($search) && !isset($error)) {
        if (empty($_GET["suggestion"])) {
            redirectToUnreadWithSuggestion(false);
        } else {
            $readingSuggestion = $_GET["suggestion"];

            // TODO should probably make sure here whether the article still exists and is still unread, but let's consider that an edge case not worth handling for now
        }
    }

    // get articles and build title
    if (isset($search)) {
        $articles = Read::getSearchResults($state, $search);
        $title = count($articles) . " $state article" . ((count($articles) == 1) ? "" : "s") . " matching \"$search\"";
        $totalArticleCount = Read::getTotalArticleCount(false, $search);
    } else {
        $articles = Read::getArticles($state, $offset, Config::MAX_ARTICLES_PER_PAGE);

        if ($state === "unread" && empty($articles))
            $title = "Inbox Zero";
        else
            $title = $totalArticleCount[$state] . " $state article" . ((count($articles) == 1) ? "" : "s");
    }

    // get graph data depending on current state
    if (Config::SHOW_ARTICLES_PER_TIME_GRAPH) {
        if ($state === "unread") {
            $articlesPerTime = Statistics::articlesPerTime(Config::ARTICLES_PER_TIME_GRAPH_STEP_SIZE, "archived");
        } else if (isset($search) && !empty($articles)) {
            $articlesPerTime = Statistics::articlesPerTime(Config::ARTICLES_PER_TIME_GRAPH_STEP_SIZE, $state, $search);
        } else {
            $articlesPerTime = Statistics::articlesPerTime(Config::ARTICLES_PER_TIME_GRAPH_STEP_SIZE, $state);
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
    <link rel="shortcut icon" href="imgs/favicon.png">
    <link rel="apple-touch-icon" href="imgs/favicon.png">
    <link rel="stylesheet" href="deps/octicons-4.3.0/build/font/octicons.css">
    <link rel="stylesheet" href="style.css?<?= $styleQueryString ?>">
    <?php if (in_array($state, $listStates)) { ?>
        <script>
            document.addEventListener("DOMContentLoaded", function(event) {

                // only auto-focus query bar on desktop
                if (window.matchMedia("(min-width: 720px)").matches) {
                    document.getElementById("query").focus();
                }
            });

            function isUrl(s) {  // should mirror Helper::isUrl()
                return s.substr(0, 7).toLowerCase() == 'http://' || s.substr(0, 8).toLowerCase() == 'https://';
            }

            function updateQueryIcons() {
                var query = document.getElementById('query');
                var submitbutton = document.getElementById('submitbutton');
                var clearbutton  = document.getElementById('clearbutton');

                if (query.value != '<?php if (isset($search)) echo $search ?>') {
                    submitbutton.style.display = 'block';
                    if (isUrl(query.value)) {
                        submitbutton.innerHTML = '<?= ($state === "unread") ? Icons::ACTION_ADD_UNREAD : (($state === "archived") ? Icons::ACTION_ADD_ARCHIVED : ICONS::ACTION_ADD_STARRED) ?>'
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
    <?php if ($state === "view") { ?>
        <script>
            let apiKey = encodeURIComponent("<?= Config::API_KEY ?>");

            let id = <?= $article["id"] ?>;

            var quote = null;
            var quoteId = null;

            document.addEventListener("scroll", () => {
                let progress = 100 * (window.scrollY / (document.documentElement.scrollHeight - document.documentElement.clientHeight))
                if (progress < 0) {
                    progress = 0
                }
                document.getElementById("progress").style.width = progress + "%"
            });

            document.addEventListener("selectionchange", () => {

                // get selection and make sure it's in the article body
                let selection = document.getSelection();
                let selectionText = selection.toString();
                if (!(selection.containsNode(document.getElementById("text"), true) && selectionText != "")) {
                    hideApiError();
                    document.getElementById("quoter").style.display = "none";
                    return;
                }
                quote = selectionText;

                // check if an exisiting quote was selected
                quoteId = null;
                let existingQuotes = document.querySelectorAll(".quote");
                if (existingQuotes) {
                    existingQuotes.forEach(e => {
                        if (selection.containsNode(e, true)) {
                            quoteId = e.getAttribute("id").replace("quote", "");
                        }
                    });
                }

                // show corresponding button
                if (quoteId) {
                    document.getElementById("quoter").style.display = "block";
                    document.getElementById("quoter-remove").style.display = "inline-block";
                    document.getElementById("quoter-add").style.display = "none";
                } else {
                    document.getElementById("quoter").style.display = "block";
                    document.getElementById("quoter-add").style.display = "inline-block";
                    document.getElementById("quoter-remove").style.display = "none";
                }
            });

            function toggleQuoteEditor() {
                let quoteEditor = document.getElementById("quote-editor");
                let quoteEditorToggle = document.getElementById("quote-editor-toggle");

                if (!quoteEditor.style.display || quoteEditor.style.display == "none") {
                    quoteEditor.style.display = "block";
                    quoteEditorToggle.querySelector(".expand").style.display = "none";
                    quoteEditorToggle.querySelector(".contract").style.display = "inline";
                } else {
                    quoteEditor.style.display = "none";
                    quoteEditorToggle.querySelector(".expand").style.display = "inline";
                    quoteEditorToggle.querySelector(".contract").style.display = "none";
                }
            }

            function api(action, parameters, callback) {
                fetch(`api.php?key=${apiKey}&action=${action}&${parameters}`)
                    .then(response => {
                        if (!response.ok) {
                            throw response;
                        }
                        return response.json();
                    })
                    .then(json => {
                        if (json.status == "error") {
                            throw json.text;
                        }
                        callback(json);
                    })
                    .catch(error => {
                        showApiError(error);
                    });
            }

            function showApiError(message) {
                let error = document.getElementById("api-error");
                error.style.display = "block";
                error.innerText = "Error: " + message;
            }

            function hideApiError() {
                let error = document.getElementById("api-error");
                error.style.display = "none";
            }

            function apiAddQuote(id, quote, callback) {
                api("add_quote", `id=${id}&quote=${encodeURIComponent(quote)}`, callback);
            }

            function apiRemoveQuote(quoteId, callback) {
                api("remove_quote", `quote_id=${quoteId}`, callback);
            }

            function listAddQuote() {
                hideApiError();
                let quoteToAdd = document.getElementById("add-additional-quote-input").textContent.trim();
                apiAddQuote(id, quoteToAdd, json => {
                    let newQuoteId = json.data.quote_id;

                    let newQuoteContainer = document.createElement("div");
                    newQuoteContainer.classList.add("quote-container");
                    newQuoteContainer.innerHTML = `
                        <span class="quote" id="quote${newQuoteId}">${quoteToAdd}</span>
                        <button class="remove-quote" onclick="listRemoveQuote(${newQuoteId});">
                            <span class="icon"><?= Icons::ACTION_REMOVE_QUOTE ?></span>
                        </button>`;
                    document.getElementById("quotes-not-found").appendChild(newQuoteContainer);
                    document.getElementById("add-additional-quote-input").innerText = "";
                    document.getElementById("quotes-not-found").style.display = "block";
                });
            }

            function listRemoveQuote(quoteId) {
                hideApiError();
                apiRemoveQuote(quoteId, () => {
                    document.getElementById("quotes-not-found").querySelector("#quote" + quoteId).parentNode.outerHTML = "";
                    if (!document.getElementById("quotes-not-found").querySelector(".quote-container")) {
                        document.getElementById("quotes-not-found").style.display = "none";
                    }
                });
            }

            function quoterAddQuote() {
                hideApiError();
                apiAddQuote(id, quote, json => {
                    let newQuoteId = json.data.quote_id;

                    // go through all text nodes (which might be interspersed with existing quotes), find current quote again and highlight it
                    let textNodes = Array.from(document.getElementById("text").childNodes).filter(n => n.nodeType == Node.TEXT_NODE);
                    textNodes.forEach(n => {
                        let offset = n.textContent.indexOf(quote);
                        if (offset >= 0) {
                            let rest = n.splitText(offset);
                            rest.textContent = rest.textContent.replace(quote, "");

                            let quoteElement = document.createElement("span");
                            quoteElement.setAttribute("class", "quote");
                            quoteElement.setAttribute("id", "quote" + newQuoteId);
                            quoteElement.appendChild(document.createTextNode(quote));

                            document.getElementById("text").insertBefore(quoteElement, rest);
                        }
                    });
                });
            }

            function quoterRemoveQuote() {
                hideApiError();
                apiRemoveQuote(quoteId, () => {

                    // replace quote span with its contained text
                    let quoteElement = document.getElementById("quote" + quoteId);
                    quoteElement.replaceWith(quoteElement.innerText);

                    // merge resulting text node with adjacent text nodes
                    document.getElementById("text").normalize();
                });
            }
        </script>
    <?php } ?>
</head>
<body>
    <header>
        <?php if ($state === "view") { ?>
            <hr>
            <hr id="progress" class="progress">
            <div class="back"><a href="index.php"><span class="icon"><?= Icons::TAB_OLDER ?></span><?php readfile("imgs/read.svg"); ?></a></div>
            <?php if ($article["archived"] == 0) { ?>
                <div class="viewfinish">
                    <form action="index.php?state=unread" method="post">
                        <input type="hidden" name="id" value="<?= $article["id"] ?>">
                        <button type="submit" name="archive"><span class="icon"><?= Icons::ACTION_ARCHIVE ?></span></button>
                    </form>
                </div>
            <?php } ?>
        <?php } else { ?>
            <nav>
                <a href="index.php" class="read"><?php readfile("imgs/read.svg"); ?></a>
                <a href="index.php?state=unread<?php if (Config::KEEP_SEARCHING_WHEN_CHANGING_STATE && isset($search)) echo "&amp;s=" . $rawSearch ?>"<?php if ($state === "unread") echo " class=\"current\"" ?> title="Unread"><span class="icon"><?= Icons::TAB_UNREAD ?></span> <?= $totalArticleCount["unread"] ?></a>
                <a href="index.php?state=archived<?php if (Config::KEEP_SEARCHING_WHEN_CHANGING_STATE && isset($search)) echo "&amp;s=" . $rawSearch ?>"<?php if ($state === "archived") echo " class=\"current\"" ?> title="Archived"><span class="icon"><?= Icons::TAB_ARCHIVED ?></span> <?= $totalArticleCount["archived"] ?></a>
                <a href="index.php?state=starred<?php if (Config::KEEP_SEARCHING_WHEN_CHANGING_STATE && isset($search)) echo "&amp;s=" . $rawSearch ?>"<?php if ($state === "starred") echo " class=\"current\"" ?> title="Starred"><span class="icon"><?= Icons::TAB_STARRED ?></span> <?= $totalArticleCount["starred"] ?></a>
            </nav>
            <nav class="pages">
                <?php if (in_array($state, $listStates) && !isset($search) && $totalArticleCount[$state] > $offset) { ?>
                    <?php if (!isset($search) && $totalArticleCount[$state] > $offset + Config::MAX_ARTICLES_PER_PAGE) { ?>
                        <a href="index.php?state=<?= $state . "&amp;offset=" . ($offset + Config::MAX_ARTICLES_PER_PAGE) ?>" class="icon" title="Older"><?= Icons::TAB_OLDER ?></a>
                    <?php } if ($offset != 0) { ?>
                        <a href="index.php?state=<?= $state; if ($offset - Config::MAX_ARTICLES_PER_PAGE > 0) echo "&amp;offset=" . ($offset - Config::MAX_ARTICLES_PER_PAGE) ?>" class="icon" title="Newer"><?= Icons::TAB_NEWER ?></a>
                    <?php } ?>
                <?php } ?>
                <a href="index.php?state=stats&amp;period=<?= Config::STATS_DEFAULT_PERIOD ?>" class="icon statsicon<?php if ($state === "stats") echo " current" ?>" title="Statistics"><?= Icons::TAB_STATS ?></a>
            </nav>
            <?php if ($state === "stats") { ?>
                <nav class="stats">
                    <?php if ($start > ReAD::getFirstArticleTime()) { ?>
                        <a class="olderbutton icon" href="index.php?state=<?= $state ?>&amp;period=<?= $period ?>&amp;end=<?= $older ?>"><?= Icons::ACTION_OLDER ?></a>
                    <?php } ?>
                    <form action="index.php?state=<?= $state ?>&amp;end=<? $end ?>" method="get">
                        <select name="period" id="period" class="period">
                            <option value="alltime" <?php if ($period == "alltime") echo "selected"; ?>>All Time</option>
                            <option value="decade" <?php if ($period == "decade") echo "selected"; ?>>Decade</option>
                            <option value="year" <?php if ($period == "year") echo "selected"; ?>>Year</option>
                            <option value="month" <?php if ($period == "month") echo "selected"; ?>>Month</option>
                            <option value="30d" <?php if ($period == "30d") echo "selected"; ?>>30 Days</option>
                            <option value="90d" <?php if ($period == "90d") echo "selected"; ?>>90 Days</option>
                            <option value="365d" <?php if ($period == "365d") echo "selected"; ?>>365 Days</option>
                            <option value="1000d" <?php if ($period == "1000d") echo "selected"; ?>>1K Days</option>
                            <?php if ($period === "custom") { ?>
                                <option value="custom" selected>Custom</option>
                            <?php } ?>
                        </select>
                    </form>
                    <?php if ($end < $time) { ?>
                        <a class="newerbutton icon" href="index.php?state=<?= $state ?>&amp;period=<?= $period ?>&amp;end=<?= $newer ?>"><?= Icons::ACTION_NEWER ?></a>
                    <?php } ?>
                </nav>
                <script>
                    period.onchange = function() {
                        window.location.href = "index.php?state=<?= $state ?>&period=" + this.value + "&end=<?= $end ?>";
                    }
                </script>
            <?php } else { ?>
            <form action="index.php?state=<?= $state ?>" method="post">
                <?php if (isset($search)) { ?>
                    <a href="index.php?state=<?= $state ?>" class="clearbutton icon" id="clearbutton"><?= Icons::ACTION_CLEAR ?></a>
                <?php } ?>
                <a href="javascript:document.getElementById('submit').click();" class="submitbutton icon" id="submitbutton"><?= ($state === "unread") ? Icons::ACTION_ADD_UNREAD : (($state === "archived") ? Icons::ACTION_ADD_ARCHIVED : ICONS::ACTION_ADD_STARRED) ?></a>
                <input type="text" name="query" class="query" id="query" value="<?php if (isset($search)) echo $search ?>" placeholder="Add or Search <?= ucfirst($state) ?> Articles" oninput="updateQueryIcons()">
                <input type="submit" name="search" class="submit" id="submit">
            </form>
            <?php } ?>
        <?php } ?>
    </header>
    <main class="main-<?= $state ?>">
        <?php if (isset($error)) { ?>
            <div class="words"><?= $title ?>. Try going back to the previous page.</div>
        <?php } else if ($state === "stats") { ?>
            <div class="stats">
                <?php include("stats.php") ?>
            </div>
        <?php } else if ($state === "view") { ?>
            <div class="quoter" id="quoter">
                <button onclick="quoterAddQuote();" id="quoter-add" class="add-quote"><span class="icon"><?= Icons::ACTION_ADD_QUOTE ?></span> Add Highlighted Quote</button>
                <button onclick="quoterRemoveQuote();" id="quoter-remove" class="remove-quote"><span class="icon"><?= Icons::ACTION_REMOVE_QUOTE ?></span> Remove Quote</button>
            </div>
            <div class="api-error" id="api-error"></div>
            <?php
                $text = $article["text"];
                $quotesNotFound = [];

                if (isset($article["quotes"])) {
                    foreach ($article["quotes"] as $quote) {
                        $text = str_replace($quote["quote"], "<span class=\"quote\" id=\"quote" . $quote["quote_id"] . "\">" . $quote["quote"] . "</span>", $text, $replaced);
                        if ($replaced == 0) {
                            $quotesNotFound[] = $quote;
                        }
                    }
                }
            ?>
            <div class="viewheader">
                <h1><a href="<?= $article["url"] ?>"><?= $article["title"] ?></a></h1>
                <div class="meta">
                    <span class="icon star"><?= ($article["starred"] == 1) ? Icons::ACTION_STAR : "" ?></span>
                    You've
                    <?= ($article["archived"] == 0) ? "added" : "archived" ?>
                    this article from
                    <strong><?= Helper::getHost($article["url"]) ?></strong>
                    on
                    <strong><?= TimeUnit::sFormatTimeVerbose("day", $article["time"]) ?></strong>.
                    It consists of
                    <strong><?= $article["wordcount"] ?> words</strong>,
                    so expect it to take roughly
                    <strong><?= Helper::makeTimeHumanReadable(TextExtractor::computeErt($article["wordcount"]), false, "minute", "minute") ?></strong>
                    to read.
                    <div class="archive-links">
                        <span class="icon">&#xf088;</span> If there's a paywall, perhaps you can get around it via <a href="https://archive.today/submit/?url=<?= rawurlencode($article["url"]) ?>">archive.today</a> or <a href="https://web.archive.org/web/9999/<?= $article["url"] ?>">archive.org</a>.
                    </div>
                    <!--TODO this looks really good as a contrasty thing when placed on the edge between header and content in dark mode, but also makes things a bit cluttered and there's no need for any action there: <div style="position: absolute; right: 0; margin-right: 1rem; margin-top: 7.5em; background-color: #ddd; color: gray; padding: 0.5em 0.6em 0.5em 0.5em; height: 2.25em; width: 2.25em; text-align: right; border-radius: 99em;"><span class="icon">&#xf088;</span></div>-->
                </div>
                <div class="quote-editor-wrapper">
                    <a href="javascript:toggleQuoteEditor();" class="quote-editor-toggle" id="quote-editor-toggle">
                        <span class="icon expand"><?= Icons::ACTION_EXPAND ?></span><span class="icon contract"><?= Icons::ACTION_CONTRACT ?></span>View, Add or Remove Quotes
                    </a>
                    <div class="quote-editor" id="quote-editor">
                        <div class="quotes-not-found" id="quotes-not-found" style="display: <?= (count($quotesNotFound) > 0) ? "block" : "none" ?>">
                            <em>The following quote(s) couldn't be located in the article text.</em>
                            <?php foreach ($quotesNotFound as $quote) { ?>
                                <div class="quote-container">
                                    <span class="quote" id="quote<?= $quote["quote_id"] ?>"><?= $quote["quote"] ?></span>
                                    <button class="remove-quote" onclick="listRemoveQuote(<?= $quote["quote_id"] ?>);">
                                        <span class="icon"><?= Icons::ACTION_REMOVE_QUOTE ?></span>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="add-additional-quotes">
                            <em>If you wish to add additional quotes not present in the extracted article text, enter them below.</em>
                            <div class="quote-container">
                                <p class="quote" id="add-additional-quote-input" contenteditable> </p>
                                <button class="add-quote" onclick="listAddQuote()">
                                    <span class="icon"><?= Icons::ACTION_ADD_QUOTE ?></span>
                                </button>
                            </div>
                        </div>
                        <em>(To add quotes directly from the text, highlight them and click the button that will summarily pop up at the bottom edge of the screen. For deletion of such quotes, highlight any part of them, upon which a delete button will appear.)</em>
                    </div>
                </div>
            </div>
            <div class="viewcontent">
                <pre id="text"><?= $text ?></pre>
            </div>
        <?php } else if (empty($articles)) { ?>
            <div class="words"><?= (isset($search) || $state !== "unread") ? "Found $title." : $title ?></div>
        <?php } else { ?>
            <?php if (isset($readingSuggestion)) { ?>
                <aside class="random">
                    <p>Why don't you read this article that you've added a while ago and have probably forgotten about?</p>
                    <?php $article = Read::getArticle($readingSuggestion); ?>
                    <table>
                        <tr>
                            <td class="left"><abbr title="<?= TimeUnit::sFormatTimeVerbose("iso", $article["time"]) ?>"><?= Helper::ago($article["time"], true) ?></abbr></td>
                            <td class="middle">
                                <a href="<?= $article["url"] ?>" class="text"><?= $article["title"] ?></a>
                                <span class="info">
                                    <a href="index.php?state=<?= "$state&amp;s=" . rawurlencode(Helper::getHost($article["url"])) ?>"><?= Helper::getHost($article["url"]) ?></a>
                                    ·
                                    <a href="index.php?state=view&id=<?= $article["id"] ?>" title="Estimated reading time based on <?= $article["wordcount"] ?> words and a reading speed of <?= Config::WPM ?> words per minute"><span class="ertlabel">ERT</span> <?= Helper::makeTimeHumanReadable(TextExtractor::computeErt($article["wordcount"]), true, "minute", "minute") ?></a>
                                </span>
                                <?php if (isset($article["quotes"])) { ?>
                                    <div class="quotes">
                                        <?php foreach ($article["quotes"] as $quote) { ?>
                                            <a class="quote" href="index.php?state=view&id=<?= $article["id"] ?>#quote<?= $quote["quote_id"] ?>"><?= $quote["quote"] ?></a>
                                            <br>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </td>
                            <td class="actions">
                                <form action="index.php?state=unread" method="post">
                                    <input type="hidden" name="id" value="<?= $article["id"] ?>">
                                    <input type="submit" name="archive" value="<?= Icons::ACTION_ARCHIVE ?>">
                                    <input type="submit" name="remove" value="<?= Icons::ACTION_REMOVE ?>">
                                </form>
                            </td>
                        </tr>
                    </table>
                </aside>
            <?php } ?>
            <table>
                <?php foreach ($articles as $article) { ?>
                    <tr>
                        <td class="left"><abbr title="<?= TimeUnit::sFormatTimeVerbose("iso", $article["time"]) ?>"><?= Helper::ago($article["time"], true) ?></abbr></td>
                        <td class="middle">
                            <a href="<?= $article["url"] ?>" class="text"><?php if (isset($search)) echo Helper::highlight($article["title"], $search); else echo $article["title"] ?></a>
                            <span class="info">
                                <a href="index.php?state=<?= "$state&amp;s=" . rawurlencode(Helper::getHost($article["url"])) ?>"><?php if (isset($search)) echo Helper::highlight(Helper::getHost($article["url"]), $search); else echo Helper::getHost($article["url"]) ?></a>
                                ·
                                <a href="index.php?state=view&id=<?= $article["id"] ?>" title="Estimated reading time based on <?= $article["wordcount"] ?> words and a reading speed of <?= Config::WPM ?> words per minute"><span class="ertlabel">ERT</span> <?= Helper::makeTimeHumanReadable(TextExtractor::computeErt($article["wordcount"]), true, "minute", "minute") ?></a>
                            </span>
                            <?php if (isset($article["quotes"])) { ?>
                                <div class="quotes">
                                    <?php foreach ($article["quotes"] as $quote) { ?>
                                        <a class="quote" href="index.php?state=view&id=<?= $article["id"] ?>#quote<?= $quote["quote_id"] ?>"><?php if (isset($search)) echo Helper::highlight($quote["quote"], $search); else echo $quote["quote"] ?></a>
                                        <br>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </td>
                        <td class="actions">
                            <form action="index.php?state=<?= $state . ((isset($search)) ? "&s=" . $rawSearch : "") . (($offset > 0) ? "&offset=$offset" : "") ?>" method="post">
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
    <?php if (Config::SHOW_ARTICLES_PER_TIME_GRAPH && in_array($state, $listStates)) { ?>
        <div id="articlespertimegraph" class="articlespertimegraph"></div>
        <script src="deps/plotly-basic.min.js"></script>
        <script>
            var data = [{
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
            }];

            var layout = {
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
                }
            };

            Plotly.newPlot('articlespertimegraph', data, layout, {staticPlot: true});
        </script>
    <?php } ?>
</body>
</html>

<!-- runtime <?= round(1000 * (microtime(true) - $runtimeStart)) ?> ms -->
