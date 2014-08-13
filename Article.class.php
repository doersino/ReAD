<?php

require_once "lib/meekrodb.2.3.class.php";
require_once "Helper.class.php";

class Article {
	public static function add($url, $state = "unread", $title = false) {
		if (!Config::$allowDuplicateArticles) {
			$query = DB::query("SELECT * FROM `read` WHERE `url` = %s", $url);
			if (!empty($query))
				return false;
		}

		if (!$title)
			$title = Helper::getTitle($url);
		if ($state === "unread")
			$query = DB::query("INSERT INTO `read` ( `url`, `title`, `time` ) VALUES (%s, %s, %s)", $url, $title, time());
		else if ($state === "archived")
			$query = DB::query("INSERT INTO `read` ( `url`, `title`, `time`, `archived` ) VALUES (%s, %s, %s, %s)", $url, $title, time(), 1);
		else if ($state === "starred")
			$query = DB::query("INSERT INTO `read` ( `url`, `title`, `time`, `archived`, `starred` ) VALUES (%s, %s, %s, %s, %s)", $url, $title, time(), 1, 1);
		return true;
	}

	public static function archive($id) {
		$query = DB::queryFirstRow("SELECT * FROM `read` WHERE `id` = %i", $id);
		if (empty($query) || $query["archived"] == 1)
			return false;

		DB::query("UPDATE `read` SET `archived` = %i, `time` = %i WHERE `id` = %i", 1, time(), $id);
		return true;
	}

	public static function star($id) {
		$query = DB::queryFirstRow("SELECT * FROM `read` WHERE `id` = %i", $id);
		if (empty($query) || $query["starred"] == 1)
			return false;

		DB::query("UPDATE `read` SET `starred` = %i WHERE `id` = %i", 1, $id);
		return true;
	}

	public static function unstar($id) {
		$query = DB::queryFirstRow("SELECT * FROM `read` WHERE `id` = %i", $id);
		if (empty($query) || $query["starred"] == 0)
			return false;

		DB::query("UPDATE `read` SET `starred` = %i WHERE `id` = %i", 0, $id);
		return true;
	}

	public static function remove($id) {
		$query = DB::queryFirstRow("SELECT * FROM `read` WHERE `id` = %i", $id);
		if (empty($query))
			return false;

		DB::query("DELETE FROM `read` WHERE `id` = %i", $id);
		return true;
	}
}

?>
