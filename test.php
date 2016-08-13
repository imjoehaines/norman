<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/TestModels/Test.php';

use imjoehaines\Norman\Norman;

$pdo = new PDO('sqlite::memory:', '', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY ASC, something);');
$pdo->exec('INSERT INTO test (something) VALUES ("abc");');

var_dump(
    (new Test($pdo))->find(1)
);

$test = (new Test($pdo))->something('bob');

$test->save();

$sth = $pdo->prepare('SELECT * FROM test;');
$sth->execute();

var_dump($sth->fetchAll());
