<?php

require_once __DIR__ . "/../config.php";

class Helper {
    public static function ago($timestamp, $short = false) {
        $ago = time() - $timestamp;
        return self::makeTimeHumanReadable($ago, $short);
    }

    // with min and max, an allowable range of units can be specified, and with
    // factor e.g. = 2, you can achieve that the unit is only changed once the
    // larger unit's value is at least 2, e.g. from hours to days at 48h/2d
    public static function makeTimeHumanReadable($seconds, $short = false, $min = false, $max = false, $factor = 1) {
        // handle arguments
        if ($min == false) {
            $min = "second";
        }
        if ($max == false) {
            $max = "year";
        }

        // determine largest unit where resulting number is not smaller than 1
        $secondsPer = array(
            "year"   => 31556926,
            "month"  => 2629744,
            "week"   => 604800,
            "day"    => 86400,
            "hour"   => 3600,
            "minute" => 60,
            "second" => 1
        );

        if ($seconds / $secondsPer["year"] >= $factor) {
            $unit = "year";
        } else if ($seconds / $secondsPer["month"] >= $factor) {
            $unit = "month";
        } else if ($seconds / $secondsPer["week"] >= $factor) {
            $unit = "week";
        } else if ($seconds / $secondsPer["day"] >= $factor) {
            $unit = "day";
        } else if ($seconds / $secondsPer["hour"] >= $factor) {
            $unit = "hour";
        } else if ($seconds / $secondsPer["minute"] >= $factor) {
            $unit = "minute";
        } else {
            $unit = "second";
        }

        // clamp unit
        if ($secondsPer[$min] > $secondsPer[$unit]) {
            $unit = $min;
        }
        if ($secondsPer[$max] < $secondsPer[$unit]) {
            $unit = $max;
        }

        // compute value
        $time = $seconds / $secondsPer[$unit];
        $time = round($time);

        // only first letter of unit
        if ($short) {
            return "$time$unit[0]";
        }

        // pluralize
        if ($time != 1)
            $unit .= "s";

        return "$time $unit";
    }

    // based on http://stackoverflow.com/a/4123825
    public static function isTimestamp($timestamp) {
        return ((string) (int) $timestamp === $timestamp)
               && ($timestamp <= PHP_INT_MAX)
               && ($timestamp >= ~PHP_INT_MAX)
               //&& (!strtotime($timestamp));
               && is_numeric($timestamp) && strlen($timestamp) > 5;
    }

    public static function getHost($url) {
        return parse_url($url, PHP_URL_HOST);
    }

    public static function getSource($url) {
        if (!($source = @file_get_contents($url)))
            return false;

        // try ungzipping
        if ($decodedSource = @gzinflate(substr($source,10)))
            return $decodedSource;
        if ($decodedSource = @gzinflate($source))
            return $decodedSource;
        return $source;
    }

    public static function getTitle($source, $url="") {
        // fancy way: try getting the title from <meta property="og:title" content="...">
        // or similarly twitter:title, <title>, og:description and twitter:description
        // juggle the encoding around to make things work more often than not, according to
        // http://stackoverflow.com/questions/2142120/php-encoding-with-domdocument
        $dom = new DOMDocument('1.0', 'UTF-8');
        if (mb_detect_encoding($source) !== false) {
            $source = mb_convert_encoding($source, 'utf-8', mb_detect_encoding($source));
        }
        #$source = mb_convert_encoding($source, 'html-entities', 'utf-8');  // deprecated and (i think) not necessary anyway, else see https://php.watch/versions/8.2/mbstring-qprint-base64-uuencode-html-entities-deprecated

        if (strlen($source) !== 0) {
            @$dom->loadHTML($source);
            $xpath = new DomXpath($dom);

            // try getting a title from og:title or twitter:title
            if (stripos(Helper::getHost($url), "reddit.com") === false) { // fix for Reddit posts and comments
                if ($ogTitle = $xpath->query('//meta[@property="og:title"][1]')->item(0)) {
                    if ($ogTitle->getAttribute("content") !== "" && !ctype_space($ogTitle->getAttribute("content"))) {
                        return $ogTitle->getAttribute("content");
                    }
                }
                if ($twitterTitle = $xpath->query('//meta[@name="twitter:title"][1]')->item(0)) {
                    if ($twitterTitle->getAttribute("content") !== "" && !ctype_space($twitterTitle->getAttribute("content"))) {
                        if (stripos($source, "blogName=" . $twitterTitle->getAttribute("content") === false)) { // fix for Tumblr setting the blog name as twitter:title on non-text posts
                            return $twitterTitle->getAttribute("content");
                        }
                    }
                }
            }

            // try getting the content of the <title> element
            $list = $dom->getElementsByTagName("title");
            if ($list->length > 0) {
                if ($list->item(0)->textContent !== "" && !ctype_space($list->item(0)->textContent)) {
                    return $list->item(0)->textContent;
                }
            }

            // if nothing has worked so far, try og:description and twitter:description
            if ($ogDescription = $xpath->query('//meta[@property="og:description"][1]')->item(0)) {
                if ($ogDescription->getAttribute("content") !== "" && !ctype_space($ogDescription->getAttribute("content"))) {
                    return $ogDescription->getAttribute("content");
                }
            }
            if ($twitterDescription = $xpath->query('//meta[@name="twitter:description"][1]')->item(0)) {
                if ($twitterDescription->getAttribute("content") !== "" && !ctype_space($twitterDescription->getAttribute("content"))) {
                    return $twitterDescription->getAttribute("content");
                }
            }

            // let's try h1 and h2 tags as well
            $h1s = $dom->getElementsByTagName("h1");
            if ($h1s->length > 0) {
                if ($h1s->item(0)->textContent !== "" && !ctype_space($h1s->item(0)->textContent)) {
                    return $h1s->item(0)->textContent;
                }
            }
            $h2s = $dom->getElementsByTagName("h2");
            if ($h2s->length > 0) {
                if ($h2s->item(0)->textContent !== "" && !ctype_space($h2s->item(0)->textContent)) {
                    return $h2s->item(0)->textContent;
                }
            }
        }

        // simple regex way, might work if the html is severely malformed but the title tag isn't
        if (preg_match("/<title>(.+?)<\/title>/isx", $source, $title))
            return $title[1];

        // same things for headlines
        if (preg_match("/<h1>(.+?)<\/h1>/isx", $source, $title))
            return $title[1];
        if (preg_match("/<h2>(.+?)<\/h2>/isx", $source, $title))
            return $title[1];

        // welp, guess we've tried everything we could
        return "";
    }

    public static function highlight($haystack, $needle) {
        $index = stripos($haystack, $needle);
        $length = strlen($needle);
        if ($index !== false) {
            return substr($haystack, 0, $index) . "<mark>" . substr($haystack, $index, $length) . "</mark>" . self::highlight(substr($haystack, $index + $length), $needle);
        }
        return $haystack;
    }

    public static function isUrl($s) {
        return strtolower(substr($s, 0, 7)) == "http://" || strtolower(substr($s, 0, 8)) == "https://";
    }
}
