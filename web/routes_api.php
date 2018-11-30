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
    Private
*/
$checkApiKey = function($request, $response, $next) {
    $CONFIG = $this->get('site_config');
    if ($request->hasHeader('HTTP_X_API_KEY')) {
        $key = $request->getHeader("HTTP_X_API_KEY")[0];
        if ($CONFIG["USER"]["KEY"] === $key) {
            return $next($request, $response);
        }
        return $response->withJson(['SUCCESS' => false, 'MSG' => "Invalid API key"], 500);
    } else {
        return $response->withJson(['SUCCESS' => false, 'MSG' => "API key not set"], 400);
    }
};

$app->group('/api', function () {
    $this->post('/rom/preupload', function ($request, $response, $args) {
        $dbh = $this->get('dbh');
        $name = $request->getParam('name');
        $console = $request->getParam('console');

        if (!$name || !$console) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Insufficient params."]);
        }

        // Get rom info
        $getRomInfo = $dbh->prepare("SELECT `id` FROM `roms` WHERE `name_original` = :name_original AND `console` = :console");
        $getRomInfo->bindParam(':name_original', $name, \PDO::PARAM_STR);
        $getRomInfo->bindParam(':console', $console, \PDO::PARAM_STR);
        $getRomInfo->execute();
        $romId = $getRomInfo->fetchColumn(0);
        if ($romId === false) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Could not find ROM."]);
        }

        // Delete old links
        $getRomInfo = $dbh->prepare("DELETE FROM `links` WHERE `rom_id` = :rom_id");
        $getRomInfo->bindParam(':rom_id', $romId, \PDO::PARAM_INT);
        $getRomInfo->execute();

        // Set uploading = 1
        $setUploading = $dbh->prepare("UPDATE `roms` SET `uploading` = 1 WHERE `id` = :rom_id");
        $setUploading->bindParam(':rom_id', $romId, \PDO::PARAM_INT);
        $setUploading->execute();
        return $response->withJson(['SUCCESS' => true]);
    });
    $this->post('/rom/add', function ($request, $response, $args) {
        $dbh = $this->get('dbh');
        $Retro = new GoodOldDownloads\Retro();
        $name = $request->getParam('name');
        $console = $request->getParam('console');
        $dirInfo = $request->getParam('dirinfoid');
        if (!$name || !$console || !$dirInfo) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Insufficient params."]);
        }
        $insert = $Retro->insertNoIntro($name, $console, $dirInfo);
        if ($insert === false) {
            $err = $dbh->prepare("INSERT INTO `error_log` (`name`, `console`) VALUES (:name, :console)");
            $err->bindParam(':name', $name, \PDO::PARAM_STR);
            $err->bindParam(':console', $console, \PDO::PARAM_STR);
            $err->execute();
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Failed to insert ROM."]);
        } else {
            return $response->withJson(['SUCCESS' => true, 'MSG' => "$name inserted."]);
        }
        return $response->withJson(['SUCCESS' => false]);
    });
    $this->get('/rom/info', function ($request, $response, $args) {
        $dbh = $this->get('dbh');
        $Retro = new GoodOldDownloads\Retro();
        $name = $request->getParam('name');
        $console = $request->getParam('console');
        if (!$name || !$console) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Missing params."]);
        }

        $getRom = $dbh->prepare("SELECT `name_original` as `name`,
                                        R.`console`,
                                        R.`name_original`,
                                        D.`console` as `dir_console`,
                                        D.`region` as `dir_region`
                                 FROM `roms` R
                                 JOIN `directory_info` D ON `dir_info_id` = D.id
                                 WHERE `name_original` = :name AND R.`console` = :console");
        $getRom->bindParam(':name', $name, \PDO::PARAM_STR);
        $getRom->bindParam(':console', $console, \PDO::PARAM_STR);
        $getRom->execute();
        $rom = $getRom->fetch(\PDO::FETCH_ASSOC);

        if ($rom) {
            $rom['dir_full'] = $rom['dir_console'].'/'.$rom['name_original'].'/';
            if ($rom['dir_region']) {
                $rom['dir_full'] = $rom['dir_console'].'/'.$rom['dir_region'].'/'.$rom['name_original'].'/';
            }
            return $response->withJson(['SUCCESS' => true, 'DATA' => $rom]);
        } else {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "ROM doesn't exist."]);
        }
        return $response->withJson(['SUCCESS' => false]);
    });
    $this->post('/link/add', function ($request, $response, $args) {
        $dbh = $this->get('dbh');
        $Retro = new GoodOldDownloads\Retro();
        $name_original = $request->getParam('name');
        $console = $request->getParam('console');
        $link = $request->getParam('link');
        $filename = $request->getParam('filename');
        $host = $request->getParam('host');
        $link_safe = $request->getParam('link_safe');
        $size = $request->getParam('size');

        if (count(array_unique([$name, $console, $link, $filename, $host])) === 1) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Insufficient params. Requires: name, console, link, filename, host. Optional: link_safe, size"]);
        }

        $insert = $Retro->insertLink($name_original, $console, $link, $filename, $host, $link_safe, $size);
        if ($insert) {
            return $response->withJson(['SUCCESS' => true, 'MSG' => "$link inserted."]);
        } else {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Failed to add link."]);
        }
        return $response->withJson(['SUCCESS' => false]);
    });
    $this->post('/rom/postupload', function ($request, $response, $args) {
        $dbh = $this->get('dbh');
        $name = $request->getParam('name');
        $console = $request->getParam('console');

        if (!$name || !$console) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Insufficient params."]);
        }

        // Get rom info
        $getRomInfo = $dbh->prepare("SELECT `id` FROM `roms` WHERE `name_original` = :name_original AND `console` = :console");
        $getRomInfo->bindParam(':name_original', $name, \PDO::PARAM_STR);
        $getRomInfo->bindParam(':console', $console, \PDO::PARAM_STR);
        $getRomInfo->execute();
        $romId = $getRomInfo->fetchColumn(0);
        if ($romId === false) {
            return $response->withJson(['SUCCESS' => false, 'MSG' => "Could not find ROM."]);
        }

        // Clear votes
        $deleteVotes = $dbh->prepare("DELETE FROM `votes` WHERE `rom_id` = :rom_id");
        $deleteVotes->bindParam(':rom_id', $romId, \PDO::PARAM_INT);
        $deleteVotes->execute();

        // Set uploading = 0 and last_upload
        $setUploading = $dbh->prepare("UPDATE `roms` SET `uploading` = 0, `last_upload` = UNIX_TIMESTAMP() WHERE `id` = :rom_id");
        $setUploading->bindParam(':rom_id', $romId, \PDO::PARAM_INT);
        $setUploading->execute();
        return $response->withJson(['SUCCESS' => true]);
    });
    $this->post('/rom/reparse', function ($request, $response, $args) {
        /*
            Reparses all ROMS in database. (For when the parser gets updated.)
        */
        set_time_limit(300);
        $dbh = $this->get('dbh');
        $Retro = new GoodOldDownloads\Retro();

        // Get rom info
        $getRoms = $dbh->prepare("SELECT `id`, `name_original`, `console`, `dir_info_id` FROM `roms`");
        $getRoms->execute();
        $roms = $getRoms->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($roms as $rom) {
            $Retro->insertNoIntro($rom['name_original'], $rom['console'], $rom['dir_info_id'], true);
        }
        return $response->withJson(['SUCCESS' => true]);
    });
    $this->get('/queue', function ($request, $response, $args) {
        $dbh = $this->get('dbh');
        $game = [];
        $get = $dbh->prepare("
            SELECT COUNT(`rom_id`) as `votes`, `name_original`, R.`console`
            FROM `votes` V
            JOIN `roms` R ON V.`rom_id` = R.`id`
            WHERE `uploading` = 0
            GROUP BY V.`rom_id`
            ORDER BY `votes` DESC, V.`rom_id` DESC
            LIMIT 1
        ");
        $get->execute();
        if ($get->rowCount() > 0) {
            $game = $get->fetch(PDO::FETCH_ASSOC);
        }
        return $response->withJson($game);
    });
})->add($checkApiKey);

/*
    Public
*/
$app->group('/api', function () {
    $this->get('/public/nointro/parse', function ($request, $response, $args) {
        $Retro = new GoodOldDownloads\Retro();
        $term = $request->getParam('term');
        return $response->withJson($Retro->parseNoIntro($term));
    });
    /*
        VisualCaptcha
    */
    $this->group('/captcha', function () {
        $this->get('/begin/{howmany}', function ($request, $response, $args) {
            $dbh = $this->get('dbh');
            $captcha = $this->get('visualCaptcha');
            $captcha->generate($args['howmany']);
            return $response->withJson($captcha->getFrontEndData());
        });
        $this->get('/img/{index}', function ($request, $response, $args) {
            $dbh = $this->get('dbh');
            $captcha = $this->get('visualCaptcha');
            $headers = [];
            $image = $captcha->streamImage($headers, $args['index'], false);
            if (!$image) {
                throw new \Slim\Exception\NotFoundException($request, $response);
            } else {
                // Set headers
                foreach ($headers as $key => $val) {
                    $response = $response->withHeader($key, $val);
                }
                return $response;
            }
        });
        $this->post('/vote', function ($request, $response, $args) {
            $dbh = $this->get('dbh');
            $ipAddress = $request->getAttribute('ip_address');
            if ($request->getParam('rom_id') != null && is_numeric($request->getParam('rom_id'))) { // Check if valid vote data
                $session = new \visualCaptcha\Session();
                $captcha = $this->get('visualCaptcha');

                $id = $request->getParam('rom_id');

                // check captcha
                $frontendData = $captcha->getFrontendData();
                $captchaError = false;
                if (!$frontendData) {
                    $captchaError = _('Invalid Captcha Data');
                } else {
                    // If an image field name was submitted, try to validate it
                    if ($imageAnswer = $request->getParam($frontendData['imageFieldName'])){
                        // If incorrect
                        if (!$captcha->validateImage($imageAnswer)){
                            $captchaError = _('Incorrect Captcha Image. Please try again.');
                        }
                        // Generate new captcha or else the user can just rety the old one
                        $howMany = count($captcha->getImageOptions());
                        $captcha->generate($howMany);
                    } else {
                        $captchaError = _('Invalid Captcha Data');
                    }
                }

                if ($captchaError !== false) {
                    return $response->withJson(['SUCCESS' => false, 'MSG' => $captchaError]);
                }

                // check ip + amount of times voted
                $checkVoteCount = $dbh->prepare("SELECT COUNT(*) FROM `votes` WHERE `uid` = INET6_ATON(:ip)");
                $checkVoteCount->bindParam(':ip', $ipAddress, \PDO::PARAM_STR);
                $checkVoteCount->execute();
                if ($checkVoteCount->fetchColumn() >= 3) {
                    return $response->withJson(['SUCCESS' => false, 'MSG' => _("Your vote limit has exceeded. Wait for the ROMS you've voted on to finish uploading before voting again.")]);
                }

                // Check if rom has links
                $chcekLinksCount = $dbh->prepare("SELECT COUNT(*) FROM `links` WHERE `rom_id` = :rom_id");
                $chcekLinksCount->bindParam(':rom_id', $id, \PDO::PARAM_INT);
                $chcekLinksCount->execute();
                if ($chcekLinksCount->fetchColumn() > 0) { // If has links
                    // check if game is old enough
                    $chechUploadAge = $dbh->prepare("SELECT `last_upload` FROM `roms` WHERE `id` = :rom_id
                                                AND IF(DATE_ADD(FROM_UNIXTIME(`last_upload`), INTERVAL 60 DAY) >= NOW(), 0, 1)");
                    $chechUploadAge->bindParam(':rom_id', $id, \PDO::PARAM_INT);
                    $chechUploadAge->execute();
                    if ($chechUploadAge->rowCount() < 1) {
                        return $response->withJson(['SUCCESS' => false, 'MSG' => _('ROM not old enough to vote on.')]);
                    }
                }

                // check if uploading
                $checkUploading = $dbh->prepare("SELECT `uploading` FROM `roms` WHERE `id` = :rom_id");
                $checkUploading->bindParam(':rom_id', $id, \PDO::PARAM_INT);
                $checkUploading->execute();
                if (intval($checkUploading->fetchColumn()) === 1) {
                    return $response->withJson(['SUCCESS' => false, 'MSG' => _('ROM is already uploading!')]);
                }

                // Check if last upload older than 60 days
                // bleh do it after launch

                // vote
                $vote = $dbh->prepare("INSERT INTO `votes` (`uid`, `rom_id`) VALUES(INET6_ATON(:ip), :rom_id)");
                $vote->bindParam(':ip', $ipAddress, \PDO::PARAM_STR);
                $vote->bindParam(':rom_id', $id, \PDO::PARAM_INT);
                if ($vote->execute()) {
                    return $response->withJson(['SUCCESS' => true]);
                } else {
                    return $response->withJson(['SUCCESS' => false, 'MSG' => _("You already voted on this, check the queue to see when it'll get uploaded!")]);
                }
            }
            return $response->withJson(['SUCCESS' => false, 'MSG' => 'Invalid ROM.']);
        });
    });
});