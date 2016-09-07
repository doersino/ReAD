<?php

require_once("Config.class.php");

if (Config::EMOJI_ICONS) {
    class Icons {
        const TAB_UNREAD     = "📥";
        const TAB_ARCHIVED   = "📚";
        const TAB_STARRED    = "👌";
        const TAB_STATS      = "📈";

        const TAB_NEWER      = "👉";
        const TAB_OLDER      = "👈";

        const ACTION_ARCHIVE = "📚";
        const ACTION_STAR    = "😍";
        const ACTION_UNSTAR  = "😒";
        const ACTION_REMOVE  = "🗑";

        const ACTION_SEARCH  = "🔍";
        const ACTION_ADD     = "📥";
        const ACTION_CLEAR   = "🌀";

        const ACTION_NEWER   = "👉";
        const ACTION_OLDER   = "👈";
    }
} else {

    // Unicode escape codes for Elusive Icons
    class Icons {
        const TAB_UNREAD     = "&#xf18e;";
        const TAB_ARCHIVED   = "&#xf1b3;";
        const TAB_STARRED    = "&#xf1fe;";
        const TAB_STATS      = "&#xf17a;";

        const TAB_NEWER      = "&#xf12f;";
        const TAB_OLDER      = "&#xf12e;";

        const ACTION_ARCHIVE = "&#xf1b3;";
        const ACTION_STAR    = "&#xf1fd;";
        const ACTION_UNSTAR  = "&#xf1fe;";
        const ACTION_REMOVE  = "&#xf213;";

        const ACTION_SEARCH  = "&#xf1ed;";
        const ACTION_ADD     = "&#xf134;";
        const ACTION_CLEAR   = "&#xf1dc;";

        const ACTION_NEWER   = "&#xf16a;";
        const ACTION_OLDER   = "&#xf1e6;";
    }
}
