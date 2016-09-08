<?php

require_once "Config.class.php";

class Helper {
    public static function ago($timestamp, $short = false) {
        $ago = time() - $timestamp;
        if ($ago / 31556926 >= 1) {
            $ago /= 31556926;
            $unit = "year";
        } else if ($ago / 2629744 >= 1) {
            $ago /= 2629744;
            $unit = "month";
        } else if ($ago / 604800 >= 1) {
            $ago /= 604800;
            $unit = "week";
        } else if ($ago / 86400 >= 1) {
            $ago /= 86400;
            $unit = "day";
        } else if ($ago / 3600 >= 1) {
            $ago /= 3600;
            $unit = "hour";
        } else if ($ago / 60 >= 1) {
            $ago /= 60;
            $unit = "minute";
        } else {
            $unit = "second";
        }
        $ago = round($ago);

        // only first letter of unit
        if ($short) {
            return "$ago$unit[0]";
        }

        // pluralize
        if ($ago != 1)
            $unit .= "s";

        return "$ago $unit";
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
        $source = mb_convert_encoding($source, 'utf-8', mb_detect_encoding($source));
        $source = mb_convert_encoding($source, 'html-entities', 'utf-8');
        if ($dom->loadHTML($source)) {
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
        if ($index !== false)
            return substr($haystack, 0, $index) . "<mark>" . substr($haystack, $index, $length) . "</mark>" . self::highlight(substr($haystack, $index + $length), $needle);
        return $haystack;
    }

    public static function getIcon($url) {
        $host = Helper::getHost($url);
        if (empty($host)) {
            $host = $url;
        }
        if (stripos($host, "reddit.com") !== false) {
            $icon = "el-reddit";
        } else if (stripos($host, "digg.com") !== false) {
            $icon = "el-digg";
        } else if (stripos($host, "blogger.com") !== false || stripos($host, "blogspot") !== false) {
            $icon = "el-blogger";
        } else if (stripos($host, "facebook.com") !== false) {
            $icon = "el-facebook";
        } else if (stripos($host, "tumblr.com") !== false) {
            $icon = "el-tumblr";
        } else if (stripos($host, "livejournal.com") !== false) {
            $icon = "el-livejournal";
        } else if (stripos($host, "myspace.com") !== false) {
            $icon = "el-myspace";
        } else if (stripos($host, "twitter.com") !== false) {
            $icon = "el-twitter";
        } else if (stripos($host, "youtube.com") !== false) {
            $icon = "el-youtube";
        } else if (stripos($host, "deviantart.com") !== false) {
            $icon = "el-deviantart";
        } else if (stripos($host, "www.w3.org") !== false) {
            $icon = "el-w3c";
        } else {
            return "";
        }
        return "<i class=\"el $icon\"></i> ";
    }

    public static function isUrl($s) {
        return strtolower(substr($s, 0, 7)) == "http://" || strtolower(substr($s, 0, 8)) == "https://";
    }
}
