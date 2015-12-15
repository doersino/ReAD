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
		return $source;
	}

	public static function getTitle($source) {
		if (preg_match("/<title>(.+?)<\/title>/isx", $source, $title))
			return $title[1];
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
