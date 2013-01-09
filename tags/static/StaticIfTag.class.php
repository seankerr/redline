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

class StaticIfTag extends RLStaticTag {

    /**
     * Check static details and indicate if this tag and children should be skipped.
     *
     * @return bool true, if the check is successful, otherwise false.
     */
    public function check () {

        $attr = $this->getAttribute('attr');

        if (($equal = $this->getAttribute('equal')) !== null) {

            $this->verifyAttributes('attr');

            return isset(self::$staticAttributes[$attr]) && self::$staticAttributes[$attr] == $equal;

        } else if (($notequal = $this->getAttribute('notequal')) !== null) {

            $this->verifyAttributes('attr');

            return !isset(self::$staticAttributes[$attr]) || self::$staticAttributes[$attr] != $notequal;

        } else if (($in = $this->getAttribute('in')) !== null) {

            $this->verifyAttributes('attr');

            if (!isset(self::$staticAttributes[$attr])) {

                return false;

            }

            $in = explode(',', $in);

            // trim items
            foreach ($in as &$item) {

                $item = trim($item);

            }

            return in_array(self::$staticAttributes[$attr], $in);

        } else if (($notin = $this->getAttribute('notin')) !== null) {

            $this->verifyAttributes('attr');

            if (!isset(self::$staticAttributes[$attr])) {

                return true;

            }

            $notin = explode(',', $notin);

            // trim items
            foreach ($notin as &$item) {

                $item = trim($item);

            }

            return !in_array(self::$staticAttributes[$attr], $notin);

        } else if (($isset = $this->getAttribute('isset')) !== null) {

            return isset(self::$staticAttributes[$isset]);

        } else if (($notset = $this->getAttribute('notset')) !== null) {

            return !isset(self::$staticAttributes[$notset]);

        }

        throw new RLException('Tag <?:?> in template ? line ? character ? is missing ' .
                              'a comparison attribute', $this->getNamespace(), $this->getName(),
                              $this->getFile(), $this->getLine(), $this->getChar());

    }

}