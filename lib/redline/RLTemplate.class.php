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

class RLTemplate {

    protected
        $content       = null,
        $stackCount    = 0,
        $template      = null,
        $templateCache = null;

    protected static
        $namespaces = null,
        $skipCount  = 0,
        $stack      = array(),
        $tags       = null;

    /**
     * Create a new RLTemplate instance.
     *
     * @param string The absolute filesystem path to the template.
     * @param string The absolute filesystem path to the template cache file.
     */
    public function __construct ($template, $templateCache) {

        $this->content       = file_get_contents($template);
        $this->template      = $template;
        $this->templateCache = $templateCache;

        $this->init();

    }

    /**
     * Initialize the template parser.
     *
     * @return void
     */
    private function init () {

        if (self::$tags !== null) {

            return;

        }

        self::$tags = array();

        // load core tag classes
        require_once(RL_REDLINE_DIR . '/RLTag.class.php');
        require_once(RL_REDLINE_DIR . '/RLStaticTag.class.php');
        require_once(RL_REDLINE_DIR . '/RLValueTag.class.php');

        // initialize namespaces
        $nsfp = opendir(RL_TAG_DIR);

        while (($nsfile = readdir($nsfp)) !== false) {

            if ($nsfile[0] == '.' || !is_dir(RL_TAG_DIR . "/$nsfile")) {

                // not a tag directory
                continue;

            }

            if (!isset(self::$tags[$nsfile])) {

                self::$tags[$nsfile] = array();

            }

            $tagfp = opendir(RL_TAG_DIR . "/$nsfile");

            while (($tagfile = readdir($tagfp)) !== false) {

                $file = RL_TAG_DIR . "/{$nsfile}/{$tagfile}";

                if ($tagfile[0] == '.' || !is_file($file) || !is_readable($file)) {

                    continue;

                }

                // check convention and load tag
                if (preg_match("#^{$nsfile}([a-z][a-z0-9]*)tag\.class\.php\$#i", $tagfile, $match)) {

                    // load tag file
                    require_once($file);

                    $match[1] = strtolower($match[1]);
                    $class    = "{$nsfile}{$match[1]}tag";

                    self::$tags[$nsfile][] = $match[1];

                    if (!class_exists($class)) {

                        throw new RLException("Tag file '{$file}' does not contain class " .
                                              "'{$class}'");

                    }

                } else {

                    throw new RLException("Tag file '{$file}' does not follow naming convention");

                }

            }

            closedir($tagfp);

        }

        closedir($nsfp);

        self::$namespaces = array_keys(self::$tags);

        sort(self::$namespaces);
        array_reverse(self::$namespaces);

        self::$namespaces = implode('|', array_values(self::$namespaces));

    }

    /**
     * Parse the template.
     *
     * @return void
     */
    public function parse () {

        if (count(self::$stack) == 0) {

            // create root tag with no parent
            self::$stack[] = new RLTag(null, 'rlroot', 'rlroot', $this->template, null, null);

        } else {

            $parent = end(self::$stack);

            // create root tag with parent (from another template that is loading this one)
            self::$stack[] = new RLTag($parent, 'rlroot', 'rlroot', $this->template, null, null);

            $parent->addChild(end(self::$stack));

        }

        // match any opening or closing tags with namespaces we know of
        $namespaces = self::$namespaces;

        preg_match_all("#(</|<)({$namespaces}):([^ />]+)\s*(.*?)(/>|(?<!-)>)#si", $this->content,
                       $matches, PREG_OFFSET_CAPTURE);

        // loop over our matches and handle them
        $loopPos = 0;

        for ($i = 0, $icount = count($matches[0]); $i < $icount; $i++) {

            // grab current tag details
            $currentTag    = end(self::$stack);
            $leftBracket   = $matches[1][$i][0];
            $rightBracket  = $matches[5][$i][0];
            $tagAttributes = $matches[4][$i][0];
            $tagName       = $matches[3][$i][0];
            $tagNamespace  = $matches[2][$i][0];

            if (strpos($tagName, '-') !== false) {

                // remove hyphens from tag name
                $tagName = str_replace('-', '', $tagName);

            }

            $tagClass = $tagNamespace . $tagName . 'tag';

            // get current line and character of this tag
            $chunk   = substr($this->content, 0, $matches[1][$i][1]);
            $tagLine = substr_count($chunk, "\n") + 1;
            $tagChar = strlen($chunk) - strrpos($chunk, "\n");

            if ($leftBracket == '</' && $rightBracket == '/>') {

                // invalid tag
                throw new RLException('Invalid tag </?:?/> in template ? line ? character ?',
                                      $tagNamespace, $tagName, $this->template, $tagLine, $tagChar);

            }

            if (!in_array($tagName, self::$tags[$tagNamespace])) {

                // unknown tag
                throw new RLException('Unknown tag ??:?? in template ? line ? character ?',
                                      $leftBracket, $tagNamespace, $tagName, $rightBracket,
                                      $this->template, $tagLine, $tagChar);

            }

            // add the content before this tag to the parent body
            $currentTag->appendBody(substr($this->content, $loopPos, $matches[0][$i][1] - $loopPos));

            // check expectations
            if ($leftBracket == '</') {

                if (count(self::$stack) == 1) {

                    // didn't expect a closing tag
                    throw new RLException('Unexpected closing tag </?:?> in template ? line ? ' .
                                          'character ?', $tagNamespace, $tagName, $this->template,
                                          $tagLine, $tagChar);

                } else if ($tagNamespace != $currentTag->getNamespace() || $tagName != $currentTag->getName()) {

                    // wrong closing tag
                    throw new RLException('Expecting closing tag </?:?> in template ? line ? ' .
                                          'character ?', $currentTag->getNamespace(),
                                          $currentTag->getName(), $this->template, $tagLine,
                                          $tagChar);

                }

                // draw the tag to the parent body
                if (self::$skipCount == 0) {

                    $currentTag->getParent()->appendBody($currentTag->draw());

                } else if ($currentTag instanceof RLStaticTag) {

                    self::$skipCount--;

                }

                // pop the tag off the stack
                array_pop(self::$stack);

                $this->stackCount--;

            } else {

                // create the new tag instance
                $tagClass = str_replace('-', '', $tagClass);
                $tagObj   = new $tagClass($currentTag, $tagNamespace, $tagName, $this->template,
                                          $tagLine, $tagChar);

                // parse attributes
                preg_match_all('#([^\s]+)="([^"]+)"#', $tagAttributes, $attributeMatches);

                for ($x = 0, $xcount = count($attributeMatches[1]); $x < $xcount; $x++) {

                    $name  = $attributeMatches[1][$x];
                    $value = $attributeMatches[2][$x];

                    $tagObj->setAttribute($name, $value);

                }

                if (self::$skipCount == 0) {

                    // post-initialize tag
                    $tagObj->init();

                    // add the new tag as a child to the current tag
                    $currentTag->addChild($tagObj);

                }

                if ($rightBracket == '/>') {

                    // self-closing tag, so we draw the content here
                    if (self::$skipCount == 0) {

                        // add the new tag to the stack
                        self::$stack[] = $tagObj;

                        if (!($tagObj instanceof RLStaticTag) || $tagObj->check()) {

                            $currentTag->appendBody($tagObj->draw());

                        }

                        // remove the new self-closing tag from the stack
                        array_pop(self::$stack);

                    }

                } else {

                    // add the new tag to the stack
                    self::$stack[] = $tagObj;

                    $this->stackCount++;

                    if ($tagObj instanceof RLStaticTag && (!$tagObj->check() || self::$skipCount > 0)) {

                        self::$skipCount++;

                    }

                }

            }

            // update the position to the end of the tag
            $loopPos = $matches[5][$i][1] + strlen($matches[5][$i][0]);

        }

        if ($this->stackCount > 0) {

            // we're missing one or more closing tags
            $tag = end(self::$stack);

            throw new RLException('Missing closing tag for <?:?> in template ? line ? character ?',
                                  $tag->getNamespace(), $tag->getName(), $tag->getFile(),
                                  $tag->getLine(), $tag->getChar());

        }

        // add the remaining template content
        if ($loopPos < strlen($this->content)) {

            end(self::$stack)->appendBody(substr($this->content, $loopPos));

        }

        // only write the main template file, none of the statically included ones
        if (count(self::$stack) == 1) {

            $content = end(self::$stack)->getBody();

            // short-hand php substitutions
            $content = preg_replace('@\{\{#([^}]+)\}\}@e', 'RLStaticTag::getStaticAttribute("\1")', $content);
            $content = preg_replace('#\{\{([^}]+)\}\}#',   '<?php echo \1; ?>', $content);
            $content = preg_replace('#\{%(.*?)%\}#',       '<?php \1 ?>', $content);

            // optimize cache file
            $content = preg_replace('/\?>([\r\n\s]*)<\?php/si', '\1', $content);

            // write cache file
            $cacheDir = dirname($this->templateCache);

            if (!file_exists($cacheDir)) {

                mkdir($cacheDir, 0777, true);

            }

            file_put_contents($this->templateCache, $content);

        }

        // remove last tag
        array_pop(self::$stack);

    }

}
