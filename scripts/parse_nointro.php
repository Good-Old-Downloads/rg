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
  Parses info from https://datomatic.no-intro.org/?page=redump and inserts ids into database
*/
require '../config.php';
require $CONFIG['BASEDIR'].'/vendor/autoload.php';
require $CONFIG['BASEDIR'].'/db.php';

$consoles = [
/*    [
        'slug' => 'n-gage',
        'id' => 
    ],*/
    [
        'slug' => 'adventure-vision',
        'id' => '5'
    ],
    [
        'slug' => 'amiga',
        'id' => '40'
    ],
    [
        'slug' => 'arcadia-2001',
        'id' => '4'
    ],
    [
        'slug' => 'atari-2600',
        'id' => '88'
    ],
    [
        'slug' => 'atari-5200',
        'id' => '1'
    ],
    [
        'slug' => 'atari-7800',
        'id' => '74'
    ],
    [
        'slug' => 'atari-st',
        'id' => '68'
    ],
    [
        'slug' => 'channel-f',
        'id' => '6'
    ],
    [
        'slug' => 'colecovision',
        'id' => '3'
    ],
    [
        'slug' => 'commodore-64',
        'id' => '42'
    ],
    [
        'slug' => 'commodore-64-pp',
        'id' => '43'
    ],
    [
        'slug' => 'commodore-64-tapes',
        'id' => '44'
    ],
    [
        'slug' => 'commodore-plus-4',
        'id' => '33'
    ],
    [
        'slug' => 'commodore-vic-20',
        'id' => '34'
    ],
    [
        'slug' => 'famicom',
        'id' => '31'
    ],
    [
        'slug' => 'game-com',
        'id' => '20'
    ],
    [
        'slug' => 'game-gear',
        'id' => '25'
    ],
    [
        'slug' => 'game-master',
        'id' => '8'
    ],
    [
        'slug' => 'gameboy',
        'id' => '46'
    ],
    [
        'slug' => 'gameboy-advance',
        'id' => '23'
    ],
    [
        'slug' => 'gameboy-color',
        'id' => '47'
    ],
    [
        'slug' => 'gp32',
        'id' => '58'
    ],
    [
        'slug' => 'intellivision',
        'id' => '105'
    ],
    [
        'slug' => 'jaguar',
        'id' => '2'
    ],
    [
        'slug' => 'loopy',
        'id' => '48'
    ],
    [
        'slug' => 'lynx',
        'id' => '30'
    ],
    [
        'slug' => 'msx',
        'id' => '10'
    ],
    [
        'slug' => 'msx2',
        'id' => '11'
    ],
    [
        'slug' => 'neo-geo-pocket',
        'id' => '35'
    ],
    [
        'slug' => 'neo-geo-pocket-color',
        'id' => '36'
    ],
    [
        'slug' => 'nes',
        'id' => '45'
    ],
    [
        'slug' => 'nintendo-64',
        'id' => '24'
    ],
    [
        'slug' => 'nintendo-ds',
        'id' => '28'
    ],
    [
        'slug' => 'nintendo-ds-download-play',
        'id' => '65'
    ],
    [
        'slug' => 'nintendo-dsi-dlc',
        'id' => '53'
    ],
    [
        'slug' => 'odyssey-2',
        'id' => '9'
    ],
    [
        'slug' => 'playstation-portable',
        'id' => '62'
    ],
    [
        'slug' => 'pokemon-mini',
        'id' => '14'
    ],
    [
        'slug' => 'pv-1000',
        'id' => '59'
    ],
    [
        'slug' => 'rca-studio-ii',
        'id' => '29'
    ],
    [
        'slug' => 'satellaview',
        'id' => '77'
    ],
    [
        'slug' => 'sega-32x',
        'id' => '17'
    ],
    [
        'slug' => 'sega-genesis',
        'id' => '32'
    ],
    [
        'slug' => 'sega-master-system',
        'id' => '26'
    ],
    [
        'slug' => 'sega-pico',
        'id' => '18'
    ],
    [
        'slug' => 'sega-sg-1000',
        'id' => '19'
    ],
    [
        'slug' => 'snes',
        'id' => '49'
    ],
    [
        'slug' => 'super-acan',
        'id' => '56'
    ],
    [
        'slug' => 'super-cassette-vision',
        'id' => '60'
    ],
    [
        'slug' => 'supergrafx',
        'id' => '13'
    ],
    [
        'slug' => 'turbografx-16',
        'id' => '12'
    ],
    [
        'slug' => 'vectrex',
        'id' => '7'
    ],
    [
        'slug' => 'videopac-plus',
        'id' => '16'
    ],
    [
        'slug' => 'virtual-boy',
        'id' => '15'
    ],
    [
        'slug' => 'vtech-creativision',
        'id' => '21'
    ],
    [
        'slug' => 'watara-supervision',
        'id' => '22'
    ],
    [
        'slug' => 'wonderswan',
        'id' => '50'
    ],
    [
        'slug' => 'wonderswan-color',
        'id' => '51'
    ],
    [
        'slug' => 'zx-spectrum-plus3',
        'id' => '73'
    ],
    [
        'slug' => 'picno',
        'id' => '101'
    ],
    [
        'slug' => 'v-smile',
        'id' => '76'
    ]
];

foreach ($consoles as $console) {
    $c = getNoIntro($console['id']);
    updateRoms($c, $console['slug'], $console['id']);
}

function updateRoms($consoleArr, $console, $noIntroId){
    global $dbh;
    $consoleKeys = array_keys($consoleArr);

    // Get
    $roms = $dbh->prepare("SELECT `id`, `name_original` FROM `roms` WHERE `console` = :console AND ((`redump_id` IS NULL) AND (`nointro_system_id` IS NULL) AND (`nointro_id` IS NULL))");
    $roms->bindParam(':console', $console, \PDO::PARAM_STR);
    $roms->execute();
    $roms = $roms->fetchAll(PDO::FETCH_ASSOC);

    // Set
    $updateRom = $dbh->prepare("UPDATE `roms` SET `nointro_system_id` = :sys_id, `nointro_id` = :noint_id WHERE `id` = :rom_id");
    $updateRom->bindParam(':rom_id', $id, \PDO::PARAM_INT);
    $updateRom->bindParam(':sys_id', $sys_id, \PDO::PARAM_STR);
    $updateRom->bindParam(':noint_id', $noint_id, \PDO::PARAM_STR);
    $fails = [];
    foreach ($roms as $rom) {
        if (array_key_exists($rom["name_original"], $consoleArr)) {
            $id = $rom['id'];
            $sys_id = $noIntroId;
            $noint_id = $consoleArr[$rom["name_original"]];
            $updateRom->execute();
        } elseif (strrpos($rom["name_original"], "-")) {
            $repalced = str_replace("-", "(-|~)", str_replace("(", "\\(", str_replace(")", "\\)", $rom["name_original"])));
            $matched = preg_grep("/".$repalced."/", $consoleKeys);
            if ($matched){
                $id = $rom['id'];
                $sys_id = $noIntroId;
                $noint_id = $consoleArr[reset($matched)];
                $updateRom->execute();
            }
        } else {
            $fails[] = $rom["name_original"];
        }
    }

    if (count($fails) > 0) {
        foreach ($fails as $key => $fail) {
            echo $fail."\n";
        }
        return false;
    }
    return true;
}

function getNoIntro($consoleId){
    $client = new \GuzzleHttp\Client();
    $res = $client->request('POST', 'https://datomatic.no-intro.org/index.php?page=redump&fun=download', ['form_params' => ['Download' => 'ho-ho-ho', 'datset_id' => $consoleId]]);
    $sheet = explode("\n", $res->getBody());
    foreach ($sheet as $row) {
        if ($row == null) {
            continue;
        }
        $split = str_getcsv($row, ';');
        // name => id
        $out[$split[1]] = $split[0];
    }
    return $out;
}