<?php
try {
    $dbh = new PDO(
        "mysql:host=localhost;dbname=".$CONFIG["DB"]["DBNAME"].";charset=utf8",
        $CONFIG["DB"]["DBUSER"],
        $CONFIG["DB"]["DBPASS"]);
} catch (PDOException $e) {
    echo "Shit's fuxked yo";
    die();
}