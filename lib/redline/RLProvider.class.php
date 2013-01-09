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

class RLProvider {

    protected
        $values = null;

    /**
     * Create a new RLProvider instance.
     *
     * @param array The default array to interface with.
     */
    public function __construct ($values = array()) {

        $this->values = $values;

    }

    /**
     * Retrieve a value.
     *
     * @param string The key.
     * @param mixed  The default value to return if the key doesn't exist.
     *
     * @return mixed The value, if the key exists, otherwise the default value.
     */
    public function get ($key, $default = null) {

        if (isset($this->values[$key])) {

            return $this->values[$key];

        }

        return $default;

    }

    /**
     * Retrieve an array of all key/value pairs.
     *
     * @return array The associative array of key/value pairs.
     */
    public function getArray () {

        return $this->values;

    }

    /**
     * Retrieve an array of all key/value pairs.
     *
     * Note: This will return null for any empty values.
     *
     * @return array The associative array of key/value pairs.
     */
    public function getArrayNullEmpty () {

        $values = array();

        foreach ($this->values as $key => $value) {

            if ($value !== null && is_string($value) && strlen($value) == 0) {

                $value = null;

            }

            $values[$key] = $value;

        }

        return $values;

    }

    /**
     * Retrieve an array of all key/value pairs.
     *
     * Note: This will skip empty values.
     *
     * @return array The associative array of key/value pairs.
     */
    public function getArraySkipEmpty () {

        $values = array();

        foreach ($this->values as $key => $value) {

            if ($value !== null && (!is_string($value) || strlen($value) > 0)) {

                $values[$key] = $value;

            }

        }

        return $values;

    }

    /**
     * Retrieve an array of keys.
     *
     * @return array The indexed array of keys.
     */
    public function getKeys () {

        return array_keys($this->values);

    }

    /**
     * Retrieve an array of values.
     *
     * @return array The indexed array of values.
     */
    public function getValues () {

        return array_values($this->values);

    }

    /**
     * Indicates that a key exists.
     *
     * @param string The key.
     *
     * @return bool true, if the key exists, otherwise false.
     */
    public function hasKey ($key) {

        return isset($this->values[$key]);

    }

    /**
     * Indicates that a value exists.
     *
     * @param mixed The value.
     *
     * @return bool true, if the value exists, otherwise false.
     */
    public function hasValue ($value) {

        return array_search($value, $this->values) !== false;

    }

    /**
     * Merge multiple values.
     *
     * @param array The associative array of key/value pairs.
     *
     * @return void
     */
    public function merge ($values) {

        // we avoid array_merge() to sustain any possible reference to $this->values
        foreach ($values as $key => $value) {

            $this->values[$key] = $value;

        }

    }

    /**
     * Remove a value.
     *
     * @param string The key.
     *
     * @return void
     */
    public function remove ($key) {

        if (isset($this->values[$key])) {

            unset($this->values[$key]);

        }

    }

    /**
     * Set a value.
     *
     * @param string The key.
     * @param mixed  The value.
     *
     * @return void
     */
    public function set ($key, $value) {

        $this->values[$key] = &$value;

    }

}
