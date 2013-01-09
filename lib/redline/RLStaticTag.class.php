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

require_once(RL_REDLINE_DIR . '/RLTag.class.php');

abstract class RLStaticTag extends RLTag {

    public static
        $staticAttributes = array();

    /**
     * Check static details and indicate if this tag and children should be skipped.
     *
     * @return bool true, if the check is successful, otherwise false.
     */
    public abstract function check ();

    /**
     * Retrieve a statically set attribute.
     *
     * @param string The attribute name.
     * @param string The default value.
     *
     * @return string The attribute value, if the attribute exists, otherwise the default value.
     */
    public static function getStaticAttribute ($name, $default = null) {

        if (isset(self::$staticAttributes[$name])) {

            return self::$staticAttributes[$name];

        }

        return $default;

    }

    /**
     * Set a static attribute.
     *
     * @param string The attribute name.
     * @param string The attribute value.
     *
     * @return void
     */
    public static function setStaticAttribute ($name, $value) {

        // replace constants, php variables and static template variables
        self::$staticAttributes[$name] = RLUtil::replaceAll($value);

    }

}