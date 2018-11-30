<?php
/*
    Clone git repos that match consoles in the DB then generate a sym.txt file so i can symlink them manually to the images dir
*/
require '../../config.php';
require $CONFIG['BASEDIR'].'/vendor/autoload.php';
require $CONFIG['BASEDIR'].'/db.php';

$consoles = $dbh->prepare("SELECT D.console as dir, R.console as slug FROM `directory_info` D
                           LEFT JOIN `roms` R ON D.id = R.dir_info_id
                           GROUP BY R.console");
$consoles->execute();
$consoles = $consoles->fetchAll(\PDO::FETCH_ASSOC);

$client = new \GuzzleHttp\Client(['headers' => ['Accept' => 'application/vnd.github.v3+json']]);
$res = $client->request('GET', 'https://api.github.com/users/libretro-thumbnails/repos?per_page=500');
$repoList = json_decode($res->getBody());

$gitList = [];
foreach ($repoList as $repo) {
    $gitList[str_replace("_", " ", $repo->name)] = $repo->clone_url;
}
//var_dump($gitList);
foreach ($consoles as $console) {
    if (isset($gitList[$console['dir']])) {
        $cloneUrl = $gitList[$console['dir']];
        $slug = $console['slug'];
        exec("git clone $cloneUrl");
        if (file_exists($CONFIG['BASEDIR']."scripts/images/".basename($cloneUrl, '.git')."/Named_Boxarts")) {
            file_put_contents('sym.txt', 'ln -s "'.$CONFIG['BASEDIR']."scripts/images/".basename($cloneUrl, '.git').'/Named_Boxarts" '.'"'.$CONFIG['BASEDIR']."web/static/img/roms/$slug".'"'."\r\n", FILE_APPEND);
            //file_put_contents('sym.txt', 'mklink /D "'.$CONFIG['BASEDIR']."web/static/img/roms/$slug".'" '.'"'.$CONFIG['BASEDIR']."scripts/images/".basename($cloneUrl, '.git').'/Named_Boxarts"'."\r\n", FILE_APPEND);
        }
    }
}