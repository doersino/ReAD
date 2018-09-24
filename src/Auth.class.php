<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DB.class.php";

// NB: "session" is used here as "timespan between login and cookie expiry".

class Auth {
    const COOKIE = "sessionuid";

    private static function generateSessionUID() {

        // throw some entropy in there
        return md5(Config::PASSWORD . microtime() . $_SERVER["HTTP_USER_AGENT"]);
    }

    public static function passwordValid($pass) {
        return $pass == Config::PASSWORD;
    }

    public static function startSession() {
        $uid = Auth::generateSessionUID();
        $expires = time() + (86400 * Config::SESSION_LENGTH);
        $useragent = $_SERVER["HTTP_USER_AGENT"];  // helps discern sessions when going through them manually, if need be

        $query = DB::query("INSERT INTO `read_sessions` ( `uid`, `expires`, `useragent` ) VALUES (%s, %s, %s)", $uid, $expires, $useragent);
        setcookie(Auth::COOKIE, $uid, time() + (86400 * 30), "/");
    }

    public static function killSession() {
        if (isset($_COOKIE[Auth::COOKIE])) {
            $query = DB::query("DELETE FROM `read_sessions` WHERE `uid` = %s", $_COOKIE[Auth::COOKIE]);
        }
    }

    public static function sessionValid() {
        if (isset($_COOKIE[Auth::COOKIE])) {
            $expires = DB::queryFirstField("SELECT `expires` FROM `read_sessions` WHERE `uid` = %s", $_COOKIE[Auth::COOKIE]);
            return !empty($expires) && $expires >= time();
        }
        return false;
    }

    public static function vacuumExpiredSessions() {
        $query = DB::query("DELETE FROM `read_sessions` WHERE `expires` < %s", time());
    }

    //public static function vacuumAllSessions() {
    //    $query = DB::query("DELETE FROM `read_sessions`");
    //}
}
