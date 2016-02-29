<?php

header("Content-type: text/css; charset: UTF-8");

// element                desktop            mobile
$background        = array("white",           "#080808");
$text              = array("black",           "#eee");
$accent            = array("#888",            "#888");
$button            = array("#888",            "#777");
$buttonAccentHover = array("black",           "white");
$navBackground     = array("#eee",            "#222");
$navBorder         = array("#ccc",            "#333");
$navHighlight      = array("#ddd",            "#444");
$rowsHover         = array("rgba(0,0,0,.07)", "rgba(255,255,255,.1)");
$mark              = array("yellow",          "yellow");

for ($i = 0; $i < 2; ++$i) {
	if ($i == 1) {
		echo "@media (max-width: 720px) {";
	}

?>

body {
	background-color: <?php echo $background[$i]; ?>;
}

body,
nav a,
header .query,
main td a.title {
	color: <?php echo $text[$i]; ?>;
}

main td,
main td a.title span {
	color: <?php echo $accent[$i]; ?>;
}

header a.clearbutton,
header a.submitbutton,
main .actions input {
	color: <?php echo $button[$i]; ?>;
}

header a.clearbutton:hover,
header a.submitbutton:hover,
main td a:hover,
main .actions input:hover {
	color: <?php echo $buttonAccentHover[$i]; ?>;
}

header {
	background-color: <?php echo $navBackground[$i]; ?>;
}

nav a {
    border-right: 1px solid <?php echo $navBorder[$i]; ?>;
}
nav.pages a {
    border-left: 1px solid <?php echo $navBorder[$i]; ?>;
}

nav a:hover,
nav a.current,
header .query {
	background-color: <?php echo $navHighlight[$i]; ?>;
}

main tr:hover {
	background-color: <?php echo $rowsHover[$i]; ?>;
}

main td mark {
	background-color: <?php echo $mark[$i]; ?>;
	color: black !important;
}

<?php

	if ($i == 1) {
		echo "}";
	}
}

?>

/* GENERAL */
* {
	margin: 0;
	padding: 0;
	list-style-type: none;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}
html {
	font-size: 14px;
}
body {
	font-family: Helvetica, Arial, sans-serif;
	-webkit-font-smoothing: antialiased;
}
.sparkline {
	height: 20rem;
	position: fixed;
	z-index: -1;
	bottom: 0;
	width: 100%;
}
.sparkline canvas {
	height: 100% !important;
	width: 100% !important;
}
.icon {
	font-family: Elusive-Icons;
	vertical-align: baseline;
}

/* HEADER */
header {
	z-index: 10;
	position: fixed;
	top: 0;
	width: 100%;
	opacity: .95;
}
/* nav */
nav {
	display: inline-block;
	font-size: 0;
	float: left;
}
nav a {
	height: 6rem;
	padding: 2rem;
	font-size: 1.6rem;
	text-decoration: none;
	display: inline-block;
	vertical-align: top;
}
nav.pages {
	float: right;
}
nav.pages a {
	border-right: none;
}
/* search bar */
header form {
	width: 100%;
	display: inline-block;
}
header .query {
	height: 4rem;
	padding: 1rem 2rem;
	font-size: 1.6rem;
	font-family: inherit;
	outline: none;
	border: none;
	width: 100%;
}
header .submit {
	display: none;
}
header a.clearbutton,
header a.submitbutton {
	position: fixed;
	right: 0;
	padding: 1.12rem;
	font-size: 1.6rem;
	text-decoration: none;
}
header a.submitbutton {
	display: none;
}

/* MAIN */
main {
	display: block;
	margin: 10rem 0 .5rem;
	padding: .5rem 0;
	word-wrap: break-word;
}
main div.notice {
	font-size: 1.3rem;
	font-weight: bold;
	padding: .7rem 2rem 0;
}
main table {
	width: 100%;
	border-collapse: collapse;
	table-layout: fixed;
}
main tr {
	vertical-align: baseline;
}
main td {
	padding: .5rem 0;
}
main td:first-child {
	padding-left: 2rem;
}
main td.ago {
	min-width: 5rem;
	width: 5rem;
}
main td.ago abbr {
	text-decoration: none;
}
main td a.title {
	margin-right: .5rem;
	font-size: 1.3rem;
	font-weight: bold;
	text-decoration: none;
}
main td a.host {
	text-decoration: none;
	line-height: 1.3rem;
	color: inherit;
}
main td mark {
	color: inherit;
}
/* actions */
main .actions input {
	font-family: Elusive-Icons;
	font-size: 1rem;
	background-color: transparent;
	border: none;
	padding: .47rem .5rem;
}
main .actions input:hover {
	cursor: pointer;
}
main td.actions {
	min-width: 7rem;
	width: 7rem;
	padding-right: 2rem;
	text-align: right;
}
main td.actions form {
	margin-right: -.5rem;
}
main div.actions {
	display: none;
	float: right;
}
main div.actions form {
	margin-right: -.3rem;
}
main div.actions input {
	font-size: 1.3rem;
	padding: 0 .3rem;
}

/* MOBILE */
@media (max-width: 720px) {
	html {
		font-size: 12px;
	}
	nav a {
		padding-left: 1rem;
		padding-right: 1rem;
	}
	nav .read {
		display: none;
	}
	main td.title {
		padding-right: 2rem;
	}
	main td.actions {
		display: none;
	}
	main td a.host:before {
		content: '\A';
		display: block;
	}
	main div.actions {
		display: inline-block;
	}
}
