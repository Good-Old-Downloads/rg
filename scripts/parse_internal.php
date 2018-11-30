<?php
/*
  Copyright (C) 2018  GoodOldDownloads

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
require '../config.php';
require $CONFIG['BASEDIR'].'/vendor/autoload.php';
require $CONFIG['BASEDIR'].'/db.php';
require $CONFIG['BASEDIR'].'/class.retro.php';

$Retro = new GoodOldDownloads\Retro();
function addRom($name, $console, $dirInfo){
    global $dbh;
    global $Retro;
    if ($Retro->insertNoIntro($name, $console, $dirInfo) === false) {
        $err = $dbh->prepare("INSERT INTO `error_log` (`name`, `console`) VALUES (:name, :console)");
        $err->bindParam(':name', $name, \PDO::PARAM_STR);
        $err->bindParam(':console', $console, \PDO::PARAM_STR);
        $err->execute();
        return false;
    } else {
        return true;
    }
}

$handle = fopen($argv[1], "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        addRom($line, $argv[2], $argv[3]);
    }
    fclose($handle);
} else {
    echo "Nope";
    die;
} 