<?php
require_once "config/config.php";
require_once "includes/db_connect.php";
require_once "classes/osoba.php";
require_once "classes/role.php";

class SessionInfo
{
    public static function isUserLogged()
    {
        return isset($_SESSION[SESSION_VAR_USER_IS_LOGGED]) && isset($_SESSION[SESSION_VAR_USER_NAME]);
    }

    public static function getLoggedOsoba()
    {
        if (!SessionInfo::isUserLogged()) {
            return null;
        }

        $query = "SELECT * FROM OSOBA
                  LEFT JOIN PROFIL ON OSOBA.LOGIN = PROFIL.LOGIN
                  WHERE OSOBA.LOGIN = '{$_SESSION[SESSION_VAR_USER_NAME]}'";

        $mysqli = DbConnect::connect();
        $result = $mysqli->query($query);

        if (!$result || $result->num_rows != 1) {
            return null;
        }

        $record = $result->fetch_assoc();

        return new Osoba($record["ID"], $record["MAIL"], $record["JMENO"], $record["PRIJMENI"], new Profil($record["LOGIN"], Role::from($record["ROLE"]), $record["HESLO"]));
    }
}
