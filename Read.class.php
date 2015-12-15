<?php

require_once "lib/meekrodb.2.3.class.php";
require_once "Config.class.php";
require_once "Helper.class.php";

class Read {
	public static function getFirstArticleTime() {
		$query = DB::queryFirstField("SELECT `time` FROM `read` WHERE `archived` = 1 ORDER BY `time` ASC LIMIT 1");
		if (empty($query))
			return time();

		return $query;
	}

	public static function getTotalArticleCount($state = false) {
		$totalArticleCount["unread"] = DB::queryFirstField("SELECT COUNT(*) AS 'count' FROM `read` WHERE `archived` = %i", 0);
		$totalArticleCount["archived"] = DB::queryFirstField("SELECT COUNT(*) AS 'count' FROM `read` WHERE `archived` = %i", 1);
		$totalArticleCount["starred"] = DB::queryFirstField("SELECT COUNT(*) AS 'count' FROM `read` WHERE `starred` = %i", 1);

		if ($state === "unread")
			return $totalArticleCount["unread"];
		else if ($state === "archived")
			return $totalArticleCount["archived"];
		else if ($state === "starred")
			return $totalArticleCount["starred"];
		else
			return $totalArticleCount;
	}

	public static function getArticles($state, $offset, $limit) {
		if ($state === "unread")
			$query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 0, $limit, $offset);
		else if ($state === "archived")
			$query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 1, $limit, $offset);
		else if ($state === "starred")
			$query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `starred` = %i ORDER BY `time` DESC LIMIT %i OFFSET %i", 1, $limit, $offset);
		else
			return false;

		for ($i = 0; $i < count($query); ++$i) {
			$query[$i]["url"] = htmlspecialchars($query[$i]["url"], ENT_QUOTES, "UTF-8");
			if (empty($query[$i]["title"]))
				$query[$i]["title"] = "[no title found]";
		}
		return $query;
	}

	public static function getSearchResults($state, $search) {
		if ($state === "unread")
			$query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC", 0);
		else if ($state === "archived")
			$query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `archived` = %i ORDER BY `time` DESC", 1);
		else if ($state === "starred")
			$query = DB::query("SELECT `id`, `url`, `title`, `time`, `starred` FROM `read` WHERE `starred` = %i ORDER BY `time` DESC", 1);
		else
			return false;

		$rows = array();
		foreach ($query as $row) {
			$row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
			if (stripos($row["title"], $search) !== false || Config::$searchInURLs && stripos($row["url"], $search) !== false || stripos(Helper::getHost($row["url"]), $search) !== false) {
				if (empty($row["title"]))
					$row["title"] = "[no title found]";
				$rows[] = $row;
			} else
				continue;
		}
		return $rows;
	}

	public static function getArticlesPerDay($state, $search = false) {
		if ($state === "unread") {
			if ($search)
				$query = DB::query("SELECT `url`, `title`, `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 0);
			else
				$query = DB::query("SELECT `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 0);
		} else if ($state === "archived") {
			if ($search)
				$query = DB::query("SELECT `url`, `title`, `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 1);
			else
				$query = DB::query("SELECT `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 1);
		} else if ($state === "starred") {
			if ($search)
				$query = DB::query("SELECT `url`, `title`, `time` FROM `read` WHERE `starred` = %i ORDER BY `time` ASC", 1);
			else
				$query = DB::query("SELECT `time` FROM `read` WHERE `starred` = %i ORDER BY `time` ASC", 1);
		} else
			return false;

		$days = array(0);
		$tempDay = Helper::getDay(self::getFirstArticleTime());
		foreach ($query as $row) {
			if ($search) {
				$row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
			}
			$relevant = !$search || $search && (stripos($row["title"], $search) !== false || Config::$searchInURLs && stripos($row["url"], $search) !== false  || stripos(Helper::getHost($row["url"]), $search) !== false);
			if (Helper::getDay($row["time"]) == $tempDay) { // same day
				if ($relevant)
					$days[count($days) - 1]++;
			} else { // new day
				while (Helper::getDay($row["time"]) > $tempDay + 1) { // days with no articles
					$days[] = 0;
					$tempDay++;
				}
				if ($relevant)
					$days[] = 1;
				else
					$days[] = 0;
				$tempDay++;
			}
		}
		while (Helper::getDay(time()) > $tempDay) { // days after latest article
			$days[] = 0;
			$tempDay++;
		}

		return implode(",", $days);
	}

	public static function getArticlesPerTime($stepsize, $state, $search = false) {
		if ($state === "unread") {
			if ($search)
				$query = DB::query("SELECT `url`, `title`, `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 0);
			else
				$query = DB::query("SELECT `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 0);
		} else if ($state === "archived") {
			if ($search)
				$query = DB::query("SELECT `url`, `title`, `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 1);
			else
				$query = DB::query("SELECT `time` FROM `read` WHERE `archived` = %i ORDER BY `time` ASC", 1);
		} else if ($state === "starred") {
			if ($search)
				$query = DB::query("SELECT `url`, `title`, `time` FROM `read` WHERE `starred` = %i ORDER BY `time` ASC", 1);
			else
				$query = DB::query("SELECT `time` FROM `read` WHERE `starred` = %i ORDER BY `time` ASC", 1);
		} else
			return false;

		$unit = substr($stepsize, 0, -1); // plural -> singular

		$times = array(0);
		$tempTime = Helper::getTime($unit, self::getFirstArticleTime());
		foreach ($query as $row) {
			if ($search) {
				$row["url"] = htmlspecialchars($row["url"], ENT_QUOTES, "UTF-8");
			}
			$relevant = !$search || $search && (stripos($row["title"], $search) !== false || Config::$searchInURLs && stripos($row["url"], $search) !== false  || stripos(Helper::getHost($row["url"]), $search) !== false);
			if (Helper::getTime($unit, $row["time"]) == $tempTime) { // same day
				if ($relevant)
					$times[count($times) - 1]++;
			} else { // new day
				while (Helper::getTime($unit, $row["time"]) > $tempTime + 1) { // days with no articles
					$times[] = 0;
					$tempTime++;
				}
				if ($relevant)
					$times[] = 1;
				else
					$times[] = 0;
				$tempTime++;
			}
		}
		while (Helper::getTime($unit, time()) > $tempTime) { // days after latest article
			$times[] = 0;
			$tempTime++;
		}

		return implode(",", $times);
	}
}

?>
