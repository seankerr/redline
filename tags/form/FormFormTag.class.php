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

class FormFormTag extends RLTag {

    protected
        $ids = array();

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

        // make sure this tag does not have a parent FormFormTag
        $parent = $this->getParent();

        while ($parent !== null && !($parent instanceof FormFormTag)) {

            $parent = $parent->getParent();

        }

        if ($parent instanceof FormFormTag) {

            throw new RLException('Tag <?:?> in template ? line ? char ? cannot have parent ' .
                                  '<form:form> tag', $this->getNamespace(), $this->getName(),
                                  $this->getFile(), $this->getLine(), $this->getChar());

        }

        // set defaults
        $this->setAttribute('x:required', '1');

    }

    /**
     * Add a child tag id to this form.
     *
     * @params string The tag id.
     *
     * @return void
     */
    public function addId ($id) {

        if (in_array($id, $this->ids)) {

            throw new RLException('Tag <?:?> in template ? line ? char ? already has a tag with the id ' .
                                  'of ?', $this->getNamespace(), $this->getName(),
                                  $this->getFile(), $this->getLine(), $this->getChar(), $id);

        }

        // add id
        $this->ids[] = $id;

    }

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        // form class details
        $fields  = array();
        $groups  = array();
        $methods = array();
        $id      = $this->getAttribute('id');

        foreach ($this->getValueChildren($this) as $child) {

            if ($child instanceof FormButtonTag) {

                // do not do buttons
                continue;

            } else if ($child instanceof FormGroupTag) {

                // grouping conditions
                $cid      = $child->getAttribute('id');
                $groups[] = "\$this->groups['{$cid}'] = {$child->drawValidation()};";

                continue;

            }

            // we care about these value tags
            $name = $child->getAttribute('name');

            if ($child instanceof FormSelectTag && $child->getAttribute('x:multiple') === '1') {

                // we have to strip off the auto-appended [] from the name
                $name = substr($name, 0, -2);

            }

            $default   = $this->getAttribute('x:default');
            $fields[]  = $name;
            $methods[] = "public function validate_{$name} (\$name = '{$name}') {\n" .
                             "    if (!in_array(\$name, \$this->fields)) {\n" .
                             "        \$this->fields[] = \$name;\n" .
                             "    }\n" .
                             "{$child->drawValidation()}\n" .
                             "}";

        }

        if (count($fields)) {

            $fields = "array('" . implode("','", $fields) . "');";

        } else {

            $fields = 'array();';

        }

        $groups     = $this->formatCode(implode("\n", $groups), 8);
        $methods    = $this->formatCode(implode("\n", $methods), 4);
        $extend     = $this->getAttribute('x:extend', 'RLModel');
        $extendFile = $this->getAttribute('x:extend-file');
        $className  = ucfirst($id);

        if ($extend !== 'RLModel') {

            if ($extendFile === null) {

                $extendFile = RL_MODEL_DIR . "/{$extend}.php";

            } else {

                $extendFile = RL_MODEL_DIR . "/{$extendFile}";

            }

            // attempt to load extends file (for testing)
            require_once($extendFile);

            $extendFile = "require_once('{$extendFile}');\n";

            // make sure class exists
            if (!class_exists($extend)) {

                throw new RLException('Tag <?:?> in template ? line ? char ? expects non-existent ' .
                                      'class ?', $this->getNamespace(), $this->getName(),
                                      $this->getFile(), $this->getLine(), $this->getChar(),
                                      $extend);

            }

        }

        $class = "<?php\n" .
                 $extendFile .
                 "class {$className}FormModel extends {$extend} {\n" .
                 "    public function __construct (\$request, \$values) {\n" .
                 "        \$this->fields = {$fields}\n" .
                 "        parent::__construct(\$request, \$values);\n" .
                 "        {$groups}\n" .
                 "    }\n" .
                 "    {$methods}\n" .
                 "}";

        // get DOM root
        $root = $this->getParent();

        while ($root->getNamespace() != 'rlroot' || $root->getParent() !== null) {

            $root = $root->getParent();

        }

        // write form model
        $formCache = RL_CACHE_DIR . '/forms/' . str_replace('/', '_', $root->getFile()) . "_{$id}.php";

        if (!file_exists(dirname($formCache))) {

            mkdir(dirname($formCache), 0777, true);

        }

        file_put_contents($formCache, $class);

        return "<form{$this->getHTMLAttributes()}>\n" .
               "<?php\n" .
               "\$__provider = \$request->params;\n" .
               "?>\n" .
               "{$this->getBody()}" .
               "</form>";

    }

    /**
     * Retrieve all RLValueTag children.
     *
     * @param RLTag The parent tag at which we're starting.
     *
     * @return array The indexed array of children.
     */
    private function getValueChildren ($parent) {

        $children = array();

        foreach ($parent->getChildren() as $child) {

            if ($child instanceof RLValueTag) {

                $children[] = $child;

            } else {

                $children = array_merge($children, $this->getValueChildren($child));

            }

        }

        return $children;

    }

    /**
     * Initialization of the tag after attributes have been set.
     *
     * @return void
     */
    public function init () {

        parent::init();

        $this->verifyAttributes('id');

    }

}
