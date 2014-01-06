<?php
/*
See class.tpl.php for license infos
*/

/**
 * Class tplcompile
 * It compiles a template file in PHP code and caches the result.
 */
class tplcompile {
    /**
     * The list of files this template depends of
     *
     * @var array
     */
    private $dep = array();

    /**
     * The template result
     *
     * @var string
     */
    private $tpl = '';

    /**
     * List of directories where to look for the template files
     *
     * @var array
     */
    private $template_dir = array();

    /**
     * List of the blocks of the template
     *
     * @var array
     */
    private $blocks = array();

    /**
     * Constructor
     *
     * @param string $tplFile absolute path to the template source
     * @param string $outputFile absolute path to the resulting compiled file
     * @param mixed $template_dir list of directories where to look for the template files
     * @return void
     */
    public function __construct($tplFile, $outputFile, $template_dir = '')
    {
        $this->template_dir = $template_dir != '' ? $template_dir : array(getcwd() . '/templates/');

        $this->addDep($tplFile);

        //gets primary tpl content
        $this->tpl = file_get_contents($tplFile);

        //includes, extends, blocks extractions
        $this->tpl = $this->tplIncludesAndExtends($this->tpl);

        //blocks replacements
        $this->replaceBlocks();

        //tpl logic tags and user's functions, vars
        $this->tpl = tpltools::analyseAndTransform($this->tpl);

        //raw output
        if (isset($_GET['tplraw'])) {
            die($this->tpl);
        }

        //write output
        $deps = '<?php' . PHP_EOL;
        $deps .= '$deps = array(' . PHP_EOL;
        $mdCat = "";
        foreach ($this->dep as $file => $md5) {
            $deps .= '\''.$file.'\' => \'' . $md5 . '\',' . PHP_EOL;
            $mdCat .= $md5;
        }
        $deps .= ');' . PHP_EOL;
        $deps .= '?>' . PHP_EOL;

        $fname = 'tpl' . md5($mdCat);
        $function = '<?php' . PHP_EOL;
        $function .= '$function = \'' . $fname . '\';' . PHP_EOL;
        $function .= '?>' . PHP_EOL;

        $f = fopen($outputFile, 'w');
        fwrite($f, $deps .
            $function .
            '<?php function ' . $fname . '($_tpl) { ?>' . PHP_EOL .
                $this->tpl . PHP_EOL .
            '<?php } ?>' . PHP_EOL
            );
        fclose($f);
    }

    /**
     * Adds files this template depends of to self::$dep
     *
     * @param string $file template file absolute path
     * @return void
     */
    private function addDep($file)
    {
        $this->dep[$file] = md5(file_get_contents($file));
    }

    /**
     * Retrieves a template file and gets its content
     *
     * @param string $tplName the absolute path to the template file to load
     * @return void
     */
    private function loadTpl($tplName)
    {
        if (($tpl = $this->lookForTpl($tplName)) !== false) {
            $this->tpl = file_get_contents($tpl);
            $this->addDep($tpl);
        }
    }

    /**
     * Checks if a template file exists
     *
     * @param string $name the template file name
     * @return mixed absolute path to the file if found, false else
     */
    private function lookForTpl($name)
    {
        foreach ($this->template_dir as $dir) {
            if (file_exists($dir . $name)) {
                return $dir . $name;
            }
        }

        return false;
    }

    /**
     * Analyses a template file content (string) and extracts the blocks
     *
     * @param string $tpl the template in which to look for blocks
     * @return void
     * @see self::$blocks
     */
    private function searchBlocks($tpl)
    {
        $r = array();
        $blockStarts = array();
        $blockEnds = array();

        preg_match_all('#{block([[:blank:]]*)name=('.tpltools::$varreg.'+)(.*)}#U', $tpl, $r, PREG_OFFSET_CAPTURE);
        foreach ($r[0] as $k => $match) {
            $p = tpltools::readParams($match[0]);
            $blockStarts[] = array($match[1], strlen($match[0]), $p['params']);
        }

        preg_match_all('#{/block}#', $tpl, $r, PREG_OFFSET_CAPTURE);
        foreach ($r[0] as $match) {
            $blockEnds[] = $match[1];
        }

        $s = sizeof($blockStarts);
        for ($i=0 ; $i< $s ; $i++) {
            for ($j=$i+1 ; $j < $s ; $j++) {
                if ($blockStarts[$j][0] < $blockEnds[$i]) {
                    $tmp = $blockEnds[$j];
                    $blockEnds[$j] = $blockEnds[$i];
                    $blockEnds[$i] = $tmp;
                }
            }
        }

        foreach ($blockStarts as $k => $b) {
            $this->blocks[$b[2]['name']][] = array(
                'content' => substr($tpl, $b[0]+$b[1], $blockEnds[$k]-$b[0]-$b[1]),
                'command' => substr($tpl, $b[0], $b[1]),
                'params' => $b[2]
            );
        }
    }

    /**
     * Computes the resulting blocks after loading all templates
     *
     * @return void
     */
    private function replaceBlocks()
    {
        $finalBlocks = array();
        foreach ($this->blocks as $blockVersions) {
            $append = 'none';
            $content = '';
            foreach ($blockVersions as $k => $v) {
                switch ($append) {
                    case 'none'   : $content = $v['content']; break;
                    case 'replace': break;
                    case 'append' : $content = $v['content'] . $content; break;
                    case 'prepend': $content .= $v['content']; break;
                    default: break;
                }

                $append = isset($v['params']['append']) ? $v['params']['append'] : 'replace';
            }

            $finalBlocks[$v['params']['name']] = $content;
        }

        $this->fillBlocks($finalBlocks);

        unset($this->blocks);
    }

    /**
     * Fills the blocks with their final values
     *
     * @param array $finalBlocks a list of blocks to fill
     * @return void
     * @see self::searchBlocks()
     */
    private function fillBlocks($finalBlocks)
    {
        $this->blocks = array();
        $this->searchBlocks($this->tpl);
        foreach ($this->blocks as $block) {
            $this->tpl = str_replace($block[0]['command'] . $block[0]['content'] . '{/block}', $finalBlocks[ $block[0]['params']['name'] ], $this->tpl);
        }

        if (sizeof($this->blocks) != 0) {
            $this->fillBlocks($finalBlocks);
        }
    }

    /**
     * Makes the appropriates extends and includes to the given template string
     *
     * @param string $tpl the template string to analyse
     * @return void
     */
    private function tplIncludesAndExtends($tpl)
    {
        preg_match_all('#{include([[:blank:]]+)file=('.tpltools::$filereg.'+)}#', $tpl, $includes);
        foreach ($includes[2] as $k => $include) {
            $file = $this->lookForTpl($include);
            $this->addDep($file);
            $c = $this->tplIncludesAndExtends( file_get_contents($file) );
            $tpl = str_replace($includes[0][$k], $c, $tpl);
        }

        $this->searchBlocks($tpl);

        preg_match_all('#{extends([[:blank:]]+)file=('.tpltools::$filereg.'+)}#', $tpl, $extends);
        foreach ($extends[2] as $k => $extend) {
            $file = $this->lookForTpl($extend);
            $this->addDep($file);
            $tpl = $this->tplIncludesAndExtends( file_get_contents($file) );
        }

        return $tpl;
    }

}
