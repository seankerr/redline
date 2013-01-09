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

class FormSelectTag extends RLValueTag {

    protected
        $options  = array(),
        $titleKey = null,
        $valueKey = null;

    /**
     * Add an option.
     *
     * @param string The title.
     * @param string The value.
     *
     * @return void
     */
    public function addOption ($title, $value) {

        $this->options[] = array($this->titleKey => $title, $this->valueKey => $value);

    }

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        $bind    = $this->getSafeAttribute('x:bind');
        $first   = '';
        $nofirst = $this->getAttribute('x:nofirst', '0');

        if ($bind === null) {

            // parse static options
            $this->parseOptions();

            $options = "\$__options = " . var_export($this->options, 1) . ";";

        } else {

            // dynamic source is provided
            $options = "\$__options = \$this->request->attribs->get('{$bind}', array());";

        }

        // check for a first option
        if ($this->getAttribute('x:first') !== null) {

            $first = $this->getSafeAttribute('x:first');
            $first = "<option value=\"\">{$first}</option>";

        } else if ($nofirst == '0' && $this->getAttribute('x:required') === '1') {

            $first = "<option value=\"\">" . RL_SELECT_CHOOSE . "</option>";

        } else if ($nofirst == '0' || $this->getAttribute('x:required') === '0') {

            $first = "<option value=\"\">" . RL_SELECT_ANY . "</option>";

        }

        $value = $this->drawGetValue($this->getAttribute('x:default'));

        return "<select{$this->getHTMLAttributes()}>\n" .
               "{$first}\n" .
               "{$value}\n" .
               "<?php\n" .
               "{$options}\n" .
               "\$__title_key = '{$this->titleKey}';\n" .
               "\$__value_key = '{$this->valueKey}';\n" .
               "for (\$__i = 0, \$__icount = count(\$__options); \$__i < \$__icount; \$__i++):\n" .
               "    \$__option_title = \$__options[\$__i][\$__title_key];\n" .
               "    \$__option_value = \$__options[\$__i][\$__value_key];\n" .
               "    if (\$__value == \$__option_value):\n" .
               "        \$__selected = ' selected=\"selected\"';\n" .
               "    else:\n" .
               "        \$__selected = '';\n" .
               "    endif;\n" .
               "    echo '<option value=\"' . htmlspecialchars(\$__option_value) . '\"' . \$__selected . '>' . \$__option_title. \"</option>\\n\";\n" .
               "endfor;\n" .
               "?>\n" .
               "</select>";

    }

    /**
     * Draw the validation code for this tag.
     *
     * @return string The validation code.
     */
    public function drawValidation () {

        $bind       = $this->getSafeAttribute('x:bind');
        $first      = $this->getAttribute('x:first');
        $invalidErr = $this->getSafeAttribute('x:invalid-err');
        $required   = $this->getFormAttribute('x:required');

        if ($required === null) {

            $required = $this->getAttribute('x:required', '1');

        }

        if ($bind === null) {

            // INITIALIZER: get options from template
            $this->addInitializer("\$options = " . var_export($this->options, 1) . ";");

        } else {

            // INITIALIZER: get dynamic options
            $this->addInitializer("\$options = \$this->request->attribs->get('{$bind}', array());");

        }

        // RULE: make sure value is in options array
        $this->addRule("{\n" .
                       "    foreach (\$options as \$option) {\n" .
                       "        if (\$value == \$option['{$this->valueKey}']) {\n" .
                       "            return;\n" .
                       "        }\n" .
                       "    }\n" .
                       "    \$this->errors[\$name] = '{$invalidErr}';\n" .
                       "}");

        // entirely custom validation so we must provide the core drawing
        $validation = implode("\n", $this->initializers) . "\n" . implode(" else ", $this->rules);

        return '    ' . str_replace("\n", "\n" . str_repeat(' ', 4), $validation);

    }

    /**
     * Initialization of the tag after attributes have been set.
     *
     * @return void
     */
    public function init () {

        parent::init();

        $this->titleKey = $this->getAttribute('x:titlekey', 'title');
        $this->valueKey = $this->getAttribute('x:valuekey', 'value');

    }

    /**
     * Parse options from the body.
     *
     * @return void
     */
    protected function parseOptions () {

        // parse title and value options
        preg_match_all('#\[([^,]+),([^\]]+)\]#s', $this->getBody(), $matches);

        for ($i = 0, $icount = count($matches[1]); $i < $icount; $i++) {

            $this->addOption(trim($matches[1][$i]), trim($matches[2][$i]));

        }

        // parse just value options
        preg_match_all('#\[([^,\]]+)\]#s', $this->getBody(), $matches);

        for ($i = 0, $icount = count($matches[1]); $i < $icount; $i++) {

            $this->addOption(trim($matches[1][$i]), trim($matches[1][$i]));

        }

    }

}
