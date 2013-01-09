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

class FormCheckboxTag extends RLValueTag {

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        $formId  = $this->getForm()->getAttribute('id');
        $name    = $this->getSafeAttribute('name');
        $on      = $this->getSafeAttribute('x:on');
        $default = $this->getSafeAttribute('x:default');
        $content = "<?php\n" .
                   "\$__checked = '';\n" .
                   "\$__value = \$request->params->get('{$name}', null);\n" .
                   "if (\$__value === '{$on}' || (\$__value === null && \$request->method == 'GET' && '{$default}' === '{$on}')):\n" .
                   "    \$__checked = ' checked=\"checked\"';\n" .
                   "endif;\n" .
                   "?>";

        return $content .
               "<input{$this->getHTMLAttributes()}<?php echo \$__checked; ?>>";

    }

    /**
     * Draw the validation code for this tag.
     *
     * @return string The validation code.
     */
    public function drawValidation () {

        $off  = $this->getSafeAttribute('x:off');
        $on   = $this->getSafeAttribute('x:on');

        // RULE: check on/off values
        $this->addRule("if (\$value != '{$on}') {\n" .
                       "    \$value = '{$off}';\n" .
                       "    \$this->set(\$name, '{$off}');\n" .
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

        $this->verifyAttributes('x:on', 'x:off');

        $on = $this->getSafeAttribute('x:on');

        $this->setAttribute('type',  'checkbox');
        $this->setAttribute('value', "<?php echo htmlspecialchars('{$on}'); ?>");

    }

}