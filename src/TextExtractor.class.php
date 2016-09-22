<?php

require_once __DIR__ . "/../deps/php-boiler-pipe/vendor/autoload.php";
require_once __DIR__ . "/DB.class.php";

class TextExtractor {
    public static function extractText($source) {
        if (empty($source)) {
            return "";
        }

        $ae = new DotPack\PhpBoilerPipe\ArticleExtractor();
        $text = @$ae->getContent($source);
        return $text;
    }

    public static function countWords($text) {
        //return count(explode(" ", $data));
        return str_word_count($text);
    }

    public static function computeErt($wordcount) {
        $ert = $wordcount / Config::WPM;

        // convert to seconds
        $ert *= 60;

        // add some time for context switching when starting to read, note that
        // this makes this function non-linear
        if ($ert > 0) {
            $ert += 30;
        }

        return $ert;
    }
}
