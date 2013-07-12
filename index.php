<?php
$runtime = microtime(true);
include "read_backend.php";
$r = new read();

$return = "";
if (isset($_POST['edit_title']) && isset($_POST['id']) && isset($_POST['title'])) // Currently unused
	$return .= $r->editTitle($_POST['id'], $_POST['title']);
if (isset($_POST['star']) && isset($_POST['id']))
	$return .= $r->toggleStarred($_POST['id']);
if (isset($_POST['remove']) && isset($_POST['id']))
	$return .= $r->removeArticle($_POST['id']);
if (isset($_REQUEST['search']) && isset($_REQUEST['query']) && !empty($_REQUEST['query'])) {
	if (substr($_REQUEST['query'], 0, 7) == "http://" || substr($_REQUEST['query'], 0, 8) == "https://")
		$return .= $r->addArticle($_REQUEST['query']);
	else {
		header('Location: index.php?s=' . rawurlencode($_REQUEST['query']));
		exit;
	}
}
if (isset($_GET['s']))
	$search = htmlspecialchars(rawurldecode($_GET['s']), ENT_QUOTES, 'UTF-8');
if (isset($_GET['offset']))
	$offset = intval($_GET['offset']);
else $offset = 0;

$firstArticleTime = $r->getFirstArticleTime();
$articleCount = $r->getArticleCount();
if (isset($search)) $articles = $r->getArticles(0, 9999999, $search);
else $articles = $r->getArticles($offset, $r->display_limit);

?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<title><?php
if (isset($search)) {
	$results = count($articles);
	if ($results == 0) echo "Nothing found";
	else if ($results == 1) echo $results . " result";
	else echo $results . " results";
	echo " for \"" . $search . "\" - ";
}
?>
ReAD</title>
	<link rel="stylesheet" type="text/css" href="static/style.css">
</head>
<body>
	<header>
		<h1><a href="index.php">Since <?php echo date("F Y", $firstArticleTime); ?>, you've read <?php echo $articleCount; ?> articles.</a></h1>
		<div class="add">
			<form action="index.php" method="post">
				<input type="text" name="query" class="query" value="<?php if (isset($search)) echo $search; ?>" autofocus="autofocus">
				<input type="submit" name="search" class="submit">
			</form>
		</div>
	</header>
	<section>
		<ol<?php if ($r->hide_url) echo " class=\"hide_url\""; ?>>
<?php
foreach ($articles as $article) {
?>
			<li<?php if ($article["Starred"] == 1) echo " class=\"starred\"" ?>>
				<a href="<?php echo $article["URL"]; ?>" title="<?php echo $article["Title"]; ?>"<?php if ($r->open_links_in_new_window) echo " target=\"_blank\""; ?>>
					<div class="description">
						<h2><?php if (isset($search)) echo $r->highlight($article["Title"], $search); else echo $article["Title"]; ?></h2>
						<abbr title="<?php echo date("Y-m-d, H:i:s", $article["TimeAdded"]); ?>">read <?php echo $r->ago($article["TimeAdded"]); ?> ago</abbr>
						<h3><?php if (isset($search)) echo $r->highlight($article["URL"], $search); else echo $article["URL"]; ?></h3>
					</div>
				</a>
				<div class="actions">
					<form action="index.php" method="post">
						<input type="hidden" name="id" value="<?php echo $article["ID"]; ?>">
						<input type="submit" name="star" value="<?php echo ($article["Starred"] == 1) ? "&#9733;" : "&#9734;" ?>"><input type="submit" name="remove" value="&#10006;">
					</form>
				</div>
			</li>
<?php
}
if (!isset($search) && $articleCount > $offset + $r->display_limit) {
?>
		</ol>
		<ol>
			<li class="nextpage">
				<a href="?offset=<?php echo $offset + $r->display_limit; ?>">
					<h2>Show older...</h2>
				</a>
			</li>
<?php
}
if (empty($articles)) {
?>
			<li class="error">
				<h2>Nothing found<?php if (isset($search)) echo " for \"$search\""; ?>.</h2>
			</li>
<?php
}
?>
		</ol>
	</section>
	<footer>
<?php
if (!empty($return)) {
	echo "\t\t<p>$return.</p>";
}
?>
		<p><a href="https://github.com/doersino/ReAD">ReAD</a> is licensed under the <a href="README.md">WTFPL</a>, runtime <?php echo round(microtime(true) - $runtime, 5); ?> s</p>
	</footer>
</body>
</html>