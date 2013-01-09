<?php

/**
 * This file is part of Redline, a lightweight web application engine for PHP.
 * Copyright (c) 2005-2009 Sean Kerr. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted
 * provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this list of conditions
 *   and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice, this list of
 *   conditions and the following disclaimer in the documentation and/or other materials provided
 *   with the distribution.
 * * Neither the name Redline nor the names of its contributors may be used to endorse or promote
 *   products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * Author: Sean Kerr <sean@code-box.org>
 */

abstract class SQLProvider extends RLProvider {

    public
        $sql      = null,
        $sqlerror = null;

    /**
     * Create a new SQLProvider instance.
     *
     * @param array The associative array of field/value pairs.
     */
    public function __construct ($values = null) {

        parent::__construct($values);

    }

    /**
     * Retrieve the count of affected records after a DELETE/INSERT/UPDATE statement.
     *
     * @return int The count of affected records.
     */
    public abstract function affected ();

    /**
     * Retrieve all the records in the record set.
     *
     * @return array The array of records, or null if no SELECT statement has been executed or if
     *               next() has already been called.
     */
    public abstract function all ();

    /**
     * Start a transaction.
     *
     * @return void
     */
    public abstract function begin ();

    /**
     * Bind parameters to a custom SQL statement.
     *
     * Note: This function accepts multiple pairs of field/value arguments where the first argument
     *       in the pair is the field and the second is the value.
     *
     * @param string The SQL statement.
     */
    public function bind ($sql) {

        $args   = func_get_args();
        $keys   = array();
        $values = array();

        if ((count($args) - 1) % 2 != 0) {

            throw new RLException('Bind fields and values must come in pairs');

        }

        for ($i = 1, $icount = count($args); $i < $icount; $i++) {

            $field = $args[$i];
            $value = $args[++$i];

            if ($value === null) {

                $keys[]   = "@{$field}";
                $keys[]   = "#{$field}";
                $values[] = 'NULL';
                $values[] = 'NULL';

            } else {

                $keys[]   = "@{$field}";
                $keys[]   = "#{$field}";
                $values[] = "'" . $this->escapeString($value) . "'";
                $values[] = $value;

            }

        }

        return str_replace($keys, $values, $sql);

    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public abstract function commit ();

    /**
     * Create a database connection.
     *
     * @return resource The database resource.
     */
    protected abstract function connect ();

    /**
     * Retrieve the count of records in the record set.
     *
     * @return int The count of records, or 0 if no SELECT statement has been executed.
     */
    public abstract function count ();

    /**
     * Execute a DELETE statement.
     *
     * @param string The database table.
     * @param array  The associative array of WHERE conditions.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public function delete ($table, $where = array()) {

        if ($where !== null) {

            return $this->exec("DELETE FROM {$this->wrapChar}{$table}{$this->wrapChar} " . $this->genWhere($where));

        }

        return $this->exec("DELETE FROM {$this->wrapChar}{$table}{$this->wrapChar}");

    }

    /**
     * Escape a string.
     *
     * @param string The string.
     *
     * @return string The escaped string.
     */
    public abstract function escapeString ($string);

    /**
     * Execute a complex non-SELECT statement.
     *
     * @param string The non-SELECT statement.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public abstract function exec ($sql);

    /**
     * Free the record set.
     *
     * @return void
     */
    public abstract function free ();

    /**
     * Generate a WHERE statement.
     *
     * @param array The associative array of conditions.
     *
     * @return string The WHERE statement.
     */
    public function genWhere ($conditions) {

        $where = array();

        foreach ($conditions as $field => $value) {

            $operator = $field{0};
            $type     = $field{1};
            $field    = substr($field, 2);

            if ($value === null) {

                $type  = null;
                $value = 'NULL';

                if ($operator == '=') {

                    $operator = 'IS';

                } else {

                    $operator = 'IS NOT';

                }

            } else if (is_array($value)) {

                $operator  = 'NOT IN';
                $type      = '#';

                foreach ($value as &$item) {

                    $item = "'" . $this->escapeString($item) . "'";

                }

                $value = '(' . implode(',', $value) . ')';

            } else if ($operator == '!') {

                $operator = '!=';

            }

            if ($type == '@') {

                $value = "'" . $this->escapeString($value) . "'";

            } else if ($type == '$') {

                $field = "UPPER({$field})";
                $value = "UPPER('" . $this->escapeString($value) . "')";

            } else if ($type == '%') {

                $field = "LOWER({$field})";
                $value = "LOWER('" . $this->escapeString($value) . "')";

            }

            $where[] = "{$field} {$operator} {$value}";

        }

        if (count($where) > 0) {

            return 'WHERE ' . implode(' AND ', $where);

        }

        return '';

    }

    /**
     * Execute an INSERT statement.
     *
     * @param string The database table.
     * @param array  The associative array of field/value pairs.
     * @param array  The associative array of field/value pairs that are not supposed to quoted or escaped.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public function insert ($table, $fieldvals, $rawvals = array()) {

        $keys   = array();
        $values = array();

        if ($fieldvals !== null) {

            $keys = array_keys($fieldvals);

            foreach (array_values($fieldvals) as $value) {

                if ($value === null) {

                    $values[] = 'NULL';

                } else {

                    $values[] = "'" . $this->escapeString($value) . "'";

                }

            }

        }

        if ($rawvals !== null) {

            foreach ($rawvals as $key => $value) {

                $keys[] = $key;

                if ($value === null) {

                    $values[] = 'NULL';

                } else {

                    $values[] = $value;

                }

            }

        }

        $keys   = $this->wrapChar . implode("{$this->wrapChar},{$this->wrapChar}", $keys) . $this->wrapChar;
        $values = implode(', ', $values);

        return $this->exec("INSERT INTO {$this->wrapChar}{$table}{$this->wrapChar} ({$keys}) VALUES ({$values})");

    }

    /**
     * Retrieve the next record in the SELECT record set.
     *
     * @return bool true, if the next record was retrieved, otherwise false.
     */
    public abstract function next ();

    /**
     * Execute a complex SELECT statement.
     *
     * @param string The SELECT statement.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public abstract function query ($sql);

    /**
     * Rollback a transaction.
     *
     * @return void
     */
    public abstract function rollback ();

    /**
     * Execute a SELECT statement.
     *
     * @param string The database table.
     * @param string The comma-delimited list of fields to select.
     * @param string The associative array of WHERE conditions.
     * @param string The comma-delimited list of GROUP BY fields.
     * @param string The comma-delimited list of ORDER BY fields.
     * @param string The LIMIT of records.
     * @param string The OFFSET of records.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public function select ($table, $fields = '*', $where = null, $groupby = null,
                            $orderby = null, $limit = null, $offset = null) {

        $sql = "SELECT {$fields} FROM {$this->wrapChar}{$table}{$this->wrapChar} ";

        if ($where !== null) {

            $sql .= $this->genWhere($where);

        }

        if ($groupby !== null) {

            $sql .= " GROUP BY {$groupby}";

        }

        if ($orderby !== null) {

            $sql .= " ORDER BY {$orderby}";

        }

        if ($limit !== null) {

            $sql .= " LIMIT {$limit}";

        }

        if ($offset !== null) {

            $sql .= " OFFSET {$offset}";

        }

        return $this->query($sql);

    }

    /**
     * Execute an UPDATE statement.
     *
     * @param string The database table.
     * @param array  The associative array of field/value pairs.
     * @param array  The associative array of field/value pairs that are not supposed to quoted or escaped.
     * @param array  The associative array of WHERE conditions.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public function update ($table, $fieldvals, $rawvals = array(), $where = array()) {

        $set = array();

        if ($fieldvals !== null) {

            foreach ($fieldvals as $key => $value) {

                if ($value === null) {

                    $set[] = "{$this->wrapChar}{$key}{$this->wrapChar} = NULL";

                } else {

                    $set[] = "{$this->wrapChar}{$key}{$this->wrapChar} = '" . $this->escapeString($value) . "'";

                }

            }

        }

        if ($rawvals !== null) {

            foreach ($rawvals as $key => $value) {

                if ($value === null) {

                    $set[] = "{$this->wrapChar}{$key}{$this->wrapChar} = NULL";

                } else {

                    $set[] = "{$this->wrapChar}{$key}{$this->wrapChar} = {$value}";

                }

            }

        }

        $set = implode(', ', $set);

        if ($where !== null) {

            return $this->exec("UPDATE {$this->wrapChar}{$table}{$this->wrapChar} SET {$set} " . $this->genWhere($where));

        }

        return $this->exec("UPDATE {$this->wrapChar}{$table}{$this->wrapChar} SET {$set}");

    }

}