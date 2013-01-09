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

class RLRequest {

    public
        $action        = null,
        $actionFile    = null,
        $attribs       = null,
        $core          = null,
        $files         = null,
        $form          = null,
        $isSSL         = false,
        $isXHR         = false,
        $method        = 'CLI',
        $params        = null,
        $session       = null,
        $template      = null,
        $templateCache = null;

    /**
     * Create a new RLRequest instance.
     *
     * @param RLCore The singleton instance of RLCore.
     */
    public function __construct ($core) {

        $this->attribs = new RLProvider;
        $this->core    = $core;
        $this->files   = $_FILES;
        $this->params  = new RLParamProvider;
        $this->session = RL_USE_SESSIONS ? new RLSessionProvider : new RLProvider;

        if (isset($_SERVER['HTTP_HOST'])) {

            $this->isXHR  = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
            $this->isSSL  = $_SERVER['SERVER_PORT'] == 443;
            $this->method = $_SERVER['REQUEST_METHOD'];

        }

    }

    /**
     * Load a form model and create a new instance.
     *
     * @param string The form id.
     * @param string The relative (from RL_MODULE_DIR) filesystem path to the template file
     *               containing the form to be loaded.
     *
     * @return RLModel The instantiated form model.
     */
    public function loadFormModel ($id, $template = null) {

        if ($template === null) {

            $template = $this->template;

        } else if ($this->template === null) {

            throw new RLException('Cannot load form model because a template has not been specified');

        }

        $file      = RL_MODULE_DIR . "/{$template}";
        $fileClean = str_replace('/', '_', $file);
        $fileCache = RL_CACHE_DIR . "/templates/{$fileClean}";

        if (RL_CACHE_MODE > RL_CACHE_IMPLY) {

            if (RL_CACHE_MODE == RL_CACHE_FORCE || filemtime($fileCache) < filemtime($file)) {

                require_once(RL_REDLINE_DIR . '/RLTemplate.class.php');

                $templateObj = new RLTemplate($file, $fileCache);

                // remove any static attributes that may have been set during the load process
                RLStaticTag::$staticAttributes = array();

                $templateObj->parse();

                // remove any static attributes that may have been set during the load process
                RLStaticTag::$staticAttributes = array();

            }

        }

        // load form model
        require_once(RL_CACHE_DIR . "/forms/{$fileClean}_{$id}.php");

        $class = "{$id}FormModel";

        return new $class($this, $this->params->getArray());

    }

    /**
     * Redirect the request.
     *
     * @param string The URL.
     *
     * @return void
     */
    public function redirect ($url) {

        if ($this->isXHR) {

            echo json_encode(array('redirect' => $url));
            exit;

        }

        header("Location: $url");
        exit;

    }

}
