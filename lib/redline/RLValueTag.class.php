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

require_once(RL_TAG_DIR . '/form/FormFormTag.class.php');

abstract class RLValueTag extends RLTag {

    protected
        $formId       = null,
        $initializers = array(),
        $rules        = array();

    /**
     * Create a new RLValueTag instance.
     *
     * @param RLTag  The parent tag.
     * @param string The tag namespace.
     * @param string The tag name.
     * @param string The file in which the tag occured.
     * @param int    The line at which the tag occured.
     * @param int    The character at which the tag occured.
     */
    public function __construct ($parent, $namespace, $name, $file, $line, $char) {

        parent::__construct($parent, $namespace, $name, $file, $line, $char);

        // make sure this tag is used beneath a FormFormTag
        $form = $this->getForm();

        if ($form === null) {

            throw new RLException('Tag <?:?> in template ? line ? char ? must have a parent ' .
                                  '<form:form> tag', $this->getNamespace(), $this->getName(),
                                  $this->getFile(), $this->getLine(), $this->getChar());

        }

        $this->formId = $form->getAttribute('id');

        if ($this instanceof FormSelectTag || $this instanceof FormRadioTag) {

            $this->setAttribute('x:invalid-err',  RL_SELECT_INVALID);
            $this->setAttribute('x:required-err', RL_SELECT_REQUIRED);

        } else {

            $this->setAttribute('x:invalid-err',  RL_INPUT_INVALID);
            $this->setAttribute('x:required-err', RL_INPUT_REQUIRED);

        }

    }

    /**
     * Add an initializer.
     *
     * @param string The initializer code.
     *
     * @return void
     */
    public function addInitializer ($initializer) {

        $this->initializers[] = $initializer;

    }

    /**
     * Add a rule.
     *
     * @param string The rule code.
     *
     * @return void
     */
    public function addRule ($rule) {

        $this->rules[] = $rule;

    }

    /**
     * Retrieve the string that will be used in the cached template for retrieving the tag value.
     *
     * @param string The static default value.
     *
     * @return string The get value string.
     */
    public function drawGetValue ($default = '') {

        $name    = $this->getAttribute('name');
        $default = str_replace("'", "\\'", $default);
        $content = "<?php\n" .
                   "\$__value = \$__provider->get('{$name}');\n" .
                   "if (\$__value === null):\n" .
                   "    if (\$request->method == 'GET'):\n" .
                   "        \$__value = '{$default}';\n" .
                   "    else:\n" .
                   "        \$__value = '';\n" .
                   "    endif;\n" .
                   "elseif (!is_string(\$__value)):\n" .
                   "    \$__value = '';\n" .
                   "endif;\n" .
                   "?>";

        return $content;

    }

    /**
     * Draw the validation code for this tag.
     *
     * @return string The validation code.
     */
    public function drawValidation () {

        $validation = implode("\n", $this->initializers) . "\n" . implode(" else ", $this->rules);

        return '    ' . str_replace("\n", "\n" . str_repeat(' ', 4), $validation);

    }

    /**
     * Retrieve the parent form tag.
     *
     * @return RLTag The parent form tag, if one exists, otherwise null.
     */
    public function getForm () {

        $parent = $this->getParent();

        while ($parent !== null && !($parent instanceof FormFormTag)) {

            $parent = $parent->getParent();

        }

        return $parent;

    }

    /**
     * Retrieve a parent form tag attribute.
     *
     * @param string The attribute name.
     * @param string The default value.
     *
     * @return string The attribute value, if the attribute exists, otherwise the default value.
     */
    public function getFormAttribute ($name, $default = '') {

        return $this->getForm()->getAttribute($name, $default);

    }

    /**
     * Initialization of the tag after attributes have been set.
     *
     * @return void
     */
    public function init () {

        parent::init();

        $this->verifyAttributes('id');

        // register this id with the form
        $this->getForm()->addId($this->getAttribute('id'));

        // copy id to name?
        if ($this->getAttribute('name', null) === null) {

            $this->setAttribute('name', $this->getAttribute('id'));

        }

        if ($this instanceof FormFileTag) {

            // no initialization or rules are required for the file tag
            return;

        }

        // default required?
        $defaultRequired = $this->getFormAttribute('x:required', '1');

        if ($this->getAttribute('x:required') === null) {

            $this->setAttribute('x:required', $defaultRequired);

        }

        // add initializers and rules
        $group       = $this->getSafeAttribute('x:group');
        $name        = $this->getSafeAttribute('name');
        $remove      = $this->getAttribute('x:remove');
        $required    = $this->getAttribute('x:required');
        $requiredErr = $this->getSafeAttribute('x:required-err');

        // INITIALIZER: get parameter value
        $this->addInitializer("\$value = \$this->get(\$name);");

        if (!($this instanceof FormMultiSelectTag)) {

            // INITIALIZER: parameter value type check (not used for select boxes so it must be a string)
            $this->addInitializer("if (!is_string(\$value)) {\n" .
                                  "    \$value = '';\n" .
                                  "    \$this->request->params->set(\$name, '');\n" .
                                  "}");

        }

        if ($this instanceof FormTextareaTag) {

            // INITIALIZER: replace invisible chars (except horiz tab, new line, carriage return)
            $this->addInitializer("\$value = preg_replace('#[\\000-\\010\\016-\\037\\013\\014\\177]#', '', \$value);\n" .
                                  "\$this->request->params->set(\$name, \$value);");

        } else if (!($this instanceof FormCheckboxTag) && !($this instanceof FormRadioTag) && !($this instanceof FormSelectTag)) {

            // INITIALIZER: replace invisible chars
            $this->addInitializer("\$value = preg_replace('#[\\000-\\037\\177]#', '', \$value);\n" .
                                  "\$this->request->params->set(\$name, \$value);");

        }

        if (!($this instanceof FormSelectTag) && $this->getAttribute('x:nohtml', '1') === '1') {

            // INITIALIZER: strip html tags
            $this->addInitializer("\$value = strip_tags(\$value);\n" .
                                  "\$this->request->params->set(\$name, \$value);");

        }

        if ($remove !== null) {

            // INITIALIZER: remove characters
            $remove = preg_quote($remove, '#');

            $this->addInitializer("\$value = preg_replace('#[{$remove}]#', '', \$value);\n" .
                                  "\$this->request->params->set(\$name, \$value);");

        }

        if (!($this instanceof FormMultiSelectTag) && !($this instanceof FormCheckboxTag)) {

            // start validation rules
            if ($required === '1' && $group === null) {

                // RULE: value is required and no group is provided
                $this->addRule("if (\$value == '') {\n" .
                               "    \$this->errors[\$name] = '{$requiredErr}';\n" .
                               "}");

            } else if ($required === '0' && $group === null) {

                // RULE: value is not required
                $this->addRule("if (\$value == '') {\n" .
                               "}");

            } else if ($required === '1' && $group !== null) {

                // RULE: value is required but we do have a group
                $this->addRule("if (\$value == '') {\n" .
                               "    if (isset(\$this->groups['{$group}']) && \$this->groups['{$group}'] === true) {\n" .
                               "        \$this->errors[\$name] = '{$requiredErr}';\n" .
                               "    }\n" .
                               "}");

            } else if ($required === '0' && $group !== null) {

                // RULE: value is not required but we do have a group
                $this->addRule("if (\$value == '') {\n" .
                               "}");

            }

        }

    }

}