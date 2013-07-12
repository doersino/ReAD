<?php

class read {
	// Database settings
	private $mysql_host = "";
	private $mysql_user = "";
	private $mysql_pass = "";
	private $mysql_db = "";
	private $mysql_table = "ReAD";

	// Settings
	public $since = "April 2013";
	public $hide_url = false;
	public $open_links_in_new_window = false;
	public $display_limit = 40;

	// This is the part of the code that's supposed to just work without any changes
	private $mysql;

	private function connectDB() {
		if (!$this->mysql) {
			$this->mysql = mysql_connect($this->mysql_host, $this->mysql_user, $this->mysql_pass);
			if (!$this->mysql) die('Could not connect: ' . mysql_error());
			mysql_select_db($this->mysql_db, $this->mysql);
		}
	}

	private function closeDB() {
		if ($this->mysql) {
			mysql_close($this->mysql);
			$this->mysql = null;
		}
	}

	private function getSource($url) {
		if (($source = @file_get_contents($url)) == false) return false;
		return $source;
	}

	private function getTitle($url) {
		$source = $this->getSource($url);
		if (preg_match("/<title>(.+?)<\/title>/isx", $source, $title)) return $title[1];
		return "no title found";
	}

	public function addArticle($url, $title = false, $starred = false) {
		$this->connectDB();
		$query = mysql_query(sprintf("SELECT * FROM `" . $this->mysql_table . "` WHERE `URL` = '%s'", mysql_real_escape_string(rawurlencode($url))));
		if (!$query) die('Could not query database: ' . mysql_error());
		if (mysql_num_rows($query) > 0) return "Could not add article: already read";

		if ($title) $title = rawurlencode($title);
		else $title = rawurlencode($this->getTitle($url));
		$url = rawurlencode($url);
		$query = mysql_query(sprintf("INSERT INTO `" . $this->mysql_table . "` ( `URL`, `Title`, `TimeAdded`, `Starred` ) VALUES ( '%s', '%s', '%s', '%s' )", mysql_real_escape_string($url), mysql_real_escape_string($title), time(), mysql_real_escape_string($starred)));
		if (!$query) die('Could not add article: ' . mysql_error());
		$this->closeDB();
		return sprintf("Added \"%s\"", rawurldecode($title));
	}

	public function editTitle($id, $title) { // Currently ununsed
		$this->connectDB();
		$query = mysql_query(sprintf("SELECT * FROM `" . $this->mysql_table . "` WHERE `ID` = '%s'", mysql_real_escape_string($id)));
		if (!$query) die('Could not query database: ' . mysql_error());
		if (mysql_num_rows($query) < 1) return false;

		$title = rawurlencode($title);
		mysql_query(sprintf("UPDATE `" . $this->mysql_table . "` SET `Title` = '%s' WHERE `ID` = '%s'", mysql_real_escape_string($title), mysql_real_escape_string($id)));
		$this->closeDB();
		return sprintf("Updated \"%s\"", rawurldecode($title));
	}

	public function toggleStarred($id) {
		$this->connectDB();
		$query = mysql_query(sprintf("SELECT * FROM `" . $this->mysql_table . "` WHERE `ID` = '%s'", mysql_real_escape_string($id)));
		if (!$query) die('Could not query database: ' . mysql_error());
		if (mysql_num_rows($query) < 1) return false;

		while ($row = mysql_fetch_object($query)) {
			if ($row->Starred == 1) $starred = 0;
			else $starred = 1;
			mysql_query(sprintf("UPDATE `" . $this->mysql_table . "` SET `Starred` = '%s' WHERE `ID` = '%s'", mysql_real_escape_string($starred), mysql_real_escape_string($id)));
			$title = rawurldecode($row->Title);
		}
		$this->closeDB();
		return sprintf((($starred == 1) ? "Starred" : "Unstarred") . " \"%s\"", $title);
	}

	public function removeArticle($id) {
		$this->connectDB();
		$query = mysql_query(sprintf("SELECT * FROM `" . $this->mysql_table . "` WHERE `ID` = '%s'", mysql_real_escape_string($id)));
		if (!$query) die('Could not query database: ' . mysql_error());
		if (mysql_num_rows($query) < 1) return false;

		mysql_query(sprintf("DELETE FROM `" . $this->mysql_table . "` WHERE `ID` = '%s'", mysql_real_escape_string($id)));
		$title = rawurldecode(mysql_fetch_object($query)->Title);
		$this->closeDB();
		return sprintf("Removed \"%s\"", $title);
	}

	public function getArticleCount() {
		$this->connectDB();
		$query = mysql_query("SELECT * FROM `" . $this->mysql_table . "`");
		if (!$query) die('Could not query database: ' . mysql_error());

		$count = mysql_num_rows($query);
		$this->closeDB();
		return $count;
	}

	public function getArticle($id) { // Currently ununsed
		$this->connectDB();
		$query = mysql_query(sprintf("SELECT * FROM `" . $this->mysql_table . "` WHERE `ID` = '%s'", mysql_real_escape_string($id)));
		if (!$query) die('Could not query database: ' . mysql_error());
		if (mysql_num_rows($query) < 1) return false;

		$row_object = mysql_fetch_object($query);
		$row = array(
			"ID" => $row_object->ID,
			"URL" => htmlspecialchars(rawurldecode($row_object->URL), ENT_QUOTES, 'UTF-8'),
			"Title" => rawurldecode($row_object->Title),
			"TimeAdded" => $row_object->TimeAdded,
			"Starred" => $row_object->Starred
		);
		$this->closeDB();
		return $row;
	}

	public function getArticles($offset, $limit, $search = false) {
		$this->connectDB();
		$query = mysql_query(sprintf("SELECT * FROM `" . $this->mysql_table . "` ORDER BY `TimeAdded` DESC LIMIT %s OFFSET %s", mysql_real_escape_string($limit), mysql_real_escape_string($offset)));
		if (!$query) die('Could not query database: ' . mysql_error());

		$rows = array();
		while ($row = mysql_fetch_object($query)) {
			$url = htmlspecialchars(rawurldecode($row->URL), ENT_QUOTES, 'UTF-8');
			$title = rawurldecode($row->Title);
			if (!$search || $search && (stripos($url, $search) !== false || stripos($title, $search) !== false || strcasecmp($search, "starred") == 0 && $row->Starred == 1)) {
				array_push($rows, array(
					"ID" => $row->ID,
					"URL" => $url,
					"Title" => $title,
					"TimeAdded" => $row->TimeAdded,
					"Starred" => $row->Starred
				));
			} else continue;
		}
		$this->closeDB();
		return $rows;
	}

	public function ago($timestamp) {
		$ago = time() - $timestamp;
		if ($ago / 31556926 > 1) {
			$ago /= 31556926;
			$unit = "year";
		} else if ($ago / 2629744 > 1) {
			$ago /= 2629744;
			$unit = "month";
		} else if ($ago / 604800 > 1) {
			$ago /= 604800;
			$unit = "week";
		} else if ($ago / 86400 > 1) {
			$ago /= 86400;
			$unit = "day";
		} else if ($ago / 3600 > 1) {
			$ago /= 3600;
			$unit = "hour";
		} else if ($ago / 60 > 1) {
			$ago /= 60;
			$unit = "minute";
		} else {
			$unit = "second";
		}
		$ago = round($ago);
		if ($ago != 1) $unit .= "s";
		return "$ago $unit";
	}

	public function highlight($haystack, $needle) {
		$index = stripos($haystack, $needle);
		$length = strlen($needle);
		if ($index !== false) return substr($haystack, 0, $index) . "<mark>" . substr($haystack, $index, $length) . "</mark>" . $this->highlight(substr($haystack, $index + $length), $needle);
		else return $haystack;
	}

}

?>