<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Database\Entities;

/**
 * Class responsible for "intelligent" table and column name quoting.
 *
 * Attention, Quoter does not support string literals at this moment, use FragmentInterface.
 */
class Quoter
{
    /**
     * Used to detect functions and expression.
     *
     * @var array
     */
    private $stops = [")", "(", " "];

    /**
     * Cached list of table aliases used to correctly inject prefixed tables into conditions.
     *
     * @var array
     */
    private $aliases = [];

    /**
     * @var PDODriver
     */
    private $driver = null;

    /**
     * Database prefix.
     *
     * @var string
     */
    private $prefix = '';

    /**
     * @param PDODriver $driver Driver needed to correctly quote identifiers and string quotes.
     * @param string    $prefix
     */
    public function __construct(PDODriver $driver, $prefix)
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    /**
     * Query query identifier, if identified stated as table - table prefix must be added.
     *
     * @param string $identifier Identifier can include simple column operations and functions,
     *                           having "." in it will automatically force table prefix to first
     *                           value.
     * @param bool   $table      Set to true to let quote method know that identified is related
     *                           to table name.
     * @return mixed|string
     */
    public function quote($identifier, $table = false)
    {
        if (preg_match('/ AS /i', $identifier, $matches)) {
            list($identifier, $alias) = explode($matches[0], $identifier);

            return $this->aliasing($identifier, $alias, $table);
        }

        if ($this->hasExpressions($identifier)) {
            //Processing complex expression
            return $this->expression($identifier);
        }

        if (strpos($identifier, '.') === false) {
            //No table/column pair found
            return $this->unpaired($identifier, $table);
        }

        //Contain table.column statement
        return $this->paired($identifier);
    }

    /**
     * Quoting columns and tables in complex expression.
     *
     * @param string $identifier
     * @return mixed
     */
    protected function expression($identifier)
    {
        return preg_replace_callback('/([a-z][0-9_a-z\.]*\(?)/i', function ($match) {
            $identifier = $match[1];

            //Function name
            if ($this->hasExpressions($identifier)) {
                return $identifier;
            }

            return $this->quote($identifier);
        }, $identifier);
    }

    /**
     * Handle "IDENTIFIER AS ALIAS" expression.
     *
     * @param string $identifier
     * @param string $alias
     * @param bool   $table
     * @return string
     */
    protected function aliasing($identifier, $alias, $table)
    {
        $quoted = $this->quote($identifier, $table) . ' AS ' . $this->driver->identifier($alias);

        if ($table && strpos($identifier, '.') === false) {
            //We have to apply operation post factum to prevent self aliasing (name AS name)
            //when db has prefix, expected: prefix_name as name)
            $this->aliases[$alias] = $identifier;
        }

        return $quoted;
    }

    /**
     * Processing pair of table and column.
     *
     * @param string $identifier
     * @return string
     */
    protected function paired($identifier)
    {
        //We expecting only table and column, no database name can be included (due database isolation)
        list($table, $column) = explode('.', $identifier);

        return "{$this->quote($table, true)}.{$this->driver->identifier($column)}";
    }

    /**
     * Process unpaired (no . separator) identifier.
     *
     * @param string $identifier
     * @param bool   $table
     * @return string
     */
    protected function unpaired($identifier, $table)
    {
        if ($table && !isset($this->aliases[$identifier])) {
            if (!isset($this->aliases[$this->prefix . $identifier])) {
                //Generating our alias
                $this->aliases[$this->prefix . $identifier] = $identifier;
            }

            $identifier = $this->prefix . $identifier;
        }

        return $this->driver->identifier($identifier);
    }

    /**
     * Check if string has expression markers.
     *
     * @param string $string
     * @return bool
     */
    protected function hasExpressions($string)
    {
        foreach ($this->stops as $symbol) {
            if (strpos($string, $symbol) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset compiler aliases cache.
     *
     * @return $this
     */
    public function reset()
    {
        $this->aliases = [];

        return $this;
    }
}