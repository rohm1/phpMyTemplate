<?php

class tpltools {

	public static $varreg    = '[a-zA-Z0-9_\.\[\]\$]';
	public static $varreg2   = '[a-zA-Z0-9_\|:\.\$-]';
	public static $filereg   = '[a-zA-Z0-9_\.-]';
	public static $specials  = array('{l}', '{r}');
	public static $rspecials = array('{', '}');

	public function analyseAndTransform($tpl) {
		return tpltools::vars( tpltools::logicAndTags($tpl) );
	}

	//format: {fn p=v ...}
	public function readParams($expr) {
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

	public function logicAndTags($tpl) {
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

	public function modifier($var, $modifier, $default='') {
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
				if(isset($var))
					return $var;
				else
					return $default;
			default:
				return $var;
		}
	}

	public function parseVar($var, $modifiers, $returnMethod, $_tpl) {

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

	public function vars($tpl) {
		//echo vars and vars modifiers
		$tpl = preg_replace('#{\$('.tpltools::$varreg.'+)(\|)*('.tpltools::$varreg2.'*)}#', '<?php tpltools::parseVar(@$$1, \'$3\', "echo", $_tpl); ?>', $tpl);
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
