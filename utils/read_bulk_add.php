<?php

// Bulk-adds URLs from urls.txt, waiting a few seconds between each request. If
// you've got more than a few URLs in that file, it's strongly recommended to
// run this script on the command line using the "php" executable (as opposed to
// "php-cgi" or similar).

error_reporting(E_ALL);

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../src/DB.class.php";
require_once __DIR__ . "/../src/Article.class.php";

// some websites really dislike empty user agent strings
ini_set("user_agent", "Mozilla/5.0 (compatible; ReAD/1.0; +https://github.com/doersino/ReAD)");

// create/generate this file first, some examples:
// in bash, run: curl http://astoryaweek.com/en/contents.php | grep '<td><a href="display_story.php?story_file=' | cut -d"\"" -f2 | sed -e "s/^/http:\/\/astoryaweek.com\/en\//" | perl -e 'print reverse <>' > urls.txt
// naviate to http://www.asahi-net.or.jp/~xs3d-bull/essays/essays.html, open console, run "document.querySelectorAll("a").forEach(e => console.log(e.href))" and copypaste the relevant ones into urls.txt
$articles = file_get_contents("urls.txt");
$articles = trim($articles);
$articles = explode("\n", $articles);

// prevent duplicate insertion
rename("urls.txt", "urls_added.txt");

$i = 0;
$I = count($articles);
foreach ($articles as $url) {
    $a = @Article::add($url, "unread");
    if ($a === true) {
        echo "Added $i/$I $url\n";

        // wait 1-3s
        usleep((0.1 * rand(10,30)) * 1000000);

        // wait an additional 2-5s
        if ($i % 10 == 0) {
            usleep((0.1 * rand(20,50)) * 1000000);
        }
    }
    else {
        echo "Error while adding $i/$I: $a\n";
    }

    // reset time limit for good measure
    set_time_limit(30);
    $i++;
}
