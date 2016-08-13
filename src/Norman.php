<?php

namespace imjoehaines\Norman;

use PDO;
use BadMethodCallException;

class Norman
{
    /**
     * @var PDO
     */
    private $db;

    protected $id;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db, array $properties = [])
    {
        $this->db = $db;

        foreach ($properties as $key => $value) {
            $this->$key($value);
        }
    }

    public function __call($method, $arguments)
    {
        if (property_exists(get_class($this), $method)) {
            $this->$method = $arguments[0];

            return $this;
        }

        throw new BadMethodCallException(sprintf(
            '%s - Could not find a "%s::%s" method',
            __CLASS__,
            get_class($this),
            $method
        ));
    }

    public function find($id)
    {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE id = :id';

        $sth = $this->db->prepare($query);
        $sth->execute(['id' => $id]);

        return new static($this->db, $sth->fetch());
    }

    private function table()
    {
        $fqClassName = get_class($this);
        $unqualifiedClass = explode('\\', $fqClassName)[0];

        return strtolower($unqualifiedClass);
    }

    public function save()
    {
        $properties = get_object_vars($this);

        $values = array_filter($properties, function ($value, $property) {
            return !empty($value) && $property !== 'db';
        }, ARRAY_FILTER_USE_BOTH);

        $columns = array_keys($values);

        $query = 'INSERT INTO ' . $this->table() . ' (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ');';

        $sth = $this->db->prepare($query);

        return $sth->execute($values);
    }
}
