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
$app->add(new RKA\Middleware\IpAddress(false));

// Remove slashes
$app->add(function ($request, $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && substr($path, -1) == '/') {
        $uri = $uri->withPath(substr($path, 0, -1))->withPort(null);
        if ($request->getMethod() == 'GET') {
            return $response->withRedirect((string)$uri, 301);
        } else {
            return $next($request->withUri($uri), $response);
        }
    }
    return $next($request, $response);
});

if (date('U') < 1541257190) {
    // Shitty password protection
    $app->add(function ($request, $response, $next) {
        if (preg_match('/^\/api\//', $request->getUri()->getPath())){
            $response = $next($request, $response);
            return $response;
        }
        if (preg_match('/^\/404/', $request->getUri()->getPath())){
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
        if ($request->getUri()->getQuery() === "cestlavie") {
            setcookie("brittlebones", "uw#-wq]v>8efKn", time()+(1 * 24 * 60 * 60));
            $response = $next($request, $response);
            return $response;
        }
        if ($_COOKIE["brittlebones"] === "uw#-wq]v>8efKn") {
            $response = $next($request, $response);
            return $response;
        }
        $view = $this->get('view');
        return $view->render($response, 'fake_password.twig');
    });
}

// Set headers for all requests
$app->add(function ($request, $response, $next) {
    $nonceJS = base64_encode(random_bytes(24));
    $nonceCSS = base64_encode(random_bytes(24));
    // A little more loose for 404 pages, should have seperate CORS for 404 and the rest of the site.
    $CORS = "default-src *; script-src 'self' 'unsafe-inline' https://www.youtube.com; style-src 'self' 'unsafe-inline'; font-src 'self'; frame-ancestors 'none'; base-uri 'self'; connect-src 'self'; img-src 'self'";

    // Add global variable to Twig
/*    $view = $this->get('view');
    $view->getEnvironment()->addGlobal('nonce', ['script' => $nonceJS, 'style' => $nonceCSS]);*/

    $response = $next($request, $response);
    return $response
            ->withHeader('Content-Security-Policy', $CORS)
            ->withHeader('X-Content-Security-Policy', $CORS)
            ->withHeader('X-WebKit-CSP', $CORS)
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('X-My-Body-Is-Ready', "https://www.youtube.com/watch?v=l4UmHKTX9H4");
});

// Need list of console always loaded in
$app->add(function ($request, $response, $next) {
    $Memcached = $this->get('memcached');
    $consoleList = $Memcached->get('rg_console_list');
    if ($consoleList === false) {
        $dbh = $this->get('dbh');
        $consoles = $dbh->prepare("SELECT * FROM `consoles` ORDER BY `name` ASC");
        $consoles->execute();
        $consoleList = $consoles->fetchAll(PDO::FETCH_ASSOC);
        $Memcached->set('rg_console_list', $consoleList, 900);
    }

    // Add global variable to Twig
    $view = $this->get('view');
    $view->getEnvironment()->addGlobal('consoles', $consoleList);

    $response = $next($request, $response);
    return $response;
});

// Set theme
$app->add(function ($request, $response, $next) {
    $CONFIG = $this->get('site_config');
    $allowedThemes = ['dark', 'light'];

    if (isset($_POST['settheme'])) {
        if (isset($_POST['settheme']) && in_array($_POST['settheme'], $allowedThemes)) {
            setcookie('theme', $_POST['settheme'], time()+60*60*24*365, '/', $request->getUri()->getHost(), ($CONFIG["DEV"] ? false : true), true);
            $_COOKIE['theme'] = $_POST['settheme']; // For current
        } else {
            unset($_COOKIE['theme']);
            setcookie('theme', '', -1, '/', $request->getUri()->getHost());
        }
    }

    // Add global variable to Twig
    $view = $this->get('view');
    if (isset($_COOKIE["theme"]) && in_array($_COOKIE["theme"], $allowedThemes)) {
        $view->getEnvironment()->addGlobal('theme', $_COOKIE["theme"]);
    } else {
        $view->getEnvironment()->addGlobal('theme', 'light');
    }

    $response = $next($request, $response);
    if (isset($_POST['settheme'])) {
        return $response->withRedirect((string)$request->getUri()->withPort(null), 302);
    }
    return $response;
});

// Set language
$app->add(function ($request, $response, $next) {
    $CONFIG = $this->get('site_config');
    $allowedLanguages = [
        'de_DE' => [
            'ISO-639-1' => 'de'
        ],
        'ru_RU' => [
            'ISO-639-1' => 'ru'
        ],
        'el_GR' => [
            'ISO-639-1' => 'el'
        ],
        'en_CA' => [
            'ISO-639-1' => 'en'
        ]
    ];

    if (isset($_POST['setlang'])) {
        if (isset($_POST['setlang']) && isset($allowedLanguages[$_POST['setlang']])) { // isset() is faster than in_array()
            setcookie('language', $_POST['setlang'], time()+60*60*24*365, '/', $request->getUri()->getHost(), ($CONFIG["DEV"] ? false : true), true);
            $_COOKIE["language"] = $_POST['setlang']; // For current
        } else {
            unset($_COOKIE["language"]);
            setcookie('language', '', -1, '/', $request->getUri()->getHost());
        }
    }

    $language = null;
    if (isset($_COOKIE["language"]) && isset($allowedLanguages[$_COOKIE["language"]])) {
        $language = $_COOKIE["language"];
        putenv("LC_ALL=$language");
        setlocale(LC_ALL, $language);
        bindtextdomain('messages', '../locale');
        bind_textdomain_codeset('messages', 'UTF-8');
        textdomain('messages');
    }

    // Add global variable to Twig
    $view = $this->get('view');
    if ($language !== null) {
        $view->getEnvironment()->addGlobal('language', $allowedLanguages[$language]);
    } else {
        $view->getEnvironment()->addGlobal('language', ['ISO-639-1' => 'en']);
    }

    $response = $next($request, $response);
    if (isset($_POST['setlang'])) {
        return $response->withRedirect((string)$request->getUri()->withPort(null), 302);
    }
    return $response;
});