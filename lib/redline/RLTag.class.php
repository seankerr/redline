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

class RLTag {

    protected
        $attributes = array(),
        $body       = '',
        $char       = null,
        $children   = array(),
        $file       = null,
        $line       = null,
        $name       = null,
        $namespace  = null,
        $parent     = null;

    /**
     * Create a new RLTag instance.
     *
     * @param RLTag  The parent tag.
     * @param string The tag namespace.
     * @param string The tag name.
     * @param string The file in which the tag occured.
     * @param int    The line at which the tag occured.
     * @param int    The character at which the tag occured.
     */
    public function __construct ($parent, $namespace, $name, $file, $line, $char) {

        $this->char      = $char;
        $this->file      = $file;
        $this->line      = $line;
        $this->name      = $name;
        $this->namespace = $namespace;
        $this->parent    = $parent;

    }

    /**
     * Add a child tag.
     *
     * @param RLTag The child tag.
     *
     * @return void
     */
    public function addChild (RLTag $tag) {

        $this->children[] = $tag;

    }

    /**
     * Append content to the body.
     *
     * @param string The content.
     *
     * @return void
     */
    public function appendBody ($content) {

        $this->body .= $content;

    }

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        return $this->body;

    }

    /**
     * Format code.
     *
     * @param string The code.
     * @param string How many spaces to indent?
     *
     * @return string The formatted code.
     */
    public function formatCode ($code, $indent) {

        return str_replace("\n", "\n" . str_repeat(' ', $indent), $code);

    }

    /**
     * Retrieve an attribute.
     *
     * @param string The attribute name.
     * @param string The default value.
     *
     * @return string The attribute value, if the attribute exists, otherwise the default value.
     */
    public function getAttribute ($name, $default = null) {

        if (isset($this->attributes[$name])) {

            return $this->attributes[$name];

        }

        return $default;

    }

    /**
     * Retrieve the body of this tag.
     *
     * @return string The body.
     */
    public function getBody () {

        return $this->body;

    }

    /**
     * Retrieve the character at which the tag occured.
     *
     * @return int The character.
     */
    public function getChar () {

        return $this->char;

    }

    /**
     * Retrieve child tags.
     *
     * @return array The indexed array of child tags.
     */
    public function getChildren () {

        return $this->children;

    }

    /**
     * Retrieve the file in which this tag occured.
     *
     * @return int The absolute filesystem path to the file.
     */
    public function getFile () {

        return $this->file;

    }

    /**
     * Retrieve all HTML attributes in string form.
     *
     * @return string The HTML attributes.
     */
    public function getHTMLAttributes () {

        $attributes = '';

        foreach ($this->attributes as $name => $value) {

            if (strpos($name, ':') === false) {

                $attributes .= " {$name}=\"{$value}\"";

            }

        }

        return $attributes;

    }

    /**
     * Retrieve the line at which the tag occured.
     *
     * @return int The line.
     */
    public function getLine () {

        return $this->line;

    }

    /**
     * Retrieve the tag name.
     *
     * @return int The name.
     */
    public function getName () {

        return $this->name;

    }

    /**
     * Retrieve the tag namespace.
     *
     * @return int The namespace.
     */
    public function getNamespace () {

        return $this->namespace;

    }

    /**
     * Retrieve the parent tag.
     *
     * @return RLTag The parent tag.
     */
    public function getParent () {

        return $this->parent;

    }

    /**
     * Retrieve an attribute with single quotes escaped.
     *
     * @param string The attribute name.
     * @param string The default value.
     *
     * @return string The attribute value, if the attribute exists, otherwise the default value.
     */
    public function getSafeAttribute ($name, $default = null) {

        $value = $this->getAttribute($name, $default);

        if ($value === null) {

            return $value;

        }

        return str_replace("'", "\\'", $value);

    }

    /**
     * Initialization of the tag after attributes have been set.
     *
     * @return void
     */
    public function init () {

    }

    /**
     * Remove an attribute.
     *
     * @param string The attribute name.
     *
     * @return mixed The attribute value, if the attribute existed, otherwise null.
     */
    public function removeAttribute ($name) {

        if (isset($this->attributes[$name])) {

            $value = $this->attributes[$name];

            unset($this->attributes[$name]);

            return $value;

        }

        return null;

    }

    /**
     * Set an attribute.
     *
     * @param string The attribute name.
     * @param string The attribute value.
     * @param bool   Indicates that the replacement procedure should be skipped.
     *
     * @return void
     */
    public function setAttribute ($name, $value, $replace = true) {

        if ($replace) {

            // replace constants, php variables and static template variables
            $value = RLUtil::replaceAll($value);

        }

        $this->attributes[$name] = $value;

    }

    /**
     * Set the body.
     *
     * @param string The content.
     *
     * @return void
     */
    public function setBody ($content) {

        $this->body = $content;

    }

    /**
     * Verify tag attributes.
     *
     * Note: This function accepts any number of replacement values for the error message.
     *
     * @param mixed Zero or more attribute names to check.
     *
     * @return void
     *
     * @throws RLException If a given attribute doesn't exist.
     */
    public function verifyAttributes () {

        $args = func_get_args();

        // iterate the arguments and make replacements
        for ($i = 0, $icount = count($args); $i < $icount; $i++) {

            if (!isset($this->attributes[$args[$i]])) {

                throw new RLException('Tag <?:?> in template ? line ? character ? is missing ' .
                                      '"?" attribute', $this->getNamespace(), $this->getName(),
                                      $this->getFile(), $this->getLine(), $this->getChar(),
                                      $args[$i]);

            }

        }

    }

}