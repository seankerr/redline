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

class RLModel extends RLProvider {

    public
        $fields = null;

    protected
        $errors  = array(),
        $groups  = array(),
        $request = null;

    /**
     * Create a new RLModel instance.
     *
     * @param RLRequest The current request.
     * @param array     The associative array of key/value pairs.
     */
    public function __construct ($request, $values = null) {

        parent::__construct();

        $this->request = $request;

        if ($values !== null) {

            $this->merge($values);

        }

    }

    /**
     * Retrieve an array of all key/value pairs that have been validated as form fields.
     *
     * @return array The associative array of key/value pairs.
     */
    public function getArray () {

        $values = array();

        foreach ($this->fields as $field) {

            $values[$field] = $this->get($field);

        }

        return $values;

    }

    /**
     * Retrieve an array of all key/value pairs that have been validated.
     *
     * Note: This will return null for any empty values.
     *
     * @return array The associative array of key/value pairs.
     */
    public function getArrayNullEmpty () {

        $values = array();

        foreach ($this->fields as $field) {

            $value = $this->get($field);

            if ($value !== null && is_string($value) && strlen($value) == 0) {

                $value = null;

            }

            $values[$field] = $value;

        }

        return $values;

    }

    /**
     * Retrieve an array of all key/value pairs that have been validated.
     *
     * Note: This will skip empty values.
     *
     * @return array The associative array of key/value pairs.
     */
    public function getArraySkipEmpty () {

        $values = array();

        foreach ($this->fields as $field) {

            $value = $this->get('field');

            if ($value !== null && (!is_string($value) || strlen($value) > 0)) {

                $values[$field] = $value;

            }

        }

        return $values;

    }

    /**
     * Retrieve an error.
     *
     * @param string The error name.
     *
     * @return string The error message, if one exists, otherwise null.
     */
    public function getError ($name) {

        if (isset($this->errors[$name])) {

            return $this->errors[$name];

        }

        return null;

    }

    /**
     * Retrieve the count of errors.
     *
     * @return int The count of errors.
     */
    public function getErrorCount () {

        return count($this->errors);

    }

    /**
     * Retrieve all errors.
     *
     * @return array The associative array of errors.
     */
    public function getErrors () {

        return $this->errors;

    }

    /**
     * Remove a value.
     *
     * @param string The key.
     *
     * @return void
     */
    public function remove ($key) {

        parent::remove($key);

        if ($index = array_search($key, $this->fields)) {

            unset($this->fields[$index]);

        }

    }

    /**
     * Remove an error.
     *
     * @param string The error name.
     *
     * @return string The error message, if one existed, otherwise null.
     */
    public function removeError ($name) {

        if (isset($this->errors[$name])) {

            $error = $this->errors[$name];

            unset($this->errors[$name]);

            return $error;

        }

        return null;

    }

    /**
     * Set an error.
     *
     * @param string The error name.
     * @param string The error message.
     *
     * @return void
     */
    public function setError ($name, $error) {

        $this->errors[$name] = $error;

    }

    /**
     * Validate the model.
     *
     * @return bool true, if all validation methods completed successfully, otherwise false.
     */
    public function validate () {

        // iterate all parameters
        foreach ($this->fields as $field) {

            $method = "validate_{$field}";

            $this->$method();

        }

        $this->request->form = $this;

        return count($this->errors) == 0;

    }

}