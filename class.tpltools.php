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
 * Class tpltools
 * Static class providing tools to the template engine
 */
class tpltools {
	/**
	 * RegExp used to check a variable name
	 *
	 * @var string
	 */
	public static $varreg    = '[a-zA-Z0-9_\.\[\]\$]';

	/**
	 * RegExp used to check a vriable name that also contains modifiers
	 *
	 * @var string
	 */
	public static $varreg2   = '[a-zA-Z0-9_\|:\.\$-]';
	//~ public static $varreg2   = '[^\{\}.]';

	/**
	 * RegExp used to check a file name
	 *
	 * @var string
	 */
	public static $filereg   = '[a-zA-Z0-9_\.-]';

	/**
	 * Special template tags
	 *
	 * @var array
	 */
	public static $specials  = array('{l}', '{r}');

	/**
	 * Replacements for the special template tags
	 *
	 * @var array
	 */
	public static $rspecials = array('{', '}');

	/**
	 * Shortcut method that combines self::vars() and self::logicAndTags()
	 *
	 * @param string $tpl the template string to process
	 * @return string processed template string
	 */
	public static function analyseAndTransform($tpl) {
		return tpltools::vars( tpltools::logicAndTags($tpl) );
	}

	/**
	 * Reads the parameters in an expression
	 * format: {fn p=v ...}
	 *
	 * @param string $exp the expression to analyse
	 * @return array an array containing the parameters of the expression
	 */
	public static function readParams($expr) {
		$p = array('function' => '', 'params' => array());
		$l = strlen($expr);
		$i = 1;
		$t = array(' ', '}', '"', "'");

		//gets fn name
		do {
			$p['function'] .= $expr[$i++];
		} while($expr[$i] != ' ' && $i<$l-1);

		//gets params
		$crtparam = '';
		$crtvalue = '';
		$valwithsimplequote = false;
		$valwithdoublequote = false;
		$is_param = true;
		$is_val = false;
		for($i; $i<$l-1; $i++) {
			if(!$is_val && $expr[$i] == ' ');

			elseif(!$is_val && $expr[$i] == '=') {
				$is_param = false;
				$is_val = true;
				$valwithsimplequote = $expr[$i+1] == "'";
				$valwithdoublequote = $expr[$i+1] == '"';

				if($valwithsimplequote || $valwithdoublequote)
					$i++;
			}

			elseif($is_param) {
				$crtparam .= $expr[$i];
			}

			elseif($is_val) {
				if($expr[$i] == ' ' && ($valwithsimplequote || $valwithdoublequote))
					$crtvalue .= $expr[$i];

				elseif($expr[$i] == '"' && (($valwithsimplequote || $expr[$i-1] == '\\') || (!$valwithsimplequote &&  !$valwithdoublequote)))
					$crtvalue .= $expr[$i];

				elseif($expr[$i] == "'" && (($valwithdoublequote || $expr[$i-1] == '\\') || (!$valwithsimplequote &&  !$valwithdoublequote)))
					$crtvalue .= $expr[$i];

				elseif(!in_array($expr[$i], $t))
					$crtvalue .= $expr[$i];

				else {
					$p['params'][$crtparam] = $crtvalue;
					$is_param = true;
					$is_val = false;
					$crtparam = '';
					$crtvalue = '';
				}
			}
		}
		if($crtparam != '' && $crtvalue != '')
			$p['params'][$crtparam] = $crtvalue;

		return $p;
	}

	/**
	 * Replaces logic template tags by PHP logic and control structures
	 * Also replaces the user functions
	 *
	 * @param string $tpl the template string to process
	 * @return string processed template string
	 */
	public static function logicAndTags($tpl) {
		preg_match_all('#{(.*)}#U', $tpl, $tags);
		foreach($tags[1] as $k => $tag) {
			$a = explode(' ', $tag);
			$t = $a[0];
			$a[0] = '';
			$a = implode(' ', $a);
			$r = false;

			if    ($t == 'if'      ) $r = '<?php if(' . $a . '): ?>';
			elseif($t == 'elseif'  ) $r = '<?php elseif(' . $a . '): ?>';
			elseif($t == 'else'    ) $r = '<?php else: ?>';
			elseif($t == '/if'     ) $r = '<?php endif; ?>';
			elseif($t == 'foreach' ) {
				$t = explode(' as ', $a);
				if(sizeof($t) == 1)
					$t = explode(' AS ', $a);
				$r = '<?php if(isset('.$t[0].') && is_array('.$t[0].') && sizeof('.$t[0].') > 0): foreach(' . $a . '): ?>';
			}
			elseif($t == 'foreachelse') $r = '<?php endforeach; ?><?php else: ?><?php foreach(array("1") as $foreachelse): ?>';
			elseif($t == '/foreach') $r = '<?php endforeach; ?><?php endif; ?>';
			elseif($t == 'while'   ) $r = '<?php while(' . $a . '): ?>';
			elseif($t == '/while'  ) $r = '<?php endwhile; ?>';
			elseif($t == '/for'    ) $r = '<?php endfor; ?>';
			elseif($t == 'for'     ) {
				$args = tpltools::readParams('{' . $tag . '}');
				$args = $args['params'];
				$args['step'] = isset($args['step']) ? $args['step'] : (isset($args['to']) ? 1 : -1);
				$r = '<?php for('.$args['var'].'='.$args['from'].';'.$args['var'].(isset($args['to']) ? '<='.$args['to'] : '>='.$args['downto']).';'.$args['var'].'+='.$args['step'].'): ?>';
			}
			elseif(preg_match('#^('.tpltools::$filereg.'+)$#', $t)) {
				if(in_array($tags[0][$k], tpltools::$specials))
					$r = str_replace(tpltools::$specials, tpltools::$rspecials, $tags[0][$k]);
				else {
					$p = tpltools::readparams($tags[0][$k]);
					$r = '<?php echo tplfunction_'.$p['function'].'(array(';
					$t = array();
					foreach($p['params'] as $param => $val)
						$t[] = '"'.$param.'"=>'.($val[0] == '$' ? $val : '"'.$val.'"');
					$r .= implode(',', $t);
					$r .= '), $_tpl); ?>';
				}
			}

			if($r !== false)
				$tpl = str_replace($tags[0][$k], $r, $tpl);
		}

		return $tpl;
	}

	/**
	 * Modifies the given variable according to the given modifier
	 *
	 * @param string $var the variable to modify
	 * @param string $modifier the modifier to use
	 * @param string $default default value to use with some modifiers
	 * @return string the modified variable
	 */
	public static function modifier($var, $modifier, $default='') {
		switch($modifier) {
			case 'capitalize':
				$str = explode(' ', $var);
				foreach($str as $k => $v)
					$str[$k] = ucfirst($v);
				return implode(' ', $str);
			case 'lower':
				return strtolower($var);
			case 'count':
				return sizeof($var);
			case 'nl2br':
				return nl2br($var);
			case 'default':
				if(isset($var) && !empty($var))
					return $var;
				else {
					if(in_array(substr($default, 0, 1), array('"', "'")))
						return substr($default, 1, strlen($default) - 2);
					else
						return $default;
				}
			default:
				return $var;
		}
	}

	/**
	 * Modifies a variable and check for template tags in it
	 *
	 * @param string $var the variable to process
	 * @param string $modifiers the modifiers to apply to the variable
	 * @param string $returnMethod the return method to use (echo|return)
	 * @param object $_tpl the current template object
	 * @return mixed a string if returnMethod=='echo', void else
	 * @see self::modifier()
	 */
	public static function parseVar($var, $modifiers, $returnMethod, $_tpl) {
		$modifiers = explode('|', $modifiers);
		foreach($modifiers as $modifier) {
			$t = explode(':', $modifier);
			$var = tpltools::modifier($var, $t[0], isset($t[1]) ? $t[1] : '');
		}

		if(is_string($var) && strpos($var, '{') !== false) //allows the var to be considered as a template
			$var = eval('?>' . tpltools::analyseAndTransform($var) );

		if($returnMethod == 'echo') echo $var;
		else                        return $var;
	}

	/**
	 * Processes the variables in a template string
	 *
	 * @param string $tpl the template string to process
	 * @return string processed template string
	 */
	public static function vars($tpl) {
		//echo vars and vars modifiers
		$tpl = preg_replace('#{\$('.tpltools::$varreg.'+)(\|?)('.tpltools::$varreg2.'*)}#', '<?php tpltools::parseVar(@$$1, \'$3\', "echo", $_tpl); ?>', $tpl);
		$tpl = preg_replace('#\$('.tpltools::$varreg.'+)\|('.tpltools::$varreg2.'*)#', 'tpltools::parseVar(@$$1, \'$2\', "return", $_tpl)', $tpl);

		//vars transformation
		preg_match_all('#\$('.tpltools::$varreg.'+)#', $tpl, $matches, PREG_OFFSET_CAPTURE);
		$offset = 0;
		foreach($matches[1] as $match => $var) {
			$var[0] = preg_replace('#([^$][.]*)\$([.]*)#', '$2', $var[0]);
			if($var[0] != '_tpl') {
				$r = preg_split('#(\[|\]|\.)#', $var[0]);
				foreach($r as $k => $v) {
					if(substr($v, 0, 1) != '$') {
						if(is_numeric($v))
							$r[$k] = $v;
						else
							$r[$k] = '"'.$v.'"';
					}
				}
				$replacement = '_tpl->vars['.implode('][', $r).']';
			}
			else
				$replacement = '_tpl';
			$tpl = substr_replace($tpl, $replacement, $matches[1][$match][1]+$offset, strlen($var[0]));
			$offset += strlen($replacement)-strlen($var[0]);
		}

		return $tpl;
	}

}

?>
