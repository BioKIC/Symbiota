<?php
include_once($SERVER_ROOT . "/classes/Database.php");

/*This is 
 * This is a static class used to make santizing variables "in" to the databse and "out" to the client webpage easy to use, compact, and secure
 */
class Sanitize {
	public static function out(Mixed $val): Mixed {
		if(is_array($val)) {
			return array_map(fn ($v) => self::out($v), $val);
		} else if(is_bool($val) || is_numeric($val) || is_string($val)) {
			return htmlspecialchars($val, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
		} else {
		  return '';
		}
	}

	public static function in(Mixed $val): Mixed {
		if(is_array($val)) {
			$arr = [];
			foreach ($val as $key => $array_value) {
				$arr[self::in($key)] = self::in($array_value);
			}
			return $arr;
		} else if (is_numeric($val)) {
			return filter_var($val, FILTER_SANITIZE_NUMBER_INT);
		} else if(is_string($val)){
			$str = trim($val);
			if(!$str) return null;

			return mysqli_real_escape_string(
				Database::connect('readonly'), 
				preg_replace('/\s\s+/', ' ', $str)
			);
		} else if(is_bool($val)) {
			return $val;
		} else {
			return null;
		}
	}
}
?>
