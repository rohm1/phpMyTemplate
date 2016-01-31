<?php
/*
See class.tpl.php for license infos
*/

/**
 * Class tpltools
 * Static class providing tools to the template engine
 */
class tpltools
{
    /**
     * RegExp used to check a variable name
     *
     * @var string
     */
    public static $simplevarreg = '[a-zA-Z0-9_\.]';

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
    public static function analyseAndTransform($tpl)
    {
        return tpltools::vars( tpltools::logicAndTags($tpl) );
    }

    /**
     * Reads the parameters in an expression
     * format: {fn p=v ...}
     *
     * @param string $exp the expression to analyse
     * @return array an array containing the parameters of the expression
     */
    public static function readParams($expr)
    {
        $p = array('function' => '', 'params' => array());
        $l = strlen($expr);
        $i = 1;
        $t = array(' ', '}', '"', "'");

        //gets fn name
        do {
            $p['function'] .= $expr[$i++];
        } while ($expr[$i] != ' ' && $i<$l-1);

        //gets params
        $crtparam = '';
        $crtvalue = '';
        $valwithsimplequote = false;
        $valwithdoublequote = false;
        $is_param = true;
        $is_val = false;
        for ($i; $i<$l-1; $i++) {
            if (!$is_val && $expr[$i] == ' ') {
                continue;

            } elseif (!$is_val && $expr[$i] == '=') {
                $is_param = false;
                $is_val = true;
                $valwithsimplequote = $expr[$i+1] == "'";
                $valwithdoublequote = $expr[$i+1] == '"';

                if($valwithsimplequote || $valwithdoublequote) {
                    $i++;
                }

            } elseif ($is_param) {
                $crtparam .= $expr[$i];

            } elseif ($is_val) {
                if ($expr[$i] == ' ' && ($valwithsimplequote || $valwithdoublequote)) {
                    $crtvalue .= $expr[$i];

                } elseif ($expr[$i] == '"' && (($valwithsimplequote || $expr[$i-1] == '\\') || (!$valwithsimplequote &&  !$valwithdoublequote))) {
                    $crtvalue .= $expr[$i];

                } elseif ($expr[$i] == "'" && (($valwithdoublequote || $expr[$i-1] == '\\') || (!$valwithsimplequote &&  !$valwithdoublequote))) {
                    $crtvalue .= $expr[$i];

                } elseif (!in_array($expr[$i], $t)) {
                    $crtvalue .= $expr[$i];

                } else {
                    $p['params'][$crtparam] = $crtvalue;
                    $is_param = true;
                    $is_val = false;
                    $crtparam = '';
                    $crtvalue = '';
                }
            }
        }

        if ($crtparam != '' && $crtvalue != '') {
            $p['params'][$crtparam] = $crtvalue;
        }

        return $p;
    }

    /**
     * Replaces template logic tags by PHP logic and control structures
     * Also replaces the user functions
     *
     * @param string $tpl the template string to process
     * @return string processed template string
     */
    public static function logicAndTags($tpl)
    {
        preg_match_all('#{(.*)}#U', $tpl, $tags);
        foreach ($tags[1] as $k => $tag) {
            $a = explode(' ', $tag);
            $t = $a[0];
            $a[0] = '';
            $a = implode(' ', $a);
            $r = false;

            if     ($t == 'if'      ) $r = '<?php if (' . $a . '): ?>';
            elseif ($t == 'elseif'  ) $r = '<?php elseif (' . $a . '): ?>';
            elseif ($t == 'else'    ) $r = '<?php else: ?>';
            elseif ($t == '/if'     ) $r = '<?php endif; ?>';
            elseif ($t == 'foreach' ) {
                $t = preg_split('/ as /i', $a);
                $t[0] = trim($t[0]);
                $t[1] = trim($t[1]);
                $foreach_vars = explode('=>', $t[1]);
                $foreach_id = uniqid();
                $r = '<?php if (isset('.$t[0].') && is_array('.$t[0].') && count('.$t[0].') > 0): ' . PHP_EOL .
                    '$_tpl->assign("_keys_' . $foreach_id . '", array_keys(' . $t[0] . ')); ' . PHP_EOL .
                    '$_tpl->assign("_length_' . $foreach_id . '", count(' . $t[0] .')); ' . PHP_EOL .
                    '$_tpl->assign("_loop_' . $foreach_id . '", 0); ' . PHP_EOL .
                    'while ($_loop_' . $foreach_id .' < $_length_' . $foreach_id . '): ' . PHP_EOL .
                    '$_tpl->assign("' . substr(trim($foreach_vars[(count($foreach_vars)+1)%2]), 1) . '", ' . $t[0] . '[$_keys_' . $foreach_id .'[$_loop_' . $foreach_id .']]); ' . PHP_EOL .
                    (count($foreach_vars) == 2 ? '$_tpl->assign("' . substr(trim($foreach_vars[0]), 1) . '", $_keys_' . $foreach_id .'[$_loop_' . $foreach_id .']); ' : '') . PHP_EOL .
                    '$_tpl->assign("_loop_' . $foreach_id . '", $_loop_' . $foreach_id . ' + 1); ' .
                    '?>';
            }
            elseif ($t == 'foreachelse') {
                $foreachelse_id = uniqid();
                $r = '<?php endwhile; else:' . PHP_EOL .
                    '$_tpl->assign("_foreachelse_' . $foreachelse_id . '", true); ' . PHP_EOL .
                    'while ($_foreachelse_' . $foreachelse_id . '):' . PHP_EOL .
                    '$_tpl->assign("_foreachelse_' . $foreachelse_id . '", false); ' .
                    '?>';
            } elseif ($t == '/foreach') $r = '<?php endwhile; endif; ?>';
            elseif ($t == 'while'   ) $r = '<?php while(' . $a . '): ?>';
            elseif ($t == '/while'  ) $r = '<?php endwhile; ?>';
            elseif ($t == 'for'     ) {
                $args = tpltools::readParams('{' . $tag . '}');
                $args = $args['params'];
                $args['step'] = isset($args['step']) ? $args['step'] : (isset($args['to']) ? 1 : -1);
                $for_id = uniqid();

                $r = '<?php' . PHP_EOL .
                    '$_tpl->assign("_loop_' . $for_id . '", ' . $args['from'] . '); ' . PHP_EOL .
                    'while ($_loop_' . $for_id . (isset($args['to']) ? ' <= '.$args['to'] : ' >= ' .$args['downto']) . '): ' . PHP_EOL .
                    '$_tpl->assign("' . $args['var'] . '", $_loop_' . $for_id .'); ' . PHP_EOL .
                    '$_tpl->assign("_loop_' . $for_id . '", $_loop_' . $for_id . ' + ' . $args['step'] . '); ' .
                    '?>';
            }
            elseif ($t == '/for'    ) $r = '<?php endwhile; ?>';
            elseif (preg_match('#^('.tpltools::$filereg.'+)$#', $t)) {
                if (in_array($tags[0][$k], tpltools::$specials)) {
                    $r = str_replace(tpltools::$specials, tpltools::$rspecials, $tags[0][$k]);
                } else {
                    $p = tpltools::readparams($tags[0][$k]);
                    $r = '<?php echo tplfunction_'.$p['function'].'(array(';
                    $t = [];
                    foreach ($p['params'] as $param => $val) {
                        $t[] = '"'.$param.'"=>'.($val != '' && $val[0] == '$' ? $val : '"'.$val.'"');
                    }
                    $r .= implode(',', $t);
                    $r .= '), $_tpl); ?>';
                }
            }

            if ($r !== false) {
                $tpl = str_replace($tags[0][$k], $r, $tpl);
            }
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
    public static function modifier($var, $modifier, $default='')
    {
        switch ($modifier) {
            case 'capitalize':
                return ucwords($var);
            case 'lower':
                return strtolower($var);
            case 'count':
                return sizeof($var);
            case 'nl2br':
                return nl2br($var);
            case 'ceil':
                return ceil($var);
            case 'floor':
                return floor($var);
            case 'round':
                return round($var);
            case 'default':
                if (isset($var) && !empty($var)) {
                    return $var;
                } else {
                    if (in_array(substr($default, 0, 1), array('"', "'"))) {
                        return substr($default, 1, strlen($default) - 2);
                    } else {
                        return $default;
                    }
                }
            default:
                return $var;
        }
    }

    /**
     * Gets a variable, parses it if necessary, and returns it
     *
     * @param string $var the variable to process
     * @param string $modifiers the modifiers to apply to the variable
     * @param string $returnMethod the return method to use (echo|return)
     * @return mixed a string if returnMethod=='echo', void else
     * @see self::modifier()
     */
    public static function getVar($var, $modifiers = '', $returnMethod = 'return')
    {
        $parse = isset($var['_parse_']) && $var['_parse_'];
        $var = is_array($var) && array_key_exists('_value_', $var) ? $var['_value_'] : $var;

        $modifiers = explode('|', $modifiers);
        foreach ($modifiers as $modifier) {
            $t = explode(':', $modifier);
            $var = tpltools::modifier($var, $t[0], isset($t[1]) ? $t[1] : '');
        }

        if (is_string($var) && $parse) {
            $var = eval('?>' . tpltools::analyseAndTransform($var) );
        }

        if ($returnMethod == 'echo') {
            echo $var;
        } elseif ($returnMethod == 'return') {
            return $var;
        }
    }

    /**
     * Processes the variables in a template string
     *
     * @param string $tpl the template string to process
     * @return string processed template string
     */
    public static function vars($tpl)
    {
        //echo vars and vars modifiers
        $tpl = preg_replace('#{\$('.tpltools::$varreg.'+)(\|?)('.tpltools::$varreg2.'*)}#', '<?php tpltools::getVar(@$$1, \'$3\', "echo"); ?>', $tpl);
        $tpl = preg_replace('#\$('.tpltools::$varreg.'+)\|('.tpltools::$varreg2.'*)#', 'tpltools::getVar(@$$1, \'$2\', "return")', $tpl);

        //vars transformation
        preg_match_all('#\$('.tpltools::$varreg.'+)#', $tpl, $matches, PREG_OFFSET_CAPTURE);
        $offset = 0;
        foreach ($matches[1] as $match => $var) {
            if ($var[0] != '_tpl') {
                $replacement = '$' . $var[0];

                preg_match_all('#\$('.tpltools::$simplevarreg.'+)#', $replacement, $varmatches, PREG_OFFSET_CAPTURE);
                $varoffset = 0;
                foreach ($varmatches[1] as $varmatch => $varval) {
                    $r = explode('.', $varval[0]);
                    $_r = [];
                    foreach ($r as $k => $v) {
                        if (is_numeric($v)) {
                            $_r[] = $v;
                        } else {
                            $_r[] = '"'.$v.'"';
                        }
                    }
                    $varreplacement = 'tpltools::getVar(@$_tpl->vars['.implode('][', $_r).'], "", "return")';

                    $replacement = substr_replace(
                        $replacement,
                        $varreplacement,
                        $varmatches[1][$varmatch][1] + $varoffset - 1,
                        strlen($varval[0]) + 1
                    );
                    $varoffset += strlen($varreplacement) - strlen($varval[0]) - 1;
                }

                $replacement = preg_replace('/\]\[/', ']["_value_"][', $replacement, 1);

                $tpl = substr_replace($tpl, $replacement, $matches[1][$match][1] + $offset - 1, strlen($var[0]) + 1);
                $offset += strlen($replacement) - strlen($var[0]) - 1;
            }
        }

        $tpl = preg_replace('/(isset|empty)\s*\(\s*tpltools::getVar/', '(tpltools::_$1', $tpl);

        return $tpl;
    }

    /**
     * Determine if a variable is set and is not NULL
     *
     * @param mixes $var
     * @see http://www.php.net/manual/en/function.isset.php
     */
    public static function _isset($var)
    {
        $var = is_array($var) && array_key_exists('_value_', $var) ? $var['_value_'] : $var;
        return isset($var);
    }

    /**
     * Determine whether a variable is empty
     *
     * @param mixes $var
     * @see http://www.php.net/manual/en/function.empty.php
     */
    public static function _empty($var)
    {
        $var = is_array($var) && array_key_exists('_value_', $var) ? $var['_value_'] : $var;
        return !isset($var) || $var == false;
    }

}
