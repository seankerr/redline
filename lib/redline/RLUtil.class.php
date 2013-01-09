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

class RLUtil {

    /**
     * This is a handy function that replaces constants and variables inside a string.
     *
     * @param string The string.
     *
     * @return The modified string.
     */
    public static function replaceAll ($string) {

        $string = RLUtil::replaceEchoStatement($string);
        $string = RLUtil::replaceStaticVariables($string);
        $string = RLUtil::replacePHPVariables($string);
        $string = RLUtil::replaceConstants($string);

        return $string;

    }

    /**
     * Replace all PHP constants inside a string.
     *
     * Note: This replaces any {CONSTANT} with the constant value.
     *
     * @param string The string.
     *
     * @return string The modified string.
     */
    public static function replaceConstants ($string) {

        if (preg_match_all('#\{([^$}]+)\}#', $string, $matches)) {

            foreach ($matches[1] as $match) {

                if (defined($match)) {

                    $string = str_replace('{' . $match . '}', constant($match), $string);

                }

            }

        }

        return $string;

    }

    /**
     * Replace all echo statements inside a string.
     *
     * Note: This replaces any {{STATEMENT}} with an echo statement of that exact value.
     *
     * @param string The string.
     *
     * @return string The modified string.
     */
    public static function replaceEchoStatement ($string) {

        $string = preg_replace('#\{\{([^}]+)\}\}#', '<?php echo \1; ?>', $string);

        return $string;

    }

    /**
     * Replace all PHP variables inside a string.
     *
     * Note: This replaces any {$VARIABLE} with the variable value.
     *
     * @param string The string.
     *
     * @return string The modified string.
     */
    public static function replacePHPVariables ($string) {

        if (preg_match_all('#\{(\$[^}]+)\}#', $string, $matches)) {

            foreach ($matches[1] as $match) {

                eval("\$value = {$match};");

                $string = str_replace('{' . $match . '}', $value, $string);

            }

        }

        return $string;

    }

    /**
     * Replace all static template variables inside a string.
     *
     * Note: This replaces any {#VARIABLE} with the static variable value.
     *
     * @param string The string.
     *
     * @return string The modified string.
     */
    public static function replaceStaticVariables ($string) {

        if (preg_match_all('@\{#([^}]+)\}@', $string, $matches)) {

            foreach ($matches[1] as $match) {

                $string = str_replace('{#' . $match . '}', RLStaticTag::getStaticAttribute($match), $string);

            }

        }

        return $string;

    }

}