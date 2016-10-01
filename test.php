<?php

namespace imjoehaines;

require __DIR__ . '/vendor/autoload.php';

use PDO;
use imjoehaines\Norman\Norman;

class Test extends Norman
{
    public $something;

    protected $columns = ['id', 'something'];
}

class AnotherTest extends Norman
{
    public $another;

    protected $columns = ['id', 'another'];
}

$pdo = new PDO('sqlite::memory:', '', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY ASC, something);');
$pdo->exec('INSERT INTO test (something) VALUES ("abc");');

$pdo->exec('CREATE TABLE another_test (id INTEGER PRIMARY KEY ASC, another);');
$pdo->exec('INSERT INTO another_test (another) VALUES ("aaa");');

assert(
    (new Test($pdo))->find(1)->something === 'abc'
);
assert(
    (new AnotherTest($pdo))->find(1)->another === 'aaa'
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
