<?php

require_once "lib/meekrodb.2.3.class.php";
require_once "Helper.class.php";

class Article {
	public static function add($url, $state = "unread", $source = false, $title = false) {
		if (!Config::$allowDuplicateArticles) {
			$query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `url` = %s", $url);
			if (!empty($query))
				return false;
		}

		if (!$source)
			$source = Helper::getSource($url);
		if (!$title)
			$title = Helper::getTitle($source, $url);
		if ($state === "unread")
			$query = DB::query("INSERT INTO `read` ( `url`, `source`, `title`, `time` ) VALUES (%s, %s, %s, %s)", $url, $source, $title, time());
		else if ($state === "archived")
			$query = DB::query("INSERT INTO `read` ( `url`, `source`, `title`, `time`, `archived` ) VALUES (%s, %s, %s, %s, %s)", $url, $source, $title, time(), 1);
		else if ($state === "starred")
			$query = DB::query("INSERT INTO `read` ( `url`, `source`, `title`, `time`, `archived`, `starred` ) VALUES (%s, %s, %s, %s, %s, %s)", $url, $source, $title, time(), 1, 1);
		return true;
	}

	public static function archive($id) {
		$query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `id` = %i", $id);
		if (empty($query))
			return false;

		DB::query("UPDATE `read` SET `archived` = %i, `time` = %i WHERE `id` = %i", 1, time(), $id);
		return true;
	}

	public static function star($id) {
		$query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `id` = %i", $id);
		if (empty($query))
			return false;

		DB::query("UPDATE `read` SET `starred` = %i WHERE `id` = %i", 1, $id);
		return true;
	}

	public static function unstar($id) {
		$query = DB::queryFirstField("SELECT 1 FROM `read` WHERE `id` = %i", $id);
		if (empty($query))
			return false;

		DB::query("UPDATE `read` SET `starred` = %i WHERE `id` = %i", 0, $id);
		return true;
	}

	public static function remove($id) {
		$query = DB::queryFirstRow("SELECT 1 FROM `read` WHERE `id` = %i", $id);
		if (empty($query))
			return false;

		DB::query("DELETE FROM `read` WHERE `id` = %i", $id);
		return true;
	}
}

?>
