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

class FormFileTag extends RLValueTag {

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        return $this->drawGetValue() . "<input{$this->getHTMLAttributes()}>";

    }

    /**
     * Draw the validation code for this tag.
     *
     * @return string The validation code.
     */
    public function drawValidation () {

        $group       = $this->getSafeAttribute('x:group');
        $minsize     = $this->getAttribute('x:minsize');
        $minsizeErr  = $this->getSafeAttribute('x:minsize-err', 'File is too small');
        $maxsize     = $this->getAttribute('x:maxsize');
        $maxsizeErr  = $this->getSafeAttribute('x:maxsize-err', 'File is too big');
        $required    = $this->getAttribute('x:required');
        $requiredErr = $this->getSafeAttribute('x:required-err', RL_FILE_REQUIRED);
        $uploadErr   = $this->getSafeAttribute('x:upload-err', RL_FILE_INVALID);

        if ($required === null) {

            $required = $this->getFormAttribute('x:required', '1');

        }

        // INITIALIZER: get the file
        $this->addInitializer("\$file = isset(\$_FILES[\$name]) ? \$_FILES[\$name] : null;");

        if ($required === '1' && $group === null) {

            // RULE: file is required and no group is provided
            $this->addRule("if (\$file === null || \$file['error'] == UPLOAD_ERR_NO_FILE) {\n" .
                           "    \$this->errors[\$name] = '{$requiredErr}';\n" .
                           "}");

        } else if ($group !== null) {

            // RULE: file is not required but we do have a group
            $this->addRule("if (\$file === null || \$file['error'] == UPLOAD_ERR_NO_FILE) {\n" .
                           "    if (isset(\$this->groups['{$group}']) && \$this->groups['{$group}'] === true) {\n" .
                           "        \$this->errors[\$name] = '{$requiredErr}';\n" .
                           "    }\n" .
                           "}");

        }

        // RULE: upload error
        $this->addRule("if (\$file['error'] == UPLOAD_ERR_CANT_WRITE ||\n" .
                       "           \$file['error'] == UPLOAD_ERR_EXTENSION  ||\n" .
                       "           \$file['error'] == UPLOAD_ERR_NO_TMP_DIR ||\n" .
                       "           \$file['error'] == UPLOAD_ERR_PARTIAL) {\n" .
                       "    \$this->errors[\$name] = '{$uploadErr}';\n" .
                       "} else if (\$file['error'] == UPLOAD_ERR_INI_SIZE) {\n" .
                       "    \$this->errors[\$name] = '{$maxsizeErr}';\n" .
                       "}");

        if ($minsize !== null) {

            // RULE: minimum file size
            $minsizeErr = str_replace('%', $minsize, $minsizeErr);

            $this->addRule("if (\$file['size'] < {$minsize}) {\n" .
                           "    \$this->errors[\$name] = '{$minsizeErr}';\n" .
                           "}");

        }

        if ($maxsize !== null) {

            // RULE: maximum file size
            $maxsizeErr = str_replace('%', $maxsize, $maxsizeErr);

            $this->addRule("if (\$file['size'] > {$maxsize}) {\n" .
                           "    \$this->errors[\$name] = '{$maxsizeErr}';\n" .
                           "}");

        }

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

        $this->setAttribute('type', 'file');

    }

}