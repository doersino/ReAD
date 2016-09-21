<?php

require_once "Config.class.php";

require "deps/php-boiler-pipe/vendor/autoload.php";

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
        return 60 * $wordcount / Config::WPM;
    }
}
