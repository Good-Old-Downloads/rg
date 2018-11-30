# RetroGames Installation

These instructions assume you have experience with installing, configuring, and securing web site software within Linux. Instructions have been tested with Ubuntu 16.04.4 LTS and Debian Stretch.

That said, it is possible to run RetroGames on a Windows server.
### Prerequisites

- PHP >= 7.2

  `apt-get install php`
- MariaDB

  `apt-get install mariadb-server`
- memcached

  `apt-get install memcached`
- Composer

  https://getcomposer.org/download/

### Installing
##### Getting the sauce:

```bash
git clone https://wherever.the/code/ends-up-on.git
```

##### Installing the PHP requirements:
`cd` into the directory where the code now lies then install the site dependencies via Composer:
```bash
cd gg
php composer.phar install
```
##### Configuring the site:
Make a copy of config_blank.php named config.php and edit it.
```bash
cp config_blank.php config.php
vi config.php
```
config.php explantion:
```php
<?php
$CONFIG = [
    "BASEDIR" => "/var/www/rg",
    "LOGIN_PATH" => 'login',
    "DEV" => true,

    "DB" => [
        "DBNAME" => "retro",
        "DBUSER" => "root",
        "DBPASS" => ""
    ],

    "USER" => [
        "NAME" => "supersecretloginname",
        "PASS" => "asdf",
        "KEY" => "123-456-789"
    ],
    "MEMCACHED" => [
        "SERVER" => "127.0.0.1",
        "PORT" => 11211
    ]
];
```

##### Importing the empty database:
Login to MySQL, create a database, then import db.sql.
```
MariaDB [(none)]> CREATE DATABASE `rg`;
MariaDB [rg]> USE `rg`;
MariaDB [rg]> SOURCE db.sql;
```

##### Configuring the Nginx:
The Nginx config is pretty standard. I'll only list the relevant config values.
```nginx
server {
        listen 443 ssl http2;
        root /var/www/rg/web; # <-- must point to /web directory
        index index.php
        autoindex on;
        location = /index.php {
                try_files $uri =404;
                fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
                fastcgi_index index.php;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                include fastcgi_params;
        }
        location / {
            try_files $uri /index.php$is_args$args;
        }
        location ~ \.php$ {
                # prevent exposure of any other .php files!!!
                return 404;
        }
        location ~ /\.ht {
                deny all;
        }
}
```

### Running tests
lol

### Coding style
hahaha