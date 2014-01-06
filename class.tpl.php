<?php
/*
Copyright (c) 2011-2013, rohm1 <rp@rohm1.com>.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

 * Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.

 * Neither the name of rohm1 nor the names of his
   contributors may be used to endorse or promote products derived
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Retrieves some tools
 */
require_once __DIR__ . '/class.tpltools.php';
require_once __DIR__ . '/tplfunctions.php';

/**
 * constant TPL_DEBUG
 * if defined, recompiles all templates
 */
// define('TPL_DEBUG', 'debug');

/**
 * Class tpl
 * This is the main class and the only one the user should instanciate.
 */
class tpl
{
    /**
     * List of directories where to look for the template files
     *
     * @var array
     */
    public $template_dir = [];

    /**
     * Directory where compiled templates are cached
     *
     * @var string
     */
    public $compile_dir;

    /**
     * Directory where resulting pages are cached
     *
     * @var string
     */
    public $content_dir;

    /**
     * List of variables assigned in the PHP code and that can be retrieved in the templates
     *
     * @var array
     */
    public $vars = [];

    /**
     * The current page name
     */
    private $page_name = '';


    /**
     * Constructor
     *
     * @param mixed $newDir a directory to add to self::$template_dir
     * @return void
     */
    public function __construct($newDir = '')
    {
        $this->addDir(getcwd() . '/templates/');
        $this->compile_dir  = getcwd() . '/templates_c/';
        $this->content_dir  = getcwd() . '/pages_c/';

        if ($newDir != '') {
            $this->addDir($newDir);
        }
    }

    /**
     * Adds a directory of list of directories to self::$template_dir
     *
     * @param mixed $newDir can be a string or an array of strings
     * @return tpl
     */
    public function addDir($newDir)
    {
        if (is_array($newDir)) {
            foreach ($newDir as $dir) {
                $this->template_dir[] = $dir;
                if (file_exists($dir . '/tplfunctions.php')) {
                    include $dir . '/tplfunctions.php';
                }
            }
        } else {
            $this->template_dir[] = $newDir;
            if (file_exists($newDir . '/tplfunctions.php')) {
                include $newDir . '/tplfunctions.php';
            }
        }

        return $this;
    }

    /**
     * Assigns a variable or list of variables in self::$vars
     *
     * @param mixed $vars key for $val or array of key/values to assign
     * @param mixed $val if $vars is a string, this will be the value assign to the $vars key
     * @param bool $parse whether the content of the variable will be parsed as a template or not
     * @return tpl
     */
    public function assign($vars, $val = '', $parse = false)
    {
        if (is_array($vars)) {
            foreach ($vars as $name => $value) {
                $this->vars[$name] = ['_value_' => $value, '_parse_' => $parse];
            }
        } else {
            $this->vars[$vars] = ['_value_' => $val, '_parse_' => $parse];
        }

        return $this;
    }

    /**
     * Return whether to use the cache or nor
     *
     * @param string $fname the file to check for
     * @return boolean
     */
    protected function use_cache($fname)
    {
        return !defined('TPL_DEBUG')           &&
            !isset($_GET['tplnocompilecache']) &&
            !isset($_GET['tplnocontentcache']) &&
            !isset($_GET['tplraw'])            &&
            file_exists($fname);
    }

    /**
     * Compiles a template file
     *
     * @param string $tplFile absolute path to the template source
     * @param string $outputFile absolute path to the resulting compiled file
     * @return void
     * @see class.tplcompile.php
     */
    protected function compile($tplFile, $outputFile)
    {
        require_once __DIR__ . '/class.tplcompile.php';
        new tplcompile($tplFile, $outputFile, $this->template_dir);
    }

    /**
     * Displays the assigned variabes in the given template
     *
     * @param string $file the template file name to use
     * @return void
     */
    public function display($file = '')
    {
        if ($file == 'json' || (isset($_GET['format']) && $_GET['format'] == 'json')) {
            echo json_encode($this->vars);
        } else {
            foreach ($this->template_dir as $dir) {
                if (file_exists($dir . $file)) {
                    $this->assign('tpl', array(
                        'const' => get_defined_constants(),
                        'get'   => $_GET,
                        'post'  => $_POST,
                    ));

                    $tpl = $dir . $file;
                    $compiled = $this->compile_dir . str_replace(array('/', ' '), array('__', '-'), $tpl) . '.php';
                    if (!$this->use_cache($compiled)) {
                        $this->compile($tpl, $compiled);
                        require $compiled;
                    } else {
                        require $compiled;
                        foreach ($deps as $file => $md5) {
                            $dir = dirname($file) . '/';
                            if (!in_array($dir, $this->template_dir) || md5(file_get_contents($file)) != $md5) {
                                $this->compile($tpl, $compiled);
                                require $compiled;
                                break;
                            }
                        }
                    }

                    call_user_func($function, $this);
                    break;
                }
            }
        }
    }

    /**
     * Returns the result of the template instead of displaying it
     *
     * @param string $file the template file name to use
     * @param boolean $save whether to save the capture or not
     *     captures are saved to to tpl::$content_dir
     * @return string the result of the template
     * @see tpl::display()
     */
    public function capture($file = '', $save = true)
    {
        ob_start();
        $this->display($file);
        $html = ob_get_contents();
        ob_end_clean();

        if ($save) {
            $f = @fopen($this->content_dir . str_replace(array('/', ' '), array('__', '-'), $this->get_page_name() . '~~' . $file) . '.html', 'w');
            if ($f) {
                fwrite($f, $html);
                fclose($f);
            }
        }

        return $html;
    }

    /**
     * Retrieves a cached file
     *
     * @param string $file the template file name to use
     * @param int $max_age max age of the file, in seconds
     * @return mixed the cached file content if the cached file exists
     *     and younger than $max_age, false otherwise
     */
    public function get_cached_file($file, $max_age = 3600)
    {
        $fname = $this->content_dir . str_replace(array('/', ' '), array('__', '-'), $this->get_page_name() . '~~' . $file) . '.html';

        if ($this->use_cache($fname) && time() - @filemtime($fname) < $max_age) {
            return file_get_contents($fname);
        }

        return false;
    }

    /**
     * Returns the current URI cleaned of the templates' variables
     *
     * @return current URI cleaned
     */
    private function get_page_name()
    {
        if ($this->page_name == '') {
            $query_string = preg_replace('#\&{2,}#', '&', str_replace(array('tplnocompilecache', 'tplnocontentcache', 'tplraw'), '', $_SERVER['QUERY_STRING']));
            $this->page_name = $_SERVER['SCRIPT_FILENAME'] . ($query_string != '' ? '?' . $query_string : '');
        }

        return $this->page_name;
    }

    /**
     * Clears the compile cache
     *
     * @see tpl::clear_dir()
     * @return tpl
     */
    public function clear_compile_cache()
    {
        $this->clear_dir($this->compile_dir);

        return $this;
    }

    /**
     * Clears the content cache
     *
     * @see tpl::clear_dir()
     * @return tpl
     */
    public function clear_content_cache()
    {
        $this->clear_dir($this->content_dir);

        return $this;
    }

    /**
     * Clears a directory
     *
     * @return void
     * @param string $dir the directory to clear
     */
    protected function clear_dir($dir)
    {
        $handle = opendir($dir);
        if ($handle) {
            while (false !== ($fname = readdir($handle))) {
                if ($fname != '.' && $fname != '..' &&
                    $fname != 'index.php' && $fname != '.htaccess' /*to protect a directory*/
                ) {
                    @unlink($dir . $fname);
                }
            }
           closedir($handle);
        }
    }

}
