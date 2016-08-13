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

assert(
    (new Test($pdo))->find(1)->something === 'abc'
);

$test = new Test($pdo);
$test->something = 'bob';

$test->save();

$sth = $pdo->prepare('SELECT * FROM test;');
$sth->execute();
$results = $sth->fetchAll();

assert(
    count($results) === 2
);
assert(
    $results[1]['something'] === 'bob'
);

$test->something = 'bobby';

$test->save();

$sth = $pdo->prepare('SELECT * FROM test;');
$sth->execute();

assert(
    $sth->fetchAll()[1]['something'] === 'bobby'
);
