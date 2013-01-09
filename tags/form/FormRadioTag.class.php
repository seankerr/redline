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

class FormRadioTag extends RLValueTag {

    protected
        $options = array();

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        // parse options
        preg_match_all('#\[([^\]]+)\]#is', $this->getBody(), $matches);

        for ($i = 0, $icount = count($matches[1]); $i < $icount; $i++) {

            $this->options[] = $matches[1][$i];

        }

        // get id attribute
        // note: we have to remove it temporarily since each replaced radio has its own
        $id = $this->removeAttribute('id');

        // iterate over options and replace them in the body
        $attributes = $this->getHTMLAttributes();
        $content    = $this->drawGetValue($this->getAttribute('x:default')) .
                      $this->getBody();

        for ($i = 0, $icount = count($this->options); $i < $icount; $i++) {

            $htmlId    = "{$id}_{$i}";
            $htmlValue = htmlspecialchars($this->options[$i]);
            $value     = str_replace("'", "\\'", $this->options[$i]);

            $checked = "<?php\n" .
                       "if (\$__value === '{$value}'):\n" .
                       "    echo ' checked=\"checked\"';\n" .
                       "endif;\n" .
                       "?>";

            $radio   = "<input{$attributes} id=\"{$htmlId}\" value=\"{$htmlValue}\"{$checked}>";
            $content = str_replace("[{$this->options[$i]}]", $radio, $content);

        }

        // put the id attribute back
        $this->setAttribute('id', $id);

        return $content;

    }

    /**
     * Draw the validation code for this tag.
     *
     * @return string The validation code.
     */
    public function drawValidation () {

        $group       = $this->getSafeAttribute('x:group');
        $invalidErr  = $this->getSafeAttribute('x:invalid-err');
        $options     = var_export($this->options, 1);
        $required    = $this->getFormAttribute('x:required');
        $requiredErr = $this->getSafeAttribute('x:required-err');

        if ($required === null) {

            $required = $this->getAttribute('x:required', '1');

        }

        // INITIALIZER: get value
        $this->addInitializer("\$value = \$this->get(\$name);");

        // INITIALIZER: make sure value is a string
        $this->addInitializer("if (!is_string(\$value)) {\n" .
                              "    \$value = '';\n" .
                              "    \$this->set(\$name, \$value);\n" .
                              "}");

        // INITIALIZER: provide options
        $this->addInitializer("\$options = {$options};");

        if ($required === '1' && $group === null) {

            // RULE: value is required and no group is provided
            $this->addRule("if (\$value == '') {\n" .
                           "    \$this->errors[\$name] = '{$requiredErr}';\n" .
                           "}");

        } else if ($group !== null) {

            // RULE: value is not required but we do have a group
            $this->addRule("if (\$value == '') {\n" .
                           "    if (isset(\$this->groups['{$group}']) && \$this->groups['{$group}'] === true) {\n" .
                           "        \$this->errors[\$name] = '{$requiredErr}';\n" .
                           "    }\n" .
                           "}");

        }

        // RULE: make sure value is in option array
        $this->addRule("if (!in_array(\$value, \$options)) {\n" .
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

        $this->removeAttribute('value');
        $this->setAttribute('type', 'radio');

    }

}
