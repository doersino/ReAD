<?php

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

		if ($short) {
			return "$ago$unit[0]";
		}
		if ($ago != 1)
			$unit .= "s";
		return "$ago $unit";
	}

	/**
	 * Returns number of day, week, month or year corresponding to a timestamp,
	 * should account for DST (assuming your server does).
	 *
	 * Used in Read::getArticlesPerDay().
	 *
	 * @param string $unit day, week, month or year
	 * @param string|int $timestamp
	 * @return int number of day
	 */
	public static function getTime($unit, $timestamp) {
		if ($unit == "week")
			$secondsPerUnit = 86400 * 7;
		else if ($unit == "month")
			$secondsPerUnit = 86400 * 7 * 4.35;
		else if ($unit == "year")
			$secondsPerUnit = 86400 * 7 * 52.18;
		else // day
			$secondsPerUnit = 86400;
		return floor(($timestamp + date("Z") - (date("I") * 3600)) / $secondsPerUnit);
	}

	public static function getHost($url) {
		return parse_url($url, PHP_URL_HOST);
	}

	public static function getSource($url) {
		if (!($source = @file_get_contents($url)))
			return false;
		if (!($decodedSource = @gzinflate($source)))
			return $source;
		return $decodedSource;
	}

	public static function getTitle($source) {
		// fancy way: try getting the title from <meta property="og:title" content="...">
		// or similarly twitter:title, <title>, og:description and twitter:description
		$dom = new DOMDocument();
		if ($dom->loadHTML($source)) {
			$xpath = new DomXpath($dom);

			// try getting a title from og:title or twitter:title
			if ($ogTitle = $xpath->query('//meta[@property="og:title"][1]')->item(0)) {
				if ($ogTitle->getAttribute("content") !== "") {
					return utf8_decode($ogTitle->getAttribute("content"));
				}
			}
			if ($twitterTitle = $xpath->query('//meta[@name="twitter:title"][1]')->item(0)) {
				if ($twitterTitle->getAttribute("content") !== "") {

					// fix for Tumblr setting the blog name as twitter:title on non-text posts
					if (!strpos($source, $twitterTitle->getAttribute("content") . ".tumblr.com")) {
						return utf8_decode($twitterTitle->getAttribute("content"));
					}
				}
			}

			// try getting the content of the <title> element
			$list = $dom->getElementsByTagName("title");
			if ($list->length > 0) {
				if ($list->item(0)->textContent !== "") {
					return utf8_decode($list->item(0)->textContent);
				}
			}

			// if nothing has worked so far, try og:description and twitter:description
			if ($ogDescription = $xpath->query('//meta[@property="og:description"][1]')->item(0)) {
				if ($ogDescription->getAttribute("content") !== "") {
					return utf8_decode($ogDescription->getAttribute("content"));
				}
			}
			if ($twitterDescription = $xpath->query('//meta[@name="twitter:description"][1]')->item(0)) {
				if ($twitterDescription->getAttribute("content") !== "") {
					return utf8_decode($twitterDescription->getAttribute("content"));
				}
			}
		}

		// simple regex way, might work if the html is severely malformed but the title isn't
		if (preg_match("/<title>(.+?)<\/title>/isx", $source, $title))
			return $title[1];

		// welp, we've tried everything we could
		return "";
	}

	public static function highlight($haystack, $needle) {
		$index = stripos($haystack, $needle);
		$length = strlen($needle);
		if ($index !== false)
			return substr($haystack, 0, $index) . "<mark>" . substr($haystack, $index, $length) . "</mark>" . self::highlight(substr($haystack, $index + $length), $needle);
		return $haystack;
	}
}

?>
