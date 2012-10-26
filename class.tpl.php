<?php
require_once dirname(__FILE__) . '/class.tpltools.php';

/**
 * constant DEBUG
 *  if defined, recompile all templates
 */

define('TPL_DEBUG', 'debug');

/**
 * class tpl
 */

class tpl {
	public $template_dir = '';
	public $compile_dir  = '';

	private $tpl    = '';
	public $vars   = array();


	public function __construct($newDir = '') {
		$this->template_dir = array(getcwd() . '/templates/');
		$this->compile_dir  = getcwd() . '/templates_c/';

		if($newDir != '')
			$this->addDir($newDir);
	}

	public function addDir($newDir) {
		if(is_array($newDir)) {
			foreach($newDir as $dir)
				$this->template_dir[] = $dir;
		}
		else
			$this->template_dir[] = $newDir;
	}

	public function assign($vars, $val = '') {
		if(is_array($vars)) {
			foreach($vars as $name => $value)
				$this->vars[$name] = $value;
		}
		else
			$this->vars[$vars] = $val;
	}

	private function compile($tplFile, $outputFile) {
		require_once dirname(__FILE__) . '/class.tplcompile.php';
		new tplcompile($tplFile, $outputFile, $this->template_dir);
	}

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

/**
 * user's fonctions for the template engine
 */

function tplfunction_assign($params, $_tpl) {
	$_tpl->assign($params['var'], $params['value']);
}

?>
