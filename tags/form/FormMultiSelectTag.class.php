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

require_once(RL_TAG_DIR . '/form/FormSelectTag.class.php');

class FormMultiSelectTag extends FormSelectTag {

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        $bind    = $this->getSafeAttribute('x:bind');
        $first   = '';
        $formId  = $this->getForm()->getAttribute('id');
        $name    = $this->getAttribute('name');
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

        // append array operator onto name (lil hackery)
        $this->removeAttribute('name');

        $attributeString = $this->getHTMLAttributes();

        $this->setAttribute('name', $name);

        return "<select{$attributeString} name=\"{$name}[]\" multiple=\"multiple\">\n" .
               "{$first}\n" .
               "<?php\n" .
               "\$__value = \$request->params->get('{$name}', array());\n" .
               "if (!is_array(\$__value)):\n" .
               "    \$__value = array();\n" .
               "endif;\n" .
               "{$options}\n" .
               "\$__title_key = '{$this->titleKey}';\n" .
               "\$__value_key = '{$this->valueKey}';\n" .
               "for (\$__i = 0, \$__icount = count(\$__options); \$__i < \$__icount; \$__i++):\n" .
               "    \$__option_title = \$__options[\$__i][\$__title_key];\n" .
               "    \$__option_value = \$__options[\$__i][\$__value_key];\n" .
               "    if (in_array(\$__option_value, \$__value)):\n" .
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

        $bind        = $this->getSafeAttribute('x:bind');
        $group       = $this->getSafeAttribute('x:group');
        $invalidErr  = $this->getSafeAttribute('x:invalid-err');
        $required    = $this->getFormAttribute('x:required');
        $requiredErr = $this->getSafeAttribute('x:required-err');

        if ($required === null) {

            $required = $this->getAttribute('x:required', '1');

        }

        if ($bind === null) {

            // INITIALIZER: using template options
            $this->addInitializer("\$options = " . var_export($this->options, 1) . ";");

        } else {

            // INITIALIZER: get dynamic template options
            $this->addInitializer("\$options = \$this->request->attribs->get('{$bind}', array());");

        }

        // INITIALIZER: make sure value is an array
        $this->addInitializer("if (!is_array(\$value)) {\n" .
                              "    \$value = array();\n" .
                              "    \$this->set(\$name, \$value);\n" .
                              "}");

        if ($required === '1' && $group === null) {

            // RULE: value is required and no group is provided
            $this->addRule("if (count(\$value) == 0) {\n" .
                           "    \$this->errors[\$name] = '{$requiredErr}';\n" .
                           "}");

        } else if ($group !== null) {

            // RULE: value is not required but we do have a group
            $this->addRule("if (count(\$value) == 0) {\n" .
                           "    if (isset(\$this->groups['{$group}']) && \$this->groups['{$group}'] === true) {\n" .
                           "        \$this->errors[\$name] = '{$requiredErr}';\n" .
                           "    }\n" .
                           "}");

        }

        // RULE: iterate values and compare them to all the options to make sure they're all valid
        $rule = "{\n" .
                "    foreach (\$value as \$_value) {\n" .
                "        foreach (\$options as \$option) {\n" .
                "            if (\$_value == \$option['{$this->valueKey}']) {\n" .
                "                continue 2;\n" .
                "            }\n" .
                "        }\n";

        if ($required == '1') {

            $rule .= "        \$this->errors[\$name] = '{$invalidErr}';\n" .
                     "        return;\n";

        }

        $rule .= "    }\n" .
                 "}";

        $this->addRule($rule);

        // entirely custom validation so we must provide the core drawing
        $validation = implode("\n", $this->initializers) . "\n" . implode(" else ", $this->rules);

        return '    ' . str_replace("\n", "\n" . str_repeat(' ', 4), $validation);

    }

}
