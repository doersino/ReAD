<?php

require_once __DIR__ . "/../config.php";

// define colors for each theme
$themes = array(
    "light" => array(
        "background"        => "white",
        "text"              => "black",
        "accent"            => "#888",
        "button"            => "#888",
        "buttonAccentHover" => "black",
        "navBackground"     => "#eee",
        "navBorder"         => "#ccc",
        "navHighlight"      => "#ddd",
        "rowsHover"         => "rgba(0,0,0,.07)",
        "mark"              => "yellow"
    ),
    "dark" => array(
        "background"        => "#080808",
        "text"              => "#eee",
        "accent"            => "#888",
        "button"            => "#777",
        "buttonAccentHover" => "white",
        "navBackground"     => "#222",
        "navBorder"         => "#333",
        "navHighlight"      => "#444",
        "rowsHover"         => "rgba(255,255,255,.1)",
        "mark"              => "yellow"
    ),
    "book" => array(
        "background"        => "#f8f8ec",
        "text"              => "#330205",
        "accent"            => "#750e15",
        "button"            => "#9f252d",
        "buttonAccentHover" => "#330205",
        "navBackground"     => "#ca7f74",
        "navBorder"         => "#d59885",
        "navHighlight"      => "#ecbda3",
        "rowsHover"         => "rgba(0,0,0,.07)",
        "mark"              => "#f19337"
    ),
)

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
.articlespertimegraph {
    height: 20rem;
    position: fixed;
    z-index: -1;
    bottom: 0;
    width: 100%;
}
.icon {
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
header .clearbutton,
header .submitbutton,
header .newerbutton,
header .olderbutton {
    position: fixed;
    right: 0;
    padding: 1.12rem;
    font-size: 1.6rem;
    text-decoration: none;
}
header .submitbutton {
    display: none;
}
header .olderbutton {
    left: 0;
    right: auto;
}

/* stats */
nav.stats {
    width: 100%;
    display: inline-block;
    height: 4rem;
    font-size: 0;
}
nav.stats a {
    border: 0;
    background: transparent;
    height: 4rem;
}
nav.stats select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border-radius: 0;
    font-family: inherit;
    font-size: 1.6rem;
    border: 0;
    height: 4rem;
    padding: 0 2rem;
    width: 100%;
    text-align: center;
    text-align-last: center;
    outline: none;
    background-color: transparent;
}
nav.stats a + form select {
    padding: 0 4rem;
}
nav.stats select option {
    text-align: left;
}

/* view */
header hr {
    border: 0;
    height: 0.4rem;
    width: 100%;
}

/* MAIN */
main {
    display: block;
    margin: 10rem 0 .5rem;
    padding: .5rem 0;
    word-wrap: break-word;
}
main .words {
    font-size: 1.3rem;
    font-weight: bold;
    padding: .7rem 2rem 0;
}
main .words a {
    text-decoration: none;
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
main td:last-child {
    padding-right: 2rem;
}
main td.left {
    min-width: 5rem;
    width: 5rem;
}
main td.left abbr {
    text-decoration: none;
}
main td .text {
    margin-right: .5rem;
    font-size: 1.3rem;
    font-weight: bold;
    text-decoration: none;
}
main td .info {
    line-height: 1.3rem;
}
main td .info a {
    text-decoration: none;
    color: inherit;
}
main td .info .ertlabel {
    font-weight: 500;
    font-size: 0.75rem;
}
main td mark {
    color: inherit;
}

/* actions */
main .actions {
    min-width: 7rem;
    width: 7rem;
    text-align: right;
}
main .actions form {
    margin-right: -.5rem;
}
main .actions input {
    font-size: 1rem;
    background-color: transparent;
    border: none;
    padding: .47rem .5rem;
}
main .actions input:hover {
    cursor: pointer;
}

/* stats */
main .stats .words {
    padding-bottom: 0.7rem;
}
main .stats .herotext {
    font-size: 2rem;
    font-weight: normal;
    line-height: 2.5rem;
}
main .stats .herotext p {
    font-size: 1.6rem;
    line-height: 2rem;
    margin-top: 0.7rem;
}
main .stats .words ~ .words { /* every element with this class, except the first */
    margin-top: 0.7rem;
}
main .stats .graph {
    width: 100%;
    height: 20rem;
}
main .stats .graph.large {
    height: 35rem;
}

/* view */
main .viewheader {
    margin-top: -4.1rem;
    padding: 1.5rem 0 1rem;
    opacity: 0.95;
}
main .viewheader > *,
main .viewcontent {
    margin: auto;
    max-width: 45rem;
}
main .viewheader > * {
    padding: 0 2rem;
}
main .viewheader h1 {
    margin-bottom: 1rem;
    font-size: 2rem;
    font-weight: bold;
}
main .viewheader h1 a {
    text-decoration: none;
}
main .viewheader .meta {
    margin-bottom: 1rem;
}
main .viewcontent {
    padding: 1.5rem 2rem 0;
    font-family: "Linux Libertine", serif;
    font-size: 1.5rem;
    line-height: 2rem;
}
main .viewcontent pre {
    font-family: inherit;
    white-space: pre-wrap;
    white-space: -moz-pre-wrap;
    white-space: -o-pre-wrap;
    word-wrap: break-word;
}

/* MOBILE */
@media (max-width: 720px) {
    html {
        font-size: 12px;
    }
    nav a {
        padding-left: 0.8rem;
        padding-right: 0.8rem;
    }
    nav .read {
        display: none;
    }
    main td.actions {
        vertical-align: top;
    }
    main .actions input {
        font-size: 1.3rem;
        padding-left: .3rem;
        padding-right: .3rem;
    }
    main td .info:before {
        content: '\A';
        display: block;
    }
    main div.actions {
        display: inline-block;
    }
    main .stats .graph {
        height: 10rem;
    }
    main .stats .graph.large {
        height: 15rem;
    }
}

/* COLORS */
/* first for desktop, then for mobile */
<?php for ($i = 0; $i < 2; ++$i) { if ($i == 0) { $theme = Config::THEME_DESKTOP; } else if ($i == 1) { $theme = Config::THEME_MOBILE; echo "@media (max-width: 720px) {"; } ?>
    body {
        background-color: <?= $themes[$theme]["background"]; ?>;
    }
    body,
    nav a,
    header .query,
    header select,
    main td .text,
    main .viewheader h1 a {
        color: <?= $themes[$theme]["text"]; ?>;
    }
    main td,
    main td .text span.notitle,
    main .words a,
    main .stats .words:last-child,
    main .stats .herotext p,
    main .viewheader .meta {
        color: <?= $themes[$theme]["accent"]; ?>;
    }
    header .clearbutton,
    header .submitbutton,
    header .newerbutton,
    header .olderbutton,
    main .actions input {
        color: <?= $themes[$theme]["button"]; ?>;
    }
    header .clearbutton:hover,
    header .submitbutton:hover,
    header .newerbutton:hover,
    header .olderbutton:hover,
    main td a:hover,
    main .actions input:hover,
    main .words a:hover {
        color: <?= $themes[$theme]["buttonAccentHover"]; ?>;
    }
    header {
        background-color: <?= $themes[$theme]["navBackground"]; ?>;
    }
    nav a {
        border-right: 1px solid <?= $themes[$theme]["navBorder"]; ?>;
    }
    nav.pages a {
        border-left: 1px solid <?= $themes[$theme]["navBorder"]; ?>;
    }
    nav a:hover,
    nav a.current,
    header .query,
    header hr,
    nav.stats,
    main .viewheader {
        background-color: <?= $themes[$theme]["navHighlight"]; ?>;
    }
    main tr:hover {
        background-color: <?= $themes[$theme]["rowsHover"]; ?>;
    }
    main td mark {
        background-color: <?= $themes[$theme]["mark"]; ?>;
        color: black !important;
    }
<?php if ($i == 1) { echo "}"; } } ?>

/* ICONS */
/* and related adjustments */
<?php if (Config::ICON_FONT == "elusive") { ?>
    .icon,
    main .actions input {
        font-family: Elusive-Icons;
    }
<?php } else if (Config::ICON_FONT == "emoji") { ?>
    main td {
        padding-top: .42rem;
        padding-bottom: .42rem;
    }
    main .stats td {
        padding-top: .5rem;
        padding-bottom: .5rem;
    }
    main .actions input[name="star"] {
        opacity: 0.4;
    }
    header .clearbutton:hover,
    header .submitbutton:hover,
    header .newerbutton:hover,
    header .olderbutton:hover,
    main .actions input:hover {
        opacity: 0.67;
    }
    @media (max-width: 720px) {
        nav a {
            padding-left: 0.7rem;
            padding-right: 0.7rem;
        }
    }
<?php } else if (Config::ICON_FONT == "octicons") { ?>
    .icon,
    main .actions input {
        font-family: Octicons;
    }
    nav.pages a {
        width: 5rem;
    }
    @media (max-width: 720px) {
        nav.pages a {
            width: 3rem;
            padding-left: 1.07rem;
            padding-right: 1.07rem;
        }
        nav.pages a:last-child {
            padding-left: 0.8rem;
            padding-right: 0.8rem;
        }
    }
    nav.pages a.statsicon,
    nav.pages a.viewicon {
        width: auto;
    }
    nav.stats a {
        padding-top: 1.05rem;
    }
    @media (max-width: 720px) {
        nav.stats a {
            padding-top: 1.2rem;
        }
    }
    nav.stats a.olderbutton {
        padding-left: 1.3rem;
    }
    nav.stats a.newerbutton {
        padding-right: 0.8rem;
    }
    main .actions form {
        margin-right: -.7rem;
    }
    main .actions input[name="star"] {
        opacity: .4;
    }
    main .actions input[name="star"]:hover {
        opacity: 1;
    }
    @media (max-width: 720px) {
        main .actions input {
            font-size: 1.5rem;
        }
    }
<?php } ?>
