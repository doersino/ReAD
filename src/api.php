<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Helper.class.php";
require_once __DIR__ . "/Article.class.php";

// some websites really dislike empty user agent strings
ini_set("user_agent", "Mozilla/5.0 (compatible; ReAD/1.0; +https://github.com/doersino/ReAD)");

function error($text, $emoji = "❌") {
    $err = array("status" => "error", "text" => $emoji . " " . $text);
    echo json_encode($err);
    exit;
}

function success($text = "Ok.", $emoji = "✅") {
    $succ = array("status" => "success", "text" => $emoji . " " . $text);
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
        // any additional actions should be added here
        default:
            error("Invalid action – I don't know what to do for \"" . $_GET["action"] . "\".");
        break;
    }
} else {
    error("No action given. I have successfully done nothing, I guess?");
}
