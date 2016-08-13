<?php

declare(strict_types=1);

namespace imjoehaines\Norman;

use PDO;
use ReflectionClass;
use ReflectionProperty;

class Norman
{
    /**
     * @var PDO
     */
    private $db;

    public $id;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db, array $properties = [])
    {
        $this->db = $db;

        foreach ($properties as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function find(int $id) : Norman
    {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE id = :id';

        $sth = $this->db->prepare($query);
        $sth->execute(['id' => $id]);

        return new static($this->db, $sth->fetch());
    }

    private function table() : string
    {
        $fqClassName = get_class($this);
        $unqualifiedClass = explode('\\', $fqClassName)[0];

        return strtolower($unqualifiedClass);
    }

    private function getValues() : array
    {
        $properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);

        return array_reduce($properties, function (array $carry, ReflectionProperty $property) {
            $property = $property->getName();

            if (empty($this->{$property})) {
                return $carry;
            }

            return array_merge($carry, [$property => $this->{$property}]);
        }, []);
    }

    public function save() : bool
    {
        $values = $this->getValues();

        if ($this->id) {
            return $this->update($values);
        }

        return $this->insert($values);
    }

    private function insert(array $values) : bool
    {
        $columns = array_keys($values);

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (:%s);',
            $this->table(),
            implode(', ', $columns),
            implode(', :', $columns)
        );

        $sth = $this->db->prepare($query);

        $sth->execute($values);

        $this->id = $this->db->lastInsertId();

        return true;
    }

    private function update(array $values) : bool
    {
        $columns = array_keys($values);

        $sets = array_reduce($columns, function (array $carry, string $column) {
            return array_merge($carry, [$column . ' = :' . $column]);
        }, []);

        $query = sprintf(
            'UPDATE %s SET %s WHERE id = :id;',
            $this->table(),
            implode(', ', $sets)
        );

        $sth = $this->db->prepare($query);

        return $sth->execute($values);
    }
}
