<?php

$server_name = 
    isset($_SERVER['SERVER_NAME']) 
    ? $_SERVER['SERVER_NAME'] 
    : 'default';
$robots_file = getcwd() .'/google/'. $server_name .'/google5a62b8ad67f30deb.html';
if(!file_exists($robots_file)){
  header('HTTP/1.0 404 Not Found');
  include(getcwd() . '/errors/404.php');
  exit();
}
header("Content-Type: text/html");
header("Content-Length: ". filesize($robots_file));
readfile($robots_file);
exit();

