<?php

class Config {
	// Database settings must be configured in lib/meekrodb.2.3.class.php

	// Backend settings
	public static $allowDuplicateArticles = false;
	public static $searchInURLs = true;

	// Frontend Settings
	public static $showArticlesPerDayGraph = true;
	public static $maxArticlesPerPage = 64;
	public static $openExternalLinksInNewWindow = false;
	public static $keepSearchingWhenChangingState = true;
}

?>
