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
namespace GoodOldDownloads;

require __DIR__ . '/db.php';

class Retro
{
    public function __construct()
    {
        require __DIR__ . '/config.php';
        $this->CONFIG = $CONFIG;
    }

    public function parseNoIntro($name)
    {
        global $dbh;
        if (empty($name)) {
            return false;
        }
        $regions = $dbh->prepare("SELECT * FROM `regions`");
        $regions->execute();
        $regions = $regions->fetchAll(\PDO::FETCH_COLUMN, 0);
        preg_match("/^(?'title'[A-z0-9$!#&%'()+,\-.;=@[\]\^_{}~ ]+) \((?'region'".join("|" ,$regions).")\)(?: \((?'languages'[A-z]{2}(?:,[A-z]{2})*?)\))?(?: \(Disc (?'disc'[0-9]|[A-Z])\))?(?: \((?'devstatus'Sample|Proto|Beta[0-9]*?)\))?(?: \((?'additional'(?!v[0-9.,\-+ A-z]*|Rev [0-9A-Z.]+|Unl)[A-z0-9$!#&%'()+,\-.;=@[\]\^_{}~ ]+?)\))?(?: \((?'version'(?:v|V)[0-9.,\-+ A-z]*|Rev [0-9A-Z.]+)\))?(?: \((?'special'[A-z]{2})\))?(?: \((?'license'Unl)\))?(?: \((?'extratrash'Demo|Manual|iQue|Nintendo Channel.*?|(?:DS )?Download Station.*?|Satakore|Genteiban|Virtual Console|iDS|Wi-Fi Kiosk|Save Data|NDSi Enhanced|Kiosk.*?|(?:[0-9]+?[A-Z](?:, [0-9]+?[A-Z])*?))\))*(?: \[(?'status'b)\])?/",
        trim($name), $matches, PREG_OFFSET_CAPTURE);

        if (count($matches) === 0) {
            return false;
        }
        if (trim($name) !== $matches[0][0]) {
            // If whole string not parsed then consider it a fail
            return false;
        }

        $output = [
            'title' => null,
            'disc' => null,
            'region' => null,
            'languages' => null,
            'version' => null,
            'devstatus' => null,
            'additional' => null,
            'special' => null,
            'license' => null,
            'status' => null
        ];
        foreach ($matches as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            if (!isset($value[0]) || trim($value[0]) === "") {
                $output[$key] = null;
            } else {
                $output[$key] = $value[0];
            }
        }

        // Show original name in output
        $output['original'] = $matches[0][0];

        // Explode languages
        if (isset($output['languages'])) {
            $output['languages'] = explode(",", $output['languages']);
        }

        // return false if title or region not detected
        if (($output['title'] === null) || ($output['region'] === null)) {
            return false;
        }
        return $output;
    }

    public function insertNoIntro($name, $console, $dirInfo, $updateDupe = false, $custom = [])
    {
        global $dbh;
        if (empty($name) || empty($console)) {
            return false;
        }

        // Try to parse
        $parsed = $this->parseNoIntro($name);
        if ($parsed === false) {
            return false;
        }

        if (!empty($custom)) {
            $parsed = array_merge($parsed, $custom);
            if (isset($custom['original'])) {
                $name = $custom['original'];
            }
        }

        // Check if console even exists
        $checkConsole = $dbh->prepare("SELECT COUNT(*) FROM `consoles` WHERE `slug` = :slug");
        $checkConsole->bindParam(':slug', $console, \PDO::PARAM_STR);
        $checkConsole->execute();
        $consoleCheck = $checkConsole->fetchColumn(0);
        if ($consoleCheck != 1) {
            return false;
        }

        // Put languages back in comma seperated list
        $languages = $parsed['languages'];
        if ($languages != null) {
            $languages = join(",",$parsed['languages']);
        }

        // Build slug
        $slugify = new \Cocur\Slugify\Slugify();
        $discSlug = "";
        if ($parsed['disc'] !== null) {
            $discSlug = "disc {$parsed['disc']}";
        }
        $slug = $slugify->slugify(str_replace("'", "", $name));

        //Insert ROM!
        $sql = "
            INSERT INTO `roms` (`dir_info_id`,
                                `slug`,
                                `console`,
                                `disc`,
                                `name_original`,
                                `name`,
                                `region`,
                                `languages`,
                                `version`,
                                `devstatus`,
                                `additional`,
                                `special`,
                                `license`,
                                `status`,
                                `last_upload`
                            )
                            VALUES (
                                :dir_info_id,
                                :slug,
                                :console,
                                :disc,
                                :original,
                                :name,
                                :region,
                                :languages,
                                :version,
                                :devstatus,
                                :additional,
                                :special,
                                :license,
                                :status,
                                UNIX_TIMESTAMP()
                            )";
        if ($updateDupe) {
            $sql .= "ON DUPLICATE KEY UPDATE    
                        `dir_info_id` = :dir_info_id,
                        `slug` = :slug,
                        `console` = :console,
                        `disc` = :disc,
                        `name_original` = :original,
                        `name` = :name,
                        `region` = :region,
                        `languages` = :languages,
                        `version` = :version,
                        `devstatus` = :devstatus,
                        `additional` = :additional,
                        `special` = :special,
                        `license` = :license,
                        `status` = :status,
                        `last_upload` = UNIX_TIMESTAMP()";
        }
        $insertROM = $dbh->prepare($sql);
        $insertROM->bindParam(':dir_info_id', $dirInfo, \PDO::PARAM_INT);
        $insertROM->bindParam(':slug', $slug, \PDO::PARAM_STR);
        $insertROM->bindParam(':console', $console, \PDO::PARAM_STR);
        $insertROM->bindParam(':disc', $parsed['disc'], \PDO::PARAM_STR);
        $insertROM->bindParam(':original', $parsed['original'], \PDO::PARAM_STR);
        $insertROM->bindParam(':name', $parsed['title'], \PDO::PARAM_STR);
        $insertROM->bindParam(':region', $parsed['region'], \PDO::PARAM_STR);
        $insertROM->bindParam(':languages', $languages, \PDO::PARAM_STR);
        $insertROM->bindParam(':version', $parsed['version'], \PDO::PARAM_STR);
        $insertROM->bindParam(':devstatus', $parsed['devstatus'], \PDO::PARAM_STR);
        $insertROM->bindParam(':additional', $parsed['additional'], \PDO::PARAM_STR);
        $insertROM->bindParam(':special', $parsed['special'], \PDO::PARAM_STR);
        $insertROM->bindParam(':license', $parsed['license'], \PDO::PARAM_STR);
        $insertROM->bindParam(':status', $parsed['status'], \PDO::PARAM_STR);

        // Try to parse
        return $insertROM->execute();
    }

    public function insertLink($name_original, $console, $link, $filename, $host, $link_safe = null, $size = null)
    {
        global $dbh;

        // Check if ROM exists
        $checkRom = $dbh->prepare("SELECT `id` FROM `roms` WHERE `name_original` = :name_original AND `console` = :console");
        $checkRom->bindParam(':name_original', $name_original, \PDO::PARAM_STR);
        $checkRom->bindParam(':console', $console, \PDO::PARAM_STR);
        $checkRom->execute();
        $romId = $checkRom->fetchColumn(0);

        if (!$romId) {
            return false;
        }

        $romId = intval($romId);
        $addLink = $dbh->prepare("INSERT INTO `links` (`rom_id`, `link`, `link_safe`, `file_name`, `status`, `host`) VALUES (:rom_id, :link, :safelink, :filename, 'DONE', :host)");
        $addLink->bindParam(':rom_id', $romId, \PDO::PARAM_INT);
        $addLink->bindParam(':link', $link, \PDO::PARAM_STR);
        if ($linksafe !== null) {
            $addLink->bindParam(':safelink', $linksafe, \PDO::PARAM_STR);
        } else {
            $addLink->bindValue(':safelink', null, \PDO::PARAM_INT);
        }
        if ($filename !== null) {
            $addLink->bindParam(':filename', $filename, \PDO::PARAM_STR);
        } else {
            $addLink->bindValue(':filename', null, \PDO::PARAM_INT);
        }
        $addLink->bindParam(':host', $host, \PDO::PARAM_STR);

        return $addLink->execute();
    }
}