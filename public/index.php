<?php
declare(strict_types=1);
$projectRoot=dirname(__DIR__);
if(PHP_SAPI==='cli-server' && !getenv('APP_BASE_URL'))putenv('APP_BASE_URL=/');
$base=rtrim(getenv('APP_BASE_URL')?:'/recantodasararas','/');
$requestPath=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
if(!is_string($requestPath)) {http_response_code(400);exit;}
$path=$requestPath;
if($base!=='' && ($path===$base || str_starts_with($path,$base.'/'))) $path=substr($path,strlen($base));
$path=$path?:'/';
// The built-in PHP server and Apache use the same URL map.
if(str_starts_with($path,'/assets/')) {
    $asset=realpath(__DIR__.$path);$assetRoot=realpath(__DIR__.'/assets');
    if($asset && $assetRoot && str_starts_with($asset,$assetRoot.DIRECTORY_SEPARATOR) && is_file($asset)) {
        $types=['css'=>'text/css; charset=utf-8','js'=>'application/javascript; charset=utf-8','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','svg'=>'image/svg+xml','webp'=>'image/webp','ico'=>'image/x-icon'];
        $extension=strtolower(pathinfo($asset,PATHINFO_EXTENSION));
        if(isset($types[$extension])) {header('Content-Type: '.$types[$extension]);header('X-Content-Type-Options: nosniff');readfile($asset);exit;}
    }
    http_response_code(404);exit;
}
$routes=require $projectRoot.'/routes/web.php';
if(!isset($routes[$path])) {http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Página não encontrada.';exit;}
// Keep authentication and response policies tied to the requested route.
$_SERVER['SCRIPT_NAME']=$base.($path==='/'?'/index.php':$path);
require $projectRoot.'/'.$routes[$path];
