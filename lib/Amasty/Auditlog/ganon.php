<?php
/**
 * @author Niels A.D.
 * @package Ganon
 * @link http://code.google.com/p/ganon/
 * @license http://dev.perl.org/licenses/artistic.html Artistic License
 */

include_once 'HtmlParserHTML5.php';
include_once 'HTMLFormatter.php';

/**
 * Returns HTML DOM from string
 * @param string $str
 * @param bool $return_root Return root node or return parser object
 * @return HtmlParserHTML5|HtmlNode
 */
function str_get_dom($str, $return_root = true) {
	$a = new HtmlParserHTML5($str);
	return (($return_root) ? $a->root : $a);
}

/**
 * Returns HTML DOM from file/website
 * @param string $str
 * @param bool $return_root Return root node or return parser object
 * @param bool $use_include_path Use include path search in file_get_contents
 * @param resource $context Context resource used in file_get_contents (PHP >= 5.0.0)
 * @return HtmlParserHTML5|HtmlNode
 */
function file_get_dom($file, $return_root = true, $use_include_path = false, $context = null) {
	if (version_compare(PHP_VERSION, '5.0.0', '>='))
		$f = file_get_contents($file, $use_include_path, $context);
	else {
		if ($context !== null)
			trigger_error('Context parameter not supported in this PHP version');
		$f = file_get_contents($file, $use_include_path);
	}
	
	return (($f === false) ? false : str_get_dom($f, $return_root));
}

/**
 * Format/beautify DOM
 * @param HTML_Node $root
 * @param array $options Extra formatting options {@link HTMLFormatter::$options}
 * @return bool
 */
function dom_format(&$root, $options = array()) {
	$formatter = new HTMLFormatter($options);
	return $formatter->format($root);
}

if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	/**
	 * PHP alternative to str_split, for backwards compatibility
	 * @param string $string
	 * @return string
	 */
	function str_split($string) {
		$res = array();
		$size = strlen($string);
		for ($i = 0; $i < $size; $i++) {
			$res[] = $string[$i];
		}
		
		return $res;
	}
}

if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	/**
	 * PHP alternative to array_fill_keys, for backwards compatibility
	 * @param array $keys
	 * @param mixed $value
	 * @return array
	 */
	function array_fill_keys($keys, $value) {
		$res = array();
		foreach($keys as $k) {
			$res[$k] = $value;
		}
		
		return $res;
	}
}
