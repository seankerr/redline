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

class FormErrorsTag extends RLTag {

    /**
     * Draw the tag.
     *
     * @return string The drawn tag.
     */
    public function draw () {

        $body    = $this->getBody();
        $content = preg_match('#(\[error\])(.*?)(\[/error\])#i', $body, $matches);
       
        if (count($matches)) {
            
            $arg1    = $matches[1];
            $arg2    = str_replace("'", "\\'", $matches[2]);
            $arg3    = $matches[3];
            $foreach = "    <?php\n" .
                       "    foreach (\$errors as \$__param => \$__error):\n" .
                       "        echo '{$arg2}';\n" .
                       "    endforeach;\n" .
                       "    ?>";
            $body    = str_replace("{$arg1}{$arg2}{$arg3}", $foreach, $body);

        }
        
        $body = str_replace(array('%param', '%error'),
                            array("' . \$__param . '", "' . \$__error . '"),
                            $body);

        return "<?php\n" .
               "if (isset(\$errors) && count(\$errors)):\n" .
               "    ?>{$body}<?php\n" .
               "endif; \n" .
               "?>";
    
    }

}
