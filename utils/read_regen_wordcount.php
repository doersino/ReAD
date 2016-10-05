<?php

// Regenerates the word count for all articles. Useful if you've made manual
// changes to the read_texts table.

error_reporting(E_ALL);

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../src/DB.class.php";
require_once __DIR__ . "/../src/TextExtractor.class.php";

$allArticles = DB::query("SELECT * FROM `read_texts`");
$N = count($allArticles);

foreach ($allArticles as $n => $article) {
    $id = $article["id"];
    $wordcount = TextExtractor::countWords($article["text"]);

    // print progress
    echo round(10000 * ($n / $N)) / 100 . "%\t";
    echo "$id\t";
    echo "$wordcount\n";

    // save to database
    DB::query("UPDATE `read` SET `wordcount` = %i WHERE `id` = %i", $wordcount, $id);
}
