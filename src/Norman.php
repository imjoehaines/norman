<?php

declare(strict_types=1);

namespace imjoehaines\Norman;

use PDO;
use ReflectionClass;
use ReflectionProperty;

use function Stringy\create as s;

class Norman
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $columns = ['id'];

    /**
     * @var integer
     */
    protected $id;

    /**
     * @param PDO $db
     * @param array $properties
     */
    public function __construct(PDO $db, array $properties = [])
    {
        $this->db = $db;
        $this->table = $this->table ?: $this->getTableName();

        $validProperties = array_filter($properties, [$this, 'isColumnValid'], ARRAY_FILTER_USE_KEY);

        foreach ($validProperties as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param string $column
     * @return boolean
     */
    private function isColumnValid(string $column) : bool
    {
        return in_array($column, $this->columns, true);
    }

    /**
     * @return string
     */
    private function getTableName() : string
    {
        $classNamespace = explode('\\', get_class($this));
        $unqualifiedClass = array_pop($classNamespace);

        return (string) s($unqualifiedClass)->underscored();
    }

    /**
     * @param integer $id
     * @return Norman
     */
    public function find(int $id) : Norman
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE id = :id';

        $sth = $this->db->prepare($query);
        $sth->execute(['id' => $id]);

        return new static($this->db, $sth->fetch());
    }

    /**
     * @return array
     */
    private function getValues() : array
    {
        return array_reduce($this->columns, function (array $carry, string $column) {
            if (empty($this->{$column})) {
                return $carry;
            }

            return array_merge($carry, [$column => $this->{$column}]);
        }, []);
    }

    /**
     * @return boolean
     */
    public function save() : bool
    {
        $values = $this->getValues();

        if ($this->id) {
            return $this->update($values);
        }

        return $this->insert($values);
    }

    /**
     * @param array $values
     * @return boolean
     */
    private function insert(array $values) : bool
    {
        $columns = array_keys($values);

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (:%s);',
            $this->table,
            implode(', ', $columns),
            implode(', :', $columns)
        );

        $sth = $this->db->prepare($query);

        $sth->execute($values);

        $this->id = $this->db->lastInsertId();

        return true;
    }

    /**
     * @param array $values
     * @return boolean
     */
    private function update(array $values) : bool
    {
        $columns = array_keys($values);

        $sets = array_reduce($columns, function (array $carry, string $column) {
            return array_merge($carry, [$column . ' = :' . $column]);
        }, []);

        $query = sprintf(
            'UPDATE %s SET %s WHERE id = :id;',
            $this->table,
            implode(', ', $sets)
        );

        $sth = $this->db->prepare($query);

        return $sth->execute($values);
    }
}
