<?php
    #set up database connection

    require_once('secret.php');

    $host = $env['database']['server'];
    $db   = $env['database']['database'];
    $user = $env['database']['user'];
    $pass = $env['database']['password'];
    $charset = 'utf8';
    
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    $pdo = new PDO($dsn, $user, $pass, $opt);
?>