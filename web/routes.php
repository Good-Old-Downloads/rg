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
$app->get('/', function ($request, $response, $args) {
    $dbh = $this->get('dbh');
    // Notice
    $notice = $dbh->prepare("SELECT `value` FROM `site` WHERE `name` = 'notice'");
    $notice->execute();
    $notice = $notice->fetchColumn();
    return $this->view->render($response, 'index.twig', ['notice' => $notice, 'page_name' => 'index']);
})->setName('index');

$app->get('/'.$CONFIG['LOGIN_PATH'], function ($request, $response, $args) {
    return $this->view->render($response, 'login.twig');
});

$app->post('/'.$CONFIG['LOGIN_PATH'], function ($request, $response, $args) {
    $CONFIG = $this->get('site_config');
    if ($request->getParam('login')) {
        if (($CONFIG["USER"]["NAME"] === $request->getParam('username')) && ($CONFIG["USER"]["PASS"] === $request->getParam('password'))) {
            $_SESSION['user'] = $CONFIG["USER"]["PASS"];
            setcookie("was_user", "1", 2147483647, '/');
            return $response->withRedirect('/admin');
        }
    }
    return $this->view->render($response, 'login.twig');
});

$app->get('/admin', function ($request, $response, $args) {
    $dbh = $this->get('dbh');
    $CONFIG = $this->get('site_config');
    if ($_SESSION['user'] !== $CONFIG["USER"]["PASS"]) {
        $notFoundHandler = $this['notFoundHandler'];
        return $notFoundHandler($request, $response);
    }
    // Get directory_info
    $dirInfo = $dbh->prepare("SELECT * FROM `directory_info` ORDER BY `id` ASC");
    $dirInfo->execute();
    $dirInfo = $dirInfo->fetchAll(\PDO::FETCH_ASSOC);

    // ROM Errors
    $romErrors = $dbh->prepare("SELECT * FROM `error_log`");
    $romErrors->execute();
    $romErrors = $romErrors->fetchAll(\PDO::FETCH_ASSOC);

    // Notice
    $notice = $dbh->prepare("SELECT `value` FROM `site` WHERE `name` = 'notice'");
    $notice->execute();
    $notice = $notice->fetchColumn();

    return $this->view->render($response, 'admin.twig', [
        'notice' => $notice,
        'dir_info' => $dirInfo,
        'rom_errors' => $romErrors
    ]);
});

$app->post('/admin', function ($request, $response, $args) {
    $dbh = $this->get('dbh');
    $Retro = new GoodOldDownloads\Retro();
    $CONFIG = $this->get('site_config');
    if ($_SESSION['user'] !== $CONFIG["USER"]["PASS"]) {
        $notFoundHandler = $this['notFoundHandler'];
        return $notFoundHandler($request, $response);
    }

    if ($request->getParam('reparse') == true) {
        set_time_limit(300);
        // Get rom info
        $getRoms = $dbh->prepare("SELECT `id`, `name_original`, `console`, `dir_info_id` FROM `roms`");
        $getRoms->execute();
        $roms = $getRoms->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($roms as $rom) {
            $Retro->insertNoIntro($rom['name_original'], $rom['console'], $rom['dir_info_id'], true);
        }
    }

    if ($request->getParam('save_notice') == true) {
        // Get rom info
        $setNotice = $dbh->prepare("INSERT INTO `site` (`name`, `value`) VALUES ('notice', :notice) ON DUPLICATE KEY UPDATE `value` = :notice");
        $setNotice->bindParam(':notice', $request->getParam('notice_text'), \PDO::PARAM_STR);
        $setNotice->execute();
    }
    return $response->withRedirect('/admin');
});

$app->get('/google-drive-bypass-tutorial', function ($request, $response, $args) {
    return $this->view->render($response, 'drive_tutorial.twig');
});

$app->get('/faq', function ($request, $response, $args) {
    return $this->view->render($response, 'faq.twig');
});

$app->get('/packs', function ($request, $response, $args) {
    return $this->view->render($response, 'packs.twig');
});

$app->get('/queue[/{page}]', function ($request, $response, $args) {
    $dbh = $this->get('dbh');
    $ipAddress = $request->getAttribute('ip_address');

    $getUploading = $dbh->prepare("
        SELECT `nointro_id`, `nointro_system_id`, `redump_id`, `region`, `iso_3166_1_alpha_2`, R.`name` as `name`, `name_original`, R.`slug`, `version`, `additional`, `disc`, `devstatus`, `languages`, `console` as `console_slug`, C.`name` as `console_name`, C.`image` as `console_image`
        FROM `roms` R
        JOIN `consoles` C ON R.`console` = C.`slug`
        JOIN `regions` RE ON R.`region` = RE.`name`
        WHERE `uploading` = 1
    ");
    $getUploading->execute();
    $uploading = $getUploading->fetchAll(PDO::FETCH_ASSOC);

    $limit = 15;
    $pageCurrent = intval($args['page']);
    if ($pageCurrent < 1) {
        $pageCurrent = 1;
        $offset = 0;
    } else {
        $offset = ($pageCurrent - 1) * $limit;
    }

    $getVoted = $dbh->prepare("
        SELECT COUNT(`rom_id`) as `votes`, `nointro_id`, `nointro_system_id`, `redump_id`, `region`, `iso_3166_1_alpha_2`, R.`name` as `name`, `name_original`, R.`slug`, `version`, `additional`, `disc`, `devstatus`, `languages`, `console` as `console_slug`, C.`name` as `console_name`, C.`image` as `console_image`
        FROM `votes` V
        JOIN `roms` R ON V.`rom_id` = R.`id`
        JOIN `consoles` C ON R.`console` = C.`slug`
        JOIN `regions` RE ON R.`region` = RE.`name`
        WHERE `uploading` = 0
        GROUP BY V.`rom_id`
        ORDER BY `votes` DESC, V.`rom_id` DESC
        LIMIT $offset, $limit
    ");
    $getVoted->execute();
    $votes = $getVoted->fetchAll(\PDO::FETCH_ASSOC);

    $voteCount = $dbh->prepare("
        SELECT COUNT(`rom_id`) as `count`
        FROM `votes` V
        JOIN `roms` R ON V.`rom_id` = R.`id`
        WHERE `uploading` = 0
        GROUP BY `rom_id`
    ");
    $voteCount->execute();
    $countTotal = $voteCount->rowCount();

    return $this->view->render($response, 'queue.twig', ['uploading' => $uploading, 'votes' => $votes, 'pagination' => [
            'total' => $countTotal,
            'page' => $pageCurrent,
            'offset' => $offset,
            'limit' => $limit
        ]]);
});

$app->get('/search[/{console}[/{term}[/{region}[/{page}]]]]', function ($request, $response, $args) {
    $CONFIG = $this->get('site_config');
    $dbh = $this->get('dbh');

    // Turn params into proper search (JS disabled)
    if ($request->getParam('t') !== null) {
        $term = $request->getParam('t');
    } else {
        $term = $args['term'];
    }
    if ($request->getParam('c') !== null) {
        $consoleStr = $request->getParam('c');
    } else {
        $consoleStr = $args['console'];
    }
    if ($request->getParam('r') !== null) {
        $region = $request->getParam('r');
    } else {
        $region = $args['region'];
    }

    $allowedRegions = ['europe', 'asia', 'america'];
    if (!in_array($region, $allowedRegions)) {
        $region = 'any';
    }

    $consoleStr = trim($consoleStr);
    $term = trim($term);
    if ($term === 'all') {
        $term = '';
    }

    $limit = 15;

    $pageCurrent = intval($args['page']);

    // In case page is less than first page
    if ($pageCurrent < 1) {
        $pageCurrent = 1;
        $offset = 0;
    } else {
        $offset = ($pageCurrent - 1) * $limit;
    }

    if ($consoleStr !== 'all') {
        $console = $dbh->prepare("SELECT * FROM `consoles` WHERE `slug` = :slug");
        $console->bindParam(':slug', $consoleStr, \PDO::PARAM_STR);
        $console->execute();
        $console = $console->fetch(PDO::FETCH_ASSOC);
        if (!$console) {
            $notFoundHandler = $this['notFoundHandler'];
            return $notFoundHandler($request, $response);
        }
    }

    $searchQuery = "
        SELECT `nointro_id`, `nointro_system_id`, `redump_id`, `region`, `iso_3166_1_alpha_2`, R.`name` as `name`, `name_original`, R.`slug`, `version`, `additional`, `disc`, `devstatus`, `languages`, `console` as `console_slug`, C.`name` as `console_name`, C.`image` as `console_image`
    ";
    if ($term !== "") {
        $searchQuery .= ",
        CASE WHEN R.`name` = :term THEN 1 ELSE 0 END AS score, 
        MATCH (R.`name`,`additional`) AGAINST (:term) as score2";
    }
    $searchQuery .= " FROM `roms` R
        JOIN `consoles` C ON R.`console` = C.`slug`
        JOIN `regions` RE ON R.`region` = RE.`name`";
    if ($term !== "") {
        $searchQuery .= " WHERE MATCH (R.`name`,`additional`) AGAINST (:term) > 0";
    }
    if ($consoleStr !== 'all') {
        $searchQuery .= " AND `console` = :console";
    }
    if ($region !== null) {
        // wtf store this properly
        $europe = ["Unknown", "USA, Europe", "USA, Australia", "UK", "Sweden", "Spain", "Scandinavia", "Russia", "Portugal", "Poland", "Norway", "Netherlands", "Japan, Europe", "Italy", "Ireland", "Hungary", "Greece", "Germany", "France", "Finland", "Europe, Australia", "Europe", "Denmark", "Crotia", "Brazil, Korea", "Brasil", "Brazil,", "Austria", "Australia", "Asia, Europe", "Argentina", "France, Spain", "USA, Europe, Brazil", "Europe, Brazil", "Japan, Europe, Brazi", "USA, Europe, Korea", "Japan, USA, Korea", "Europe, Asia", "Brazil, Portugal", "Japan, Europe, Brazil", "Europe, Canada", "Czech", "Brazil, Spain", "Belgium", "Belgium, Netherlands", "Switzerland", "South Africa", "Austria, Switzerland"];
        $america = ["Unknown", "USA, Korea", "USA, Japan", "USA, Europe", "USA, Brazil", "USA, Australia", "USA, Asia", "USA", "Latin America", "Japan, USA", "Canada", "USA, Europe, Brazil", "Japan, USA, Brazil", "Japan, Brazil", "USA, Europe, Korea", "Europe, Korea", "USA, Asia, Korea", "Europe, Canada"];
        $asia = ["Unknown", "USA, Korea", "USA, Japan", "USA, Asia", "Taiwan", "Korea", "Japan, USA", "Japan, Korea", "Japan, Europe", "Japan, Asia", "Japan", "Hong Kong", "China", "Brazil, Korea", "Asia, Europe", "Asia", "India", "Japan, USA, Brazil", "Japan, Europe, Brazi", "Japan, Brazil", "USA, Europe, Korea", "Asia, Korea", "Japan, Korea, Asia", "Japan, USA, Korea", "Europe, Korea", "Europe, Asia", "USA, Asia, Korea", "Japan, Hong Kong", "Japan, Europe, Brazil"];
        $searchBy = ['europe' => $europe, 'america' => $america, 'asia' => $asia];
        if (in_array($region, $allowedRegions)) {
            $searchQuery .= ' AND ((`region` = "'.join('") OR (`region` = "', $searchBy[$region]).'"))';
        }
    }
    if ($term !== "") {
        $searchQuery .= " ORDER BY `score` DESC, score2 DESC";
    } else {
        $searchQuery .= " ORDER BY `name` ASC";
    }

    // Count first before adding LIMIT
    $countQuery = "SELECT COUNT(*) FROM `roms` R ";

    // If specific console and term
    if ($consoleStr !== 'all' && $term !== "") {
        $countQuery .= " WHERE MATCH (R.`name`,`additional`) AGAINST (:term) > 0 AND `console` = :console";
    } else if ($consoleStr !== 'all' && $term === "") {
        // If specific console and no term
        $countQuery .= " WHERE `console` = :console";
    } else if ($consoleStr === 'all' && $term !== "") {
        // If both are filled
        $countQuery .= " WHERE MATCH (R.`name`,`additional`) AGAINST (:term) > 0";
    }
    if (in_array($region, $allowedRegions)) {
        $countQuery .= ' AND ((`region` = "'.join('") OR (`region` = "', $searchBy[$region]).'"))';
    }

    $romCount = $dbh->prepare($countQuery);
    if ($consoleStr !== 'all') {
        $romCount->bindParam(':console', $consoleStr, \PDO::PARAM_STR);
    }
    if ($term !== "") {
        $romCount->bindParam(':term', $term, \PDO::PARAM_STR);
    }
    $romCount->execute();
    $countTotal = intval($romCount->fetchColumn());

    // Add limit and reuse query for actual search
    $searchQuery .= " LIMIT $offset, $limit";
    $roms = $dbh->prepare($searchQuery);
    if ($consoleStr !== 'all') {
        $roms->bindParam(':console', $consoleStr, \PDO::PARAM_STR);
    }
    if ($term !== "") {
        $roms->bindParam(':term', $term, \PDO::PARAM_STR);
    }
    $roms->execute();
    $roms = $roms->fetchAll(\PDO::FETCH_ASSOC);

    return $this->view->render($response, 'search.twig', [
        'console' => $console,
        'roms' => $roms,
        'term' => $term,
        'region' => $region,
        'pagination' => [
            'total' => $countTotal,
            'page' => $pageCurrent,
            'offset' => $offset,
            'limit' => $limit,
            'term' => $term,
            'console' => $consoleStr,
            'region' => $region
        ]
    ]);
});

$app->get('/{console}', function ($request, $response, $args) {
    $CONFIG = $this->get('site_config');
    $dbh = $this->get('dbh');

    // Get console info
    $console = $dbh->prepare("SELECT * FROM `consoles` WHERE `slug` = :slug");
    $console->bindParam(':slug', $args['console'], \PDO::PARAM_STR);
    $console->execute();
    $console = $console->fetch(\PDO::FETCH_ASSOC);
    if ($console) {
        // Get emulators
        $emulators = $dbh->prepare("SELECT * FROM `consoles_emulators` CE
                                  JOIN `emulators` E
                                      ON CE.`emulator_id` = E.`id`
                                  WHERE `console_id` = :console_id");
        $emulators->bindParam(':console_id', $console['id'], \PDO::PARAM_INT);
        $emulators->execute();
        $emulators = $emulators->fetchAll(\PDO::FETCH_ASSOC);
        return $this->view->render($response, 'console.twig', ['console' => $console, 'emulators' => $emulators, 'page_name' => 'console']);
    } else {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }
});

$app->get('/{console}/{slug}', function ($request, $response, $args) {
    $CONFIG = $this->get('site_config');
    $dbh = $this->get('dbh');
    $rom = $dbh->prepare("SELECT roms.*, `iso_3166_1_alpha_2` FROM `roms` JOIN `regions` ON roms.`region` = regions.`name` WHERE `slug` = :slug AND `console` = :slug_console");
    $rom->bindParam(':slug', $args['slug'], \PDO::PARAM_STR);
    $rom->bindParam(':slug_console', $args['console'], \PDO::PARAM_STR);
    $rom->execute();
    $rom = $rom->fetch(PDO::FETCH_ASSOC);
    if (!$rom) {
        $notFoundHandler = $this['notFoundHandler'];
        return $notFoundHandler($request, $response);
    }
    // Get console info
    $console = $dbh->prepare("SELECT * FROM `consoles` WHERE `slug` = :slug_console");
    $console->bindParam(':slug_console', $rom['console'], \PDO::PARAM_STR);
    $console->execute();
    $console = $console->fetch(PDO::FETCH_ASSOC);

    // Get links
    $links = $dbh->prepare("SELECT
                                IF(links.`file_name` IS NULL, IF(`link_safe` IS NULL, `link`, `link_safe`), links.`file_name`) as `name`,
                                IF(`link_safe` IS NULL, `link`, `link_safe`) as `link`,
                                `host`,
                                hosters.`name` as `host_name`,
                                `icon_html`
                            FROM `links`
                            LEFT JOIN `hosters`
                                ON links.`host` = hosters.`id`
                            WHERE links.rom_id = :rom_id
                            ORDER BY hosters.`order` ASC, `name` ASC
                        ");
    $links->bindParam(':rom_id', $rom['id'], \PDO::PARAM_INT);
    $links->execute();
    $linkresults = $links->fetchAll(PDO::FETCH_ASSOC);

    $linklist = [];
    foreach ($linkresults as $key => $link) {
        $newitem = [];
        $newitem['name'] = $link['name'];
        $newitem['link'] = $link['link'];
        $linklist[$link['host']]['slug'] = $link['host'];
        $linklist[$link['host']]['name'] = $link['host_name'];
        $linklist[$link['host']]['icon'] = $link['icon_html'];
        $linklist[$link['host']]['links'][] = $newitem;
    }
    $hasBoxart = false;
    if (file_exists($CONFIG["BASEDIR"].'/web/static/img/roms/'.$console['slug'].'/'.$rom['name_original'].'.png')) {
        $hasBoxart = true;
    }
    return $this->view->render($response, 'rom.twig', ['rom' => $rom, 'console' => $console, 'links' => $linklist, 'has_boxart' => $hasBoxart]);
});