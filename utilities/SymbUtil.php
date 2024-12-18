<?php
class SymbUtil {
	/*
	 * This function is a wrapper for mysqli_execute_query. Not all Symbiota portals can update version so this an effort to fix backward compat issues 
	 *
	 * @param mysqli $conn
	 * @param string $sql 
	 * @param string $params
	 */
	static function execute_query(mysqli $conn, string $sql, array $params) {
		//This is supported from 4 to 8
		$version = phpversion();
		[$major, $minor, $patch] = explode('.', $version);

		if($major >= 8 && $minor >= 2) {
			return mysqli_execute_query($conn, $sql, $params);
		} else {
			$bind_params_str = '';
			foreach($params as $param) {
				//Could just bind string instead?
				if(gettype($param) === 'string') {
					$bind_params_str .= 's';
				} else {
					$bind_params_str .= 'i';
				}
			}
			$stmt = $conn->prepare($sql);
			$stmt->bind_param($bind_params_str,...$params);
			$stmt->execute();
			return $stmt->get_result();
		}
	}
}
?>
