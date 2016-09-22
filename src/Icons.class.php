<?php

require_once __DIR__ . "/../Config.class.php";

if (Config::ICON_FONT == "elusive") {

    // Unicode escape codes for Elusive Icons
    class Icons {
        const TAB_UNREAD          = "&#xf18e;";
        const TAB_ARCHIVED        = "&#xf1b3;";
        const TAB_STARRED         = "&#xf1fe;";
        const TAB_STATS           = "&#xf17a;";

        const TAB_NEWER           = "&#xf12f;";
        const TAB_OLDER           = "&#xf12e;";

        const ACTION_ARCHIVE      = "&#xf1b3;";
        const ACTION_STAR         = "&#xf1fd;";
        const ACTION_UNSTAR       = "&#xf1fe;";
        const ACTION_REMOVE       = "&#xf213;";

        const ACTION_SEARCH       = "&#xf1ed;";
        const ACTION_ADD_UNREAD   = "&#xf134;";
        const ACTION_ADD_ARCHIVED = "&#xf134;";
        const ACTION_ADD_STARRED  = "&#xf134;";
        const ACTION_CLEAR        = "&#xf1dc;";

        const ACTION_NEWER        = "&#xf16a;";
        const ACTION_OLDER        = "&#xf1e6;";
    }
} else if (Config::ICON_FONT == "emoji") {
    class Icons {

        // No escape codes: just plain emoji
        const TAB_UNREAD          = "📥";
        const TAB_ARCHIVED        = "📚";
        const TAB_STARRED         = "🌟";
        const TAB_STATS           = "📈";

        const TAB_NEWER           = "👉";
        const TAB_OLDER           = "👈";

        const ACTION_ARCHIVE      = "📚";
        const ACTION_STAR         = "🌟";
        const ACTION_UNSTAR       = "🌟";
        const ACTION_REMOVE       = "💣";

        const ACTION_SEARCH       = "🔍";
        const ACTION_ADD_UNREAD   = "📥";
        const ACTION_ADD_ARCHIVED = "📚";
        const ACTION_ADD_STARRED  = "🌟";
        const ACTION_CLEAR        = "🌀";

        const ACTION_NEWER        = "👉";
        const ACTION_OLDER        = "👈";
    }
} else if (Config::ICON_FONT == "octicons") {

    // Unicode escape codes for Octicons
    class Icons {
        const TAB_UNREAD          = "&#xf0cf;";
        const TAB_ARCHIVED        = "&#xf076;";
        const TAB_STARRED         = "&#xf07b;";
        const TAB_STATS           = "&#xf043;";

        const TAB_NEWER           = "&#xf078;";
        const TAB_OLDER           = "&#xf0a4;";

        const ACTION_ARCHIVE      = "&#xf03a;";
        const ACTION_STAR         = "&#xf02a;";
        const ACTION_UNSTAR       = "&#xf02a;";
        const ACTION_REMOVE       = "&#xf081;";

        const ACTION_SEARCH       = "&#xf02e;";
        const ACTION_ADD_UNREAD   = "&#xf05d;";
        const ACTION_ADD_ARCHIVED = "&#xf05d;";
        const ACTION_ADD_STARRED  = "&#xf05d;";
        const ACTION_CLEAR        = "&#xf084;";

        const ACTION_NEWER        = "&#xf03e;";
        const ACTION_OLDER        = "&#xf040;";
    }
}
