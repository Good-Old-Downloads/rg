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
require $CONFIG['BASEDIR'].'/memcached.php';
require $CONFIG['BASEDIR'].'/db.php';
require $CONFIG['BASEDIR'].'/class.retro.php';
require $CONFIG['BASEDIR'].'/twig.ext.php';

session_start();

$configuration = [
    'settings' => [
        'displayErrorDetails' => $CONFIG["DEV"],
    ],
];
$container = new \Slim\Container($configuration);
$app = new \Slim\App($container);

require 'middleware.php';

require 'dependencies.php';

require 'routes_api.php';

require 'routes.php';

// Run, Forrest, Run!
$app->run();