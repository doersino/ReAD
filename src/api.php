<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/Article.class.php";
require_once __DIR__ . "/Quote.class.php";
require_once __DIR__ . "/Read.class.php";
require_once __DIR__ . "/Statistics.class.php";

function error($text, $emoji = null) {
    if ($emoji === null) $emoji = "❌";
    $err = array("status" => "error", "text" => $emoji . " " . $text);
    echo json_encode($err);
    exit;
}

function success($text = "Ok.", $emoji = null, $data = null) {
    if ($emoji === null) $emoji = "✅";
    $succ = array("status" => "success", "text" => $emoji . " " . $text, "data" => $data);
    echo json_encode($succ);
    exit;
}

if (Config::API_KEY === "") {
    error("API disabled.", "⛔️");
}

if (empty($_GET["key"]) || $_GET["key"] != Config::API_KEY) {
    error("API key incorrect or not given.", "🔐");
}
// else we're good to go!

if (isset($_GET["action"])) {
    switch ($_GET["action"]) {
        case 'add_unread':
            $state = "unread";
        case 'add_archived':
            $state = isset($state) ? $state : "archived";

            if (isset($_GET["url"])) {
                if (Helper::isUrl($_GET["url"])) {

                    // no need to urldecode here since _GET is already decoded
                    $return = Article::add($_GET["url"], $state);
                    if ($return !== true) {
                        error("Could not add: " . $return . ".");
                    } else {
                        success("Article added" . (($state == "archived") ? " and archived" : "") . ".");
                    }
                } else {
                    error("\"" . $_GET["url"] . "\" is not a valid URL.");
                }
            } else {
                error("No URL given. Can't add nothing.");
            }
        break;
        case 'add_quote':
            if (isset($_GET["id"])) {
                if (isset($_GET["quote"])) {

                    // no need to unencode or something, see https://www.php.net/manual/en/function.urldecode.php#refsect1-function.urldecode-notes
                    $quote_id = Quote::add($_GET["quote"], $_GET["id"]);
                    success("Quote added.", null, array("quote_id" => $quote_id));
                } else {
                    error("No quote text given. Can't add nothing.");
                }
            } else {
                error("No article ID given. Can't add a quote to nothing.");
            }
        break;
        case 'remove_quote':
            if (isset($_GET["quote_id"])) {
                Quote::remove($_GET["quote_id"]);
                success("Quote removed.");
            } else {
                error("No quote ID given. Can't remove nothing.");
            }
        break;
        case 'stats_for_widget':
            $start = strtotime("today", time());
            $end = strtotime("tomorrow", time()) - 1;

            // note that reset() unpacks the value of a singleton array
            $unread = intval(Read::getTotalArticleCount("unread"));
            $added_today = reset(Statistics::addedPerTime("days", "all", false, $start, $end));
            $archived_today = reset(Statistics::articlesPerTime("days", "archived", false, $start, $end));
            $reading_time_today = Statistics::totalTimeSpent($start, $end);

            $data = array(
                "unread" => $unread,
                "added_today" => $added_today,
                "archived_today" => $archived_today,
                "reading_time_today" => $reading_time_today
            );
            success($text = "Collected statistics successfully.", null, $data);
        break;
        // any additional actions should be added here
        default:
            error("Invalid action – I don't know what to do for \"" . $_GET["action"] . "\".");
        break;
    }
} else {
    error("No action given. I have successfully done nothing, I guess?");
}
