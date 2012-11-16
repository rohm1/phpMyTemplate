<?php
/*
Copyright (c) 2011-2012, rohm1 <rp@rohm1.com>.
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
require_once dirname(__FILE__) . '/class.tpltools.php';

/**
 * constant DEBUG
 * if defined, recompile all templates
 */
define('TPL_DEBUG', 'debug');

/**
 * Class tpl
 * This is the main class and the only one the user should instanciate.
 */
class tpl {
	/**
	 * List of directories where to look for the template files
	 *
	 * @var array
	 */
	public $template_dir;

	/**
	 * Directory where compiled templates are cached
	 *
	 * @var string
	 */
	public $compile_dir;

	/**
	 * List of variables assigned in the PHP code and that can be retrieved in the templates
	 *
	 * @var array
	 */
	public $vars   = array();


	/**
	 * Constructor
	 *
	 * @param mixed $newDir a directory to add to self::$template_dir
	 * @return void
	 */
	public function __construct($newDir = '') {
		$this->template_dir = array(getcwd() . '/templates/');
		$this->compile_dir  = getcwd() . '/templates_c/';

		if($newDir != '')
			$this->addDir($newDir);
	}

	/**
	 * Adds a directory of list of directories to self::$template_dir
	 *
	 * @param mixed $newDir can be a string or an array of strings
	 * @return void
	 */
	public function addDir($newDir) {
		if(is_array($newDir)) {
			foreach($newDir as $dir)
				$this->template_dir[] = $dir;
		}
		else
			$this->template_dir[] = $newDir;
	}

	/**
	 * Assigns a variable or list of variables in self::$vars
	 *
	 * @param mixed $vars key for $val or array of key/values to assign
	 * @param mixed $val if $vars is a string, this will be the value assign to the $vars key
	 * @return void
	 */
	public function assign($vars, $val = '') {
		if(is_array($vars)) {
			foreach($vars as $name => $value)
				$this->vars[$name] = $value;
		}
		else
			$this->vars[$vars] = $val;
	}

	/**
	 * Compiles a template file
	 *
	 * @param string $tplFile absolute path to the template source
	 * @param string $outputFile absolute path to the resulting compiled file
	 * @return void
	 * @see class.tplcompile.php
	 */
	private function compile($tplFile, $outputFile) {
		require_once dirname(__FILE__) . '/class.tplcompile.php';
		new tplcompile($tplFile, $outputFile, $this->template_dir);
	}

	/**
	 * Displays the assigned variabes in the given template
	 *
	 * @param string $file the template file name to use
	 * @return void
	 */
	public function display($file = '') {
		if($file == 'json' || (isset($_GET['format']) && $_GET['format'] == 'json'))
			echo json_encode($this->vars);
		else {
			foreach($this->template_dir as $dir) {
				if(file_exists($dir . $file)) {
					$this->vars['tpl']['const'] = get_defined_constants();
					$this->vars['tpl']['get'] = $_GET;
					$this->vars['tpl']['post'] = $_POST;

					$tpl = $dir . $file;
					$compiled = $this->compile_dir . str_replace('/', '.', $tpl);
					if(defined('TPL_DEBUG') || isset($_GET['tplnocache']) || isset($_GET['raw']) || !file_exists($compiled)) {
						$this->compile($tpl, $compiled);
						require $compiled;
					}
					else {
						include $compiled;
						foreach($deps as $file => $md5) {
							$dir = dirname($file) . '/';
							if(!in_array($dir, $this->template_dir) || md5(file_get_contents($file)) != $md5) {
								$this->compile($tpl, $compiled);
								include $compiled;
								break;
							}
						}
					}

					$function($this);
					break;
				}
			}
		}
	}


}

/* user's fonctions for the template engine */

/**
 * Assigns a variable
 * This is a user function that should be used in s templates:
 * {assign var=foo value=bar}
 *
 * @param array $params an array containing the parameters assigned in the template
 * @param object $_tpl the current tpl object
 * @return void
 * @see tpl::assign()
 */
function tplfunction_assign($params, $_tpl) {
	$_tpl->assign($params['var'], $params['value']);
}

?>
