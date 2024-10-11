<?php
/*
 * This Trait is used for classes that do not extend Manager;
 * Note this will make a new connection per class that uses this but mysqli does pooling
 * Behind the scenes so there should be too much to worrya about and a manager class
 * would make a connection per instance which was usually one so it isn't any less 
 * effecient just more convient.
 */
include_once($SERVER_ROOT.'/config/dbconnection.php');

class Database {
	protected static $conns = [];
    /**
     * @param string $conn_type Type of db connection either 'write' or 'read'
     * @param bool $override_conn Flag if you want to for reset the connection
	 * @return mysqli
     */
    public static function connect(string $conn_type, bool $override_conn = false): Object {
		if(!isset(self::$conns[$conn_type]) || !self::$conns[$conn_type] || $override_conn) {
			$conn = MySQLiConnectionFactory::getCon($conn_type);
			self::$conns[$conn_type] = $conn; 
			return $conn;
		} else {
			return self::$conns[$conn_type];
		}
	}
}
?>
