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

require_once(RL_LIB_DIR . '/extras/db/SQLProvider.class.php');

abstract class SQLiteProvider extends SQLProvider {

    protected
        $affectedCount = 0,
        $connection    = null,
        $recordCount   = 0,
        $recordIndex   = -1,
        $recordSet     = null,
        $transactions  = 0
        $wrapChar      = '`';

    /**
     * Create a new SQLiteModel instance.
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
    public function affected () {

        return $this->affectedCount;

    }

    /**
     * Retrieve all the records in the record set.
     *
     * @return array The array of records, or null if no SELECT statement has been executed or if
     *               next() has already been called.
     */
    public function all () {

        if ($this->recordSet == null || $this->recordIndex > -1) {

            return null;

        }

        $records = array();

        while ($record = sqlite_fetch_array($this->recordSet)) {

            $records[] = $record;

        }

        return $records;

    }

    /**
     * Start a transaction.
     *
     * @return void
     */
    public function begin () {

        $this->exec('BEGIN');
        $this->transactions++;

    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public function commit () {

        if ($this->transactions) {

            $this->exec('COMMIT');
            $this->transactions--;

        }

    }

    /**
     * Retrieve the count of records in the record set.
     *
     * @return int The count of records, or 0 if no SELECT statement has been executed.
     */
    public function count () {

        return $this->recordCount;

    }

    /**
     * Escape a string.
     *
     * @param string The string.
     *
     * @return string The escaped string.
     */
    public function escapeString ($string) {

        return sqlite_escape_string($string);

    }

    /**
     * Execute a complex non-SELECT statement.
     *
     * @param string The non-SELECT statement.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public function exec ($sql) {

        if ($this->connection === null) {

            $this->connection = $this->connect();

        }

        $this->sql = $sql;
        $result    = sqlite_exec($this->connection, $sql, $this->sqlerror);

        if ($result) {

            $this->affectedCount = sqlite_changes($this->connection);
            $this->sqlerror      = null;

            return true;

        }

        return false;

    }

    /**
     * Free the record set.
     *
     * Note: This does nothing in SQLite.
     *
     * @return void
     */
    public function free () {

    }

    /**
     * Retrieve the primary key id of the last record inserted.
     *
     * @return mixed The primary key id if the sequence has been used during the current connection,
     *               otherwise null.
     */
    public function insertId () {

        return sqlite_last_insert_rowid();

    }

    /**
     * Retrieve the next record in the SELECT record set.
     *
     * @return bool true, if the next record was retrieved, otherwise false.
     */
    public function next () {

        if ($this->recordCount == 0 || ++$this->recordIndex >= $this->recordCount) {

            return false;

        }

        $this->values = sqlite_fetch_array($this->recordSet);

        return true;

    }

    /**
     * Execute a complex SELECT statement.
     *
     * @param string The SELECT statement.
     *
     * @return bool true, if the statement completed successfully, otherwise false.
     */
    public function query ($sql) {

        if ($this->connection === null) {

            $this->connection = $this->connect();

        }

        $this->sql         = $sql;
        $this->recordIndex = -1;
        $this->recordSet   = sqlite_query($this->connection, $sql, SQLITE_ASSOC, $this->sqlerror);

        if ($this->recordSet) {

            $this->recordCount = sqlite_num_rows($this->recordSet);
            $this->sqlerror    = null;

            return true;

        }

        $this->recordCount = 0;

        return false;

    }

    /**
     * Rollback a transaction.
     *
     * @return void
     */
    public function rollback () {

        if ($this->transactions) {

            $this->exec('ROLLBACK');
            $this->transactions--;

        }

    }

}
