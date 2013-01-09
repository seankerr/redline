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

class FormTextTag extends RLValueTag {

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        if ($this->getAttribute('x:maxlen') !== null) {

            $this->setAttribute('maxlength', $this->getAttribute('x:maxlen'));

        }

        if ($this->getAttribute('x:readonly') === '1') {

            $this->setAttribute('readonly', 'readonly');

        }

        return $this->drawGetValue($this->getAttribute('x:default')) .
               "<input{$this->getHTMLAttributes()}>";

    }

    /**
     * Draw the validation code for this tag.
     *
     * @return string The validation code.
     */
    public function drawValidation () {

        $keep    = $this->getAttribute('x:keep');
        $minlen  = $this->getAttribute('x:minlen');
        $maxlen  = $this->getAttribute('x:maxlen');
        $minsize = $this->getAttribute('x:minsize');
        $maxsize = $this->getAttribute('x:maxsize');
        $pattern = $this->getSafeAttribute('x:pattern');

        if ($keep !== null) {

            // RULE: keep characters
            $chars = preg_quote($chars, '#');

            $this->addRule("if ((\$value = preg_replace('#[^{$chars}]#', '', \$value)) === null) {\n" .
                           "}");

        }

        if ($minlen !== null) {

            // RULE: minimum length
            $error = $this->getSafeAttribute('x:minlen-err', 'Minimum of % characters');
            $error = str_replace('%', $minlen, $error);

            $this->addRule("if (strlen(\$value) < {$minlen}) {\n" .
                           "    \$this->errors[\$name] = '{$error}';\n" .
                           "}");

        }

        if ($maxlen !== null) {

            // RULE: maximum length
            $error = $this->getSafeAttribute('x:maxlen-err', 'Maximum of % characters');
            $error = str_replace('%', $maxlen, $error);

            $this->addRule("if (strlen(\$value) > {$maxlen}) {\n" .
                           "    \$this->errors[\$name] = '{$error}';\n" .
                           "}");

        }

        if ($minsize !== null || $maxsize !== null) {

            // RULE: expecting a number
            $error = $this->getSafeAttribute('x:number-err', 'Invalid number');

            $this->addRule("if (!is_digit(\$value)) {\n" .
                           "    \$this->errors[\$name] = '{$error}';\n" .
                           "}");

        }

        if ($minsize !== null) {

            // RULE: minimum size (for a number)
            $error = $this->getSafeAttribute('x:minsize-err', 'Minimum size of %');
            $error = str_replace('%', $minsize, $error);

            $this->addRule("if (\$value < {$minsize}) {\n" .
                           "    \$this->errors[\$name] = '{$error}';\n" .
                           "}");

        }

        if ($maxsize !== null) {

            // RULE: maximum size (for a number)
            $error = $this->getSafeAttribute('x:maxsize-err', 'Maximum size of %');
            $error = str_replace('%', $maxsize, $error);

            $this->addRule("if (\$value < {$maxsize}) {\n" .
                           "    \$this->errors[\$name] = '{$error}';\n" .
                           "}");

        }

        if ($pattern !== null) {

            // RULE: match pattern
            $error = $this->getSafeAttribute('x:pattern-err', 'Invalid format');
            $error = str_replace('%', $pattern, $error);

            $this->addRule("if (!preg_match('{$pattern}', \$value)) {\n" .
                           "    \$this->errors[\$name] = '{$error}';\n" .
                           "}");

        }

        return parent::drawValidation();

    }

    /**
     * Initialization of the tag after attributes have been set.
     *
     * @return void
     */
    public function init () {

        parent::init();

        $this->setAttribute('type',  'text');
        $this->setAttribute('value', '<?php echo htmlspecialchars($__value); ?>');

    }

}
