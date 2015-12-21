<?php

require_once "lib/meekrodb.2.3.class.php";

// Database settings
DB::$dbName = "";
DB::$user = "";
DB::$password = "";
DB::$host = "localhost";

class Config {
	// Backend settings
	public static $allowDuplicateArticles = false;
	public static $searchInURLs = true;

	// Frontend Settings
	public static $showArticlesPerTimeGraph = true;
    public static $articlesPerTimeGraphTimeStepSize = "weeks"; // days, weeks, months or years
	public static $maxArticlesPerPage = 64;
	public static $openExternalLinksInNewWindow = false;
	public static $keepSearchingWhenChangingState = true;
	public static $showBrandIcons = true;
}

?>
