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

define('RL_CACHE_IMPLY', 0);
define('RL_CACHE_CHECK', 1);
define('RL_CACHE_FORCE', 2);

class RLCore {

    protected
        $request = null;

    /**
     * Create a new RLCore instance.
     *
     * @param string The absolute filesystem path to the web application root directory.
     * @param string The absolute filesystem path to the configuration file.
     * @param string The absolute filesystem path to the cache directory.
     * @param int    The cache mode.
     */
    public function __construct ($appDir, $configFile, $cacheDir, $cacheMode) {

        define('RL_APP_DIR',     $appDir);
        define('RL_CACHE_DIR',   $cacheDir);
        define('RL_CACHE_MODE',  $cacheMode);
        define('RL_LIB_DIR',     "{$appDir}/lib");
        define('RL_MODEL_DIR',   "{$appDir}/models");
        define('RL_MODULE_DIR',  "{$appDir}/modules");
        define('RL_TAG_DIR',     "{$appDir}/tags");
        define('RL_REDLINE_DIR', dirname(__FILE__));

        // initial error settings
        ini_set('display_errors',  1);
        ini_set('error_reporting', E_ALL | E_STRICT);

        require_once(RL_REDLINE_DIR . '/RLAction.class.php');
        require_once(RL_REDLINE_DIR . '/RLException.class.php');
        require_once(RL_REDLINE_DIR . '/RLRequest.class.php');
        require_once(RL_REDLINE_DIR . '/RLProvider.class.php');
        require_once(RL_REDLINE_DIR . '/RLParamProvider.class.php');
        require_once(RL_REDLINE_DIR . '/RLSessionProvider.class.php');
        require_once(RL_REDLINE_DIR . '/RLModel.class.php');
        require_once(RL_REDLINE_DIR . '/RLUtil.class.php');

        ini_set('display_errors', 0);

        // check config settings
        $host    = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $success = false;

        if ($cacheMode > RL_CACHE_IMPLY || !file_exists("{$cacheDir}/config.php") || filemtime("{$appDir}/config.xml") > filemtime("{$cacheDir}/config.php")) {

            // load config xml
            $configXML = simplexml_load_file($configFile);

            // parse defines
            $defines = $this->parseDefines($configXML->defines);

            // parse includes
            $includes = $this->parseIncludes($configXML->includes);

            // parse routes
            $routes = $this->parseRoutes($configXML->routes);

            // write cache file
            $cacheData = "<?php\n\n" .
                         "// defines\n";

            foreach ($defines as $key => $value) {

                $value      = str_replace("'", "\\'", $value);
                $cacheData .= "define('{$key}', '{$value}');\n";

            }

            $cacheData .= "\n" .
                          "// includes\n" .
                          "\$includes = " . var_export($includes, 1) . ";\n\n" .
                          "// routes\n" .
                          "\$GLOBALS['rlroutes'] = " . var_export($routes, 1) . ";";

            file_put_contents("{$cacheDir}/config.php", $cacheData);

        }

        // load configuration
        require_once("{$cacheDir}/config.php");

        // load includes
        ini_set('display_errors', 1);

        foreach ($includes as $include) {

            require_once($include);

        }

        // init core
        ini_set('date.timezone',  RL_TIMEZONE);
        ini_set('display_errors', 0);
        ini_set('html_errors',    0);

        set_error_handler(array($this, 'handleError'));
        set_exception_handler(array($this, 'handleException'));

        // initialize the request object
        $requestClass  = RL_REQUEST_CLASS;
        $this->request = new $requestClass($this);

    }

    /**
     * Dispatch the request.
     *
     * @return void
     */
    public function dispatch () {

        ob_start();

        $this->forward($_GET['__rlquery']);

        ob_end_flush();

    }

    /**
     * Find a suitable route for the current request.
     *
     * @param array  The associative array of routes.
     * @param string The path each route will be matched against.
     *
     * @return string The matching route information, if one is found, otherwise null.
     */
    private function findRoute ($routes, $path) {

        foreach ($routes as $key => $file) {

            if (preg_match("#{$key}#", $path, $args)) {

                if (count($args) > 1) {

                    // we have more matched groups
                    foreach ($args as $name => $value) {

                        if (!is_int($name)) {

                            // set matched group as request parameter
                            $this->request->params->set($name, $value);

                        }

                    }

                }

                if (is_array($file)) {

                    return $this->findRoute($file, substr($path, strlen($args[0])));

                }

                return $file;

            }

        }

        return null;

    }

    /**
     * Internally forward the request.
     *
     * @param string The path to invoke.
     *
     * @return void
     */
    public function forward ($path) {

        // reset template
        $this->request->template = null;

        // find suitable route
        $route = $this->findRoute($GLOBALS['rlroutes'], $path);

        if ($route === null) {

            // couldn't find a route
            $this->request->redirect(RL_PAGE_NOT_FOUND_URL);

        }

        // set action details
        $this->request->action     = $route;
        $this->request->actionFile = RL_MODULE_DIR . "/{$route}.php";

        // load action file
        $actionClass = basename($route);

        if (RL_CACHE_MODE > RL_CACHE_IMPLY) {

            // we're running in a slower method of operation so we might as well check for the
            // file and class existence
            if (!is_readable($this->request->actionFile)) {

                throw new RLException("Action file '?' does not exist", $this->request->actionFile);

            }

            // allow us to display compile-time errors temporarily
            ini_set('display_errors', 1);

            // load action file
            require_once($this->request->actionFile);

            // disable compile-time errors
            ini_set('display_errors', 0);

            if (!class_exists($actionClass)) {

                throw new RLException("Action file '?' does not contain class '?'",
                                      $this->request->actionFile, $actionClass);

            }

        } else {

            // allow us to display compile-time errors temporarily
            ini_set('display_errors', 1);

            // load action file
            require_once($this->request->actionFile);

            // disable compile-time errors
            ini_set('display_errors', 0);

        }

        $actionObj = new $actionClass($this->request);

        // determine method to call
        if ($this->request->isXHR) {

            // call the XHR method
            $actionMethod = 'xhr';

        } else {

            // call the REQUEST_METHOD style method
            $actionMethod = strtolower($this->request->method);

        }

        // call action method and render the template if one is set
        $actionObj->$actionMethod($this->request);

        if ($this->request->template !== null) {

            $this->render();

        }

    }

    /**
     * Handle a triggered PHP error.
     *
     * @param int    The error level.
     * @param string The error message.
     * @param string The file in which the error occured.
     * @param int    The line at which the error occured.
     * @param array  The array of scope variables at the time of the error.
     *
     * @return void
     */
    public function handleError ($level, $message, $file = null, $line = null, $context = null) {

        if ($level == E_USER_NOTICE) {

            $level = 'DEBUG';

        } else if ($level == E_WARNING || $level == E_USER_WARNING) {

            $level = 'WARNING';

        } else if ($level == E_NOTICE) {

            $level = 'NOTICE';

        } else {

            $level = 'ERROR';

        }

        // possibly strip error file
        $file = strpos($file, RL_APP_DIR) === 0 ? substr($file, strlen(RL_APP_DIR) + 1) : $file;

        // format message
        $messageDate      = date(RL_DATE_FORMAT);
        $messageFormatted = "[%s] %s (%s:%d) %s\n";
        $messageFormatted = sprintf($messageFormatted, $messageDate, $level, $file, $line, $message);

        // write log file?
        if (strlen(RL_LOG_FILE) > 0) {

            file_put_contents(RL_LOG_FILE, $messageFormatted, FILE_APPEND);

        }

        $report = error_reporting();

        if ($this->request->isXHR) {

            // xhr request
            if (RL_DISPLAY_ERRORS && $report) {

                // send back the error details
                echo json_encode(array('redline_error' => array('date'    => $messageDate,
                                                                'level'   => $level,
                                                                'message' => $message,
                                                                'file'    => $file,
                                                                'line'    => $line)));

                exit;

            }

            // send back a redirect request
            echo json_encode(array('redirect' => RL_ERROR_URL));
            exit;

        }

        if (RL_DISPLAY_ERRORS && $report) {

            if ($level != 'DEBUG') {

                if ($this->request->method != 'CLI') {

                    // print error details to browser
                    ob_end_clean();

                    echo '<html>
                          <head>
                          <title>Redline Error</title>
                          </head>
                          <body>
                          <table cellspacing="5" style="background:  #FEE;
                                                        border:      solid 1px #760000;
                                                        font-family: verdana, helvetica, arial, sans-serif;
                                                        width:       100%;">
                            <tr>
                                <th colspan="2" style="background:  #760000;
                                                       color:       #FFF;
                                                       font-weight: bold;">Redline Error</th>
                            </tr>
                            <tr>
                                <td style="width: 1%;"><b>Level:</b></td>
                                <td style="color: #F00; font-weight: bold; width: 99%;">' . $level . '</td>
                            </tr>
                            <tr>
                                <td><b>File:</b></td>
                                <td>' . $file . '</td>
                            </tr>
                            <tr>
                                <td><b>Line:</b></td>
                                <td>' . $line . '</td>
                            </tr>
                            <tr>
                                <td valign="top"><b>Message:</b></td>
                                <td>' . htmlentities($message) . '</td>
                            </tr>
                        </table>
                        </body>
                        </html>';

                } else {

                    echo "\n===== Redline Error =====\n" .
                         "Level: {$level}\n" .
                         "File:  {$file}\n" .
                         "Line:  {$line}\n\n" .
                         "{$message}\n";

                }

                exit;

            }

            // debug message (we don't exit for this)
            echo $messageFormatted;

        } else if ($report) {

            // redirect to error url
            $this->request->redirect(RL_ERROR_URL);

        }

    }

    /**
     * Handle an uncaught PHP exception.
     *
     * @param Exception The exception.
     *
     * @return void
     */
    public function handleException ($e) {

        $this->handleError(E_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());

    }

    /**
     * Parse the defines.
     *
     * @param Object The simplexml element we're iterating.
     *
     * @return array The associative array of defines.
     */
    private function parseDefines ($xml) {

        $defines = array();

        foreach ($xml->children() as $child) {

            if (!isset($child['name'])) {

                trigger_error('Define is missing name attribute in config.xml');
                exit;

            }

            $name  = strtoupper((string) $child['name']);
            $value = str_replace(array("'",  '{RL_APP_DIR}'),
                                 array("\\'", RL_APP_DIR),
                                 (string) $child);

            $defines[$name] = $value;

        }

        return $defines;

    }

    /**
     * Parse the includes.
     *
     * @param Object The simplexml element we're iterating.
     *
     * @return array The indexed array of files to include.
     */
    private function parseIncludes ($xml) {

        $includes = array();

        foreach ($xml->children() as $child) {

            if (!isset($child['file'])) {

                trigger_error('Include is missing file attribute in config.xml');
                exit;

            }

            $includes[] = RLUtil::replaceConstants((string) $child['file']);

        }

        return $includes;

    }

    /**
     * Parse the routes.
     *
     * @param Object The simplexml element we're iterating.
     *
     * @return array The associative array of routes.
     */
    private function parseRoutes ($xml) {

        $routes = array();

        foreach ($xml->children() as $child) {

            if (!isset($child['pattern'])) {

                trigger_error('Route is missing a matching pattern');
                exit;

            }

            $pattern = (string) $child['pattern'];

            preg_match_all('#\(([a-z_][^:]*):[^)]+\)#i', $pattern, $matches);

            for ($i = 0, $icount = count($matches[1]); $i < $icount; $i++) {

                $name    = $matches[1][$i];
                $pattern = str_replace("({$name}:", "(?P<{$name}>", $pattern);

            }

            if (isset($child['action'])) {

                $routes[$pattern] = (string) $child['action'];

            } else if (isset($child['include'])) {

                // load an external xml file for routes
                $file   = RLUtil::replaceConstants((string) $child['include']);
                $subxml = simplexml_load_file($file);

                $routes[$pattern] = $this->parseRoutes($subxml);

            } else {

                $routes[$pattern] = $this->parseRoutes($child);

            }

        }

        return $routes;

    }

    /**
     * Render a template.
     *
     * @return void
     */
    private function render () {

        $this->request->template      = RL_MODULE_DIR . "/{$this->request->template}";
        $this->request->templateCache = RL_CACHE_DIR . '/templates/' . str_replace('/', '_', $this->request->template);

        if (RL_CACHE_MODE > RL_CACHE_IMPLY) {

            if (RL_CACHE_MODE == RL_CACHE_FORCE || (!file_exists($this->request->templateCache) || filemtime($this->request->templateCache) < filemtime($this->request->template))) {

                require_once(RL_REDLINE_DIR . '/RLTemplate.class.php');

                $templateObj = new RLTemplate($this->request->template, $this->request->templateCache);

                // remove any static attributes that may have been set during the load process
                RLStaticTag::$staticAttributes = array();

                $templateObj->parse();

                // remove any static attributes that may have been set during the load process
                RLStaticTag::$staticAttributes = array();

            }

        }

        if ($this->request->form !== null && $this->request->form->getErrorCount() > 0) {

            $this->request->attribs->set('errors', $this->request->form->getErrors());

        }

        extract($this->request->attribs->getArray());

        $cookie  = $_COOKIE;
        $params  = $this->request->params;
        $request = $this->request;
        $session = $this->request->session;

        require($this->request->templateCache);

    }

}
