<?php


$server_name = 
    isset($_SERVER['SERVER_NAME']) 
    ? $_SERVER['SERVER_NAME'] 
    : 'default';

$robots_file = getcwd() .'/robots/'. $server_name .'/robots.txt';

$robots_file = 
    file_exists($robots_file) 
    ? $robots_file 
    : getcwd() .'/robots/default/robots.txt';

header("Content-Type: text/plain");
header("Content-Length: ". filesize($robots_file));
readfile($robots_file);
exit;
