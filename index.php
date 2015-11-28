<?php

require_once "Config.class.php";
require_once "Helper.class.php";
require_once "Article.class.php";
require_once "Read.class.php";

// some websites dislike empty user agent strings
ini_set("user_agent", "Mozilla/5.0 (compatible; ReAD/1.0; +https://github.com/doersino/ReAD)");

// make sure we're always in a valid state
if ($_GET["state"] !== "unread" && $_GET["state"] !== "archived" && $_GET["state"] !== "starred") {
	header("Location: index.php?state=unread");
	exit;
} else
	$state = $_GET["state"];

// handle search and offset
if (!empty($_GET["s"]))
	$search = htmlspecialchars($_GET["s"], ENT_QUOTES, "UTF-8");
if (!empty($_GET["offset"]))
	$offset = intval($_GET["offset"]);
else
	$offset = 0;

// handle user actions/changes to the database
if (isset($_POST["archive"]) && isset($_POST["id"]))
	$return = Article::archive($_POST["id"]);
if (isset($_POST["star"]) && isset($_POST["id"]))
	$return = Article::star($_POST["id"]);
if (isset($_POST["unstar"]) && isset($_POST["id"]))
	$return = Article::unstar($_POST["id"]);
if (isset($_POST["remove"]) && isset($_POST["id"]))
	$return = Article::remove($_POST["id"]);
if (isset($_REQUEST["search"]) && isset($_REQUEST["query"])) {
	if (empty($_REQUEST["query"]))
		$return = true;
	else if (substr($_REQUEST["query"], 0, 7) == "http://" || substr($_REQUEST["query"], 0, 8) == "https://")
		$return = Article::add($_REQUEST["query"], $state);
	else {
		header("Location: index.php?state=$state&s=" . rawurlencode($_REQUEST["query"]));
		exit;
	}
}
if (isset($return)) {
	if ($return) {
		header("Location: index.php?state=" . $state . ((isset($search)) ? "&s=" . rawurlencode($_GET["s"]) : "") . (($offset > 0) ? "&offset=$offset" : ""));
		exit;
	} else {
		exit("An error occured. Most likely, you've tried adding an artice you've already added a while ago. Try refreshing this page or go back to the previous page.");
	}
}

// get article count for each state for display in header
$totalArticleCount = Read::getTotalArticleCount();

// get articles and build title
if (isset($search)) {
	$articles = Read::getSearchResults($state, $search);
	$title = count($articles) . " $state article" . ((count($articles) == 1) ? "" : "s") . " matching \"$search\"";
} else {
	$articles = Read::getArticles($state, $offset, Config::$maxArticlesPerPage);

	if ($state === "unread" && empty($articles))
		$title = "Inbox Zero";
	else
		$title = $totalArticleCount[$state] . " $state article" . ((count($articles) == 1) ? "" : "s");
}

// get graph data depending on current state
if (Config::$showArticlesPerDayGraph) {
	if ($state === "unread" || isset($search) && empty($articles))
		$articlesPerDay = Read::getArticlesPerDay("archived");
	else if (isset($search))
		$articlesPerDay = Read::getArticlesPerDay($state, $search);
	else
		$articlesPerDay = Read::getArticlesPerDay($state);
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo $title; ?> - ReAD</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="favicon.gif">
	<link rel="stylesheet" href="lib/elusive-webfont.css">
	<link rel="stylesheet" href="style.css">
<?php if (Config::$showArticlesPerDayGraph) { ?>
	<script src="lib/jquery.min.js"></script>
	<script src="lib/jquery.sparkline.min.js"></script>
	<script>
		$(function() {
			var values = [<?php echo $articlesPerDay; ?>];
			$('.sparkline').sparkline(values, {type: 'line', width: '100%', height: '100%', lineColor: '#ddd', fillColor: '#eee', spotColor: false, minSpotColor: false, maxSpotColor: false, disableInteraction: true});
		});
	</script>
<?php } ?>
</head>
<body>
<?php if (Config::$showArticlesPerDayGraph) { ?>
	<div class="sparkline"></div>
<?php } ?>
	<header>
		<nav>
			<a href="index.php" class="read"><strong>ReAD</strong></a>
			<a href="index.php?state=unread<?php if (Config::$keepSearchingWhenChangingState && isset($search)) echo "&amp;s=" . rawurlencode($_GET["s"]); ?>"<?php if ($_GET["state"] === "unread") echo " class=\"current\""; ?> title="Unread"><span class="icon">&#xe69c;</span> <?php echo $totalArticleCount["unread"]; ?></a>
			<a href="index.php?state=archived<?php if (Config::$keepSearchingWhenChangingState && isset($search)) echo "&amp;s=" . rawurlencode($_GET["s"]); ?>"<?php if ($_GET["state"] === "archived") echo " class=\"current\""; ?> title="Archived"><span class="icon">&#xe67a;</span> <?php echo $totalArticleCount["archived"]; ?></a>
			<a href="index.php?state=starred<?php if (Config::$keepSearchingWhenChangingState && isset($search)) echo "&amp;s=" . rawurlencode($_GET["s"]); ?>"<?php if ($_GET["state"] === "starred") echo " class=\"current\""; ?> title="Starred"><span class="icon">&#xe634;</span> <?php echo $totalArticleCount["starred"]; ?></a>
		</nav>
		<nav class="pages">
<?php if (!isset($search) && $totalArticleCount[$state] > $offset && $offset != 0) { ?>
			<a href="index.php?state=<?php echo $state; if ($offset - Config::$maxArticlesPerPage > 0) echo "&amp;offset=" . ($offset - Config::$maxArticlesPerPage); ?>" class="icon" title="Newer">&#xe6fd;</a>
<?php }
if (!isset($search) && $totalArticleCount[$state] > $offset + Config::$maxArticlesPerPage) { ?>
			<a href="index.php?state=<?php echo $state . "&amp;offset=" . ($offset + Config::$maxArticlesPerPage); ?>" class="icon" title="Older">&#xe6fc;</a>
<?php } ?>
		</nav>
		<form action="index.php?state=<?php echo $state; ?>" method="post">
<?php if (isset($search)) { ?>
			<a href="index.php?state=<?php echo $state; ?>" class="clear icon">&#xe653;</a>
<?php } ?>
			<input type="text" name="query" class="query" value="<?php if (isset($search)) echo $search; ?>" autofocus="autofocus" placeholder="Add or Search <?php echo ucfirst($state); ?> Articles">
			<input type="submit" name="search" class="submit">
		</form>
	</header>
	<main>
<?php if (empty($articles)) { ?>
		<div class="notice"><?php echo (isset($search) || $state !== "unread") ? "Found $title." : $title; ?></div>
<?php } else { ?>
		<table>
<?php foreach ($articles as $article) { ?>
			<tr>
				<td class="ago"><abbr title="<?php echo date("Y-m-d H:i:s", $article["time"]); ?>"><?php echo Helper::ago($article["time"], true); ?></abbr></td>
				<td class="title">
					<a href="<?php echo $article["url"]; ?>" class="title"<?php if (Config::$openExternalLinksInNewWindow) echo " target=\"_blank\""; ?>><?php if (isset($search)) echo Helper::highlight($article["title"], $search); else echo $article["title"]; ?></a>
					<a href="index.php?state=<?php echo "$state&amp;s=" . rawurlencode(Helper::getHost($article["url"])); ?>" class="host"><?php if (isset($search)) echo Helper::highlight(Helper::getHost($article["url"]), $search); else echo Helper::getHost($article["url"]); ?></a>
					<div class="actions">
						<form action="index.php?state=<?php echo $state . ((isset($search)) ? "&s=" . rawurlencode($_GET["s"]) : "") . (($offset > 0) ? "&offset=$offset" : ""); ?>" method="post">
							<input type="hidden" name="id" value="<?php echo $article["id"]; ?>">
<?php if ($state === "unread") { ?>
							<input type="submit" name="archive" value="&#xe67a;">
<?php } else { ?>
							<input type="submit" name="<?php echo ($article["starred"] == 1) ? "unstar" : "star"; ?>" value="<?php echo ($article["starred"] == 1) ? "&#xe634;" : "&#xe632;"; ?>">
<?php } ?>
							<input type="submit" name="remove" value="&#xe61e;">
						</form>
					</div>
				</td>
				<td class="actions">
					<form action="index.php?state=<?php echo $state . ((isset($search)) ? "&s=" . rawurlencode($_GET["s"]) : "") . (($offset > 0) ? "&offset=$offset" : ""); ?>" method="post">
						<input type="hidden" name="id" value="<?php echo $article["id"]; ?>">
<?php if ($state === "unread") { ?>
						<input type="submit" name="archive" value="&#xe67a;">
<?php } else { ?>
						<input type="submit" name="<?php echo ($article["starred"] == 1) ? "unstar" : "star"; ?>" value="<?php echo ($article["starred"] == 1) ? "&#xe634;" : "&#xe632;"; ?>">
<?php } ?>
						<input type="submit" name="remove" value="&#xe61e;">
					</form>
				</td>
			</tr>
<?php } ?>
		</table>
<?php } ?>
	</main>
</body>
</html>
