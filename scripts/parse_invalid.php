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
/*
  Search via MD5
  Add to Database if redirected directly to rom page
*/
require '../config.php';
require $CONFIG['BASEDIR'].'/vendor/autoload.php';
require $CONFIG['BASEDIR'].'/db.php';
require $CONFIG['BASEDIR'].'/class.retro.php';
$Retro = new GoodOldDownloads\Retro();

$invalidInput = $argv[1];

$list = explode("\n", file_get_contents($invalidInput));
foreach ($list as $file) {
    if ($file == null) {
        continue;
    }
    $arr = explode('|', $file);
    if ($arr[1] === 'Parseable') {
        continue;
    }
    $data = [
        'name_parseable' => $arr[1],
        'name_original' => $arr[0],
        'console' => $arr[2],
        'dir_id' => intval($arr[3])
    ];
    $parseAdd = $Retro->insertNoIntro($data['name_parseable'], $data['console'], $data['dir_id'], true, [
        'original' => $data['name_original']
    ]);

    if(!$parseAdd){
        var_dump($data);
    } else {
        $rmLog = $dbh->prepare("DELETE FROM `error_log` WHERE `name` LIKE ? AND `console` = ?");
        $rmLog->execute([$data['name_original'].'%', $data['console']]);
    }
}

/*$client = new \GuzzleHttp\Client(['headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:63.0) Gecko/20100101 Firefox/63.0']]);
foreach ($out as $rom) {
    echo "${rom['name']}\n";                                               
    $res = $client->request('GET',
                            'http://redump.org/discs/quicksearch/'.$rom["md5"].'/',
                            ['allow_redirects' => false]
                        );
    if ($res->getStatusCode() === 302) { // If redirecting to rom page
        preg_match('/\/disc\/([0-9]+)\//', $res->getHeader('Location')[0], $matches);
        $redumpId = $matches[1];
        if(updateRoms($redumpId, $rom['name'], $consoleSlug)){
            echo "success\n";
        } else {
            echo "fail!\n";
        }
    } else {
        if ($res->getStatusCode() !== 200) {
            echo $res->getStatusCode();
            die;
        }
        echo "Not redirected!\n";
    }
}

function updateRoms($redumpId, $nameOrig, $console){
    global $dbh;
    // Set
    $updateRom = $dbh->prepare("UPDATE `roms` SET `redump_id` = :redump_id WHERE `name_original` = :name_orig AND `console` = :console");
    $updateRom->bindParam(':redump_id', $redumpId, \PDO::PARAM_INT);
    $updateRom->bindParam(':name_orig', $nameOrig, \PDO::PARAM_STR);
    $updateRom->bindParam(':console', $console, \PDO::PARAM_STR);
    return $updateRom->execute();
}*/