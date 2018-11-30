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

// TODO: Move these properly

$container['visualCaptcha'] = function ($container) {
    global $CONFIG;
    $session = new \visualCaptcha\Session();
    require $CONFIG['BASEDIR'].'/captcha/images.php';
    return new \visualCaptcha\Captcha($session, "{$CONFIG['BASEDIR']}/captcha", $captchaImages);
};

$container['memcached'] = function ($container) {
    global $Memcached;
    return $Memcached;
};

$container['site_config'] = function ($container) {
    global $CONFIG;
    return $CONFIG; 
};

$container['dbh'] = function($container) {
    global $dbh;
    return $dbh;
};

// Register component on container
$container['view'] = function ($container) {
    $CONFIG = $container->get('site_config');
    $view = new \Slim\Views\Twig("{$CONFIG['BASEDIR']}/templates", [
        'cache' => ($CONFIG['DEV'] ? false : "{$CONFIG['BASEDIR']}/twig_cache")
    ]);
    $twig = $view->getEnvironment();

    // Add extensions
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($container->get('router'), $uri->withPort(null)));

    $twig->addExtension(new Twig_Extensions_Extension_I18n());
    $twig->addExtension(new AppExtension());
    $twig->addGlobal('config', $CONFIG);
    $twig->addGlobal('session', $_SESSION);

    $twig->addGlobal('nonce', ['script' => $nonceJS]);
    $twig->addGlobal('was_user', isset($_COOKIE['was_user']));

    return $view;
};

$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        $notFoundTemplates = ['/404/mgs3.twig', '/404/zelda2.twig', '/404/ss2.twig', '/404/tekken3.twig', '/404/zerowing.twig'];
        return $container->view->render($response->withStatus(404), $notFoundTemplates[array_rand($notFoundTemplates)]);
    };
};