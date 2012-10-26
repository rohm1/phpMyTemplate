<?php

class tplcompile {

	private $dep = array();
	private $tpl = "";
	private $template_dir = array();
	private $blocks = array();

	public function __construct($tplFile, $outputFile, $template_dir = '') {

		$this->template_dir = $template_dir != '' ? $template_dir : array(getcwd() . '/templates/');

		$this->addDep($tplFile);

		//gets primary tpl content
		$this->tpl = file_get_contents($tplFile);

		//includes, extends, blocks extractions
		$this->tpl = $this->tplIncludesAndExtends($this->tpl);

		//blocks replacements
		$this->replaceBlocks();

		//raw output
		if(isset($_GET['raw']))
			die($this->tpl);

		//tpl logic tags and user's functions, vars
		$this->tpl = tpltools::analyseAndTransform($this->tpl);

		//write output
		$deps = '<?php' . PHP_EOL;
		$deps .= '$deps = array(' . PHP_EOL;
		$mdCat = "";
		foreach($this->dep as $file => $md5) {
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

	private function addDep($file) {
		$this->dep[$file] = md5(file_get_contents($file));
	}

	private function loadTpl($tplName) {
		if(($tpl = $this->lookForTpl($tplName)) !== false) {
			$this->tpl = file_get_contents($tpl);
			$this->addDep($tpl);
		}
	}

	private function lookForTpl($name) {
		foreach($this->template_dir as $dir) {
			if(file_exists($dir . $name))
				return $dir . $name;
		}
		return false;
	}

	private function searchBlocks($tpl) {
		$r = array();
		$blockStarts = array();
		$blockEnds = array();

		preg_match_all('#{block([[:blank:]]*)name=('.tpltools::$varreg.'+)(.*)}#U', $tpl, $r, PREG_OFFSET_CAPTURE);
		foreach($r[0] as $k => $match) {
			$p = tpltools::readParams($match[0]);
			$blockStarts[] = array($match[1], strlen($match[0]), $p['params']);
		}

		preg_match_all('#{/block}#', $tpl, $r, PREG_OFFSET_CAPTURE);
		foreach($r[0] as $match)
			$blockEnds[] = $match[1];

		$s = sizeof($blockStarts);
		for($i=0 ; $i< $s ; $i++) {
			for($j=$i+1 ; $j < $s ; $j++) {
				if($blockStarts[$j][0] < $blockEnds[$i]) {
					$tmp = $blockEnds[$j];
					$blockEnds[$j] = $blockEnds[$i];
					$blockEnds[$i] = $tmp;
				}
			}
		}

		foreach($blockStarts as $k => $b)
			$this->blocks[$b[2]['name']][] = array(
					'content' => substr($tpl, $b[0]+$b[1], $blockEnds[$k]-$b[0]-$b[1]),
					'command' => substr($tpl, $b[0], $b[1]),
					'params' => $b[2]
					);
	}

	private function replaceBlocks() {
		$finalBlocks = array();
		foreach($this->blocks as $blockVersions) {
			$append = 'none';
			$content = '';
			foreach($blockVersions as $k => $v) {
				switch($append) {
					case 'none'   : $content = $v['content']; break;
					case 'replace': $content = $content; break;
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

	private function fillBlocks($finalBlocks) {
		$this->blocks = array();
		$this->searchBlocks($this->tpl);
		foreach($this->blocks as $block)
			$this->tpl = str_replace($block[0]['command'] . $block[0]['content'] . '{/block}', $finalBlocks[ $block[0]['params']['name'] ], $this->tpl);

		if(sizeof($this->blocks) != 0)
			$this->fillBlocks($finalBlocks);
	}

	private function tplIncludesAndExtends($tpl) {
		preg_match_all('#{include([[:blank:]]+)file=('.tpltools::$filereg.'+)}#', $tpl, $includes);
		foreach($includes[2] as $k => $include) {
			$file = $this->lookForTpl($include);
			$this->addDep($file);
			$c = $this->tplIncludesAndExtends( file_get_contents($file) );
			$tpl = str_replace($includes[0][$k], $c, $tpl);
		}

		$this->searchBlocks($tpl);

		preg_match_all('#{extends([[:blank:]]+)file=('.tpltools::$filereg.'+)}#', $tpl, $extends);
		foreach($extends[2] as $k => $extend) {
			$file = $this->lookForTpl($extend);
			$this->addDep($file);
			$tpl = $this->tplIncludesAndExtends( file_get_contents($file) );
		}

		return $tpl;
	}

}
?>
