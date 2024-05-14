<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$CHARSET);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	</head>

	<body>
    <h1 class="page-heading">Character Encoding Cleaner</h1>
		<div>
			<b>READ ME:</b> This page is for cleaning central database tabels that may contain mixed latin and UTF-8 character sets.
			This module will convert mixed character sets to UTF-8. If you want to convert from UTF-8 to another character set,
			you will need to modify the code.
		</div>
		<?php

		$cleanManager = new characterEnclodeCleaner();
		//$cleanManager->cleanOccurrences(1,1,0,100000);
		//$cleanManager->cleanDeterminations(1,1,224,2000);
		//$cleanManager->cleanTaxa(1,1,486080,100000);

		?>
	</body>
</html>
<?php

class characterEnclodeCleaner{

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon('write');
		set_time_limit(1800);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function cleanOccurrences($preview = 1, $fix = 0, $startOccid = 0, $limit = 100000){
		$sql = 'SELECT * '.
			'FROM omoccurrences ';
		if($startOccid) $sql .= 'WHERE occid > '.$startOccid.' ';
		$sql .= 'ORDER BY occid ';
		if($limit) $sql .= 'LIMIT '.$limit;
		//echo $sql;
		$excludeFields = array('occid','dbpk','collid','tidinterpreted','eventdate','day','month','year','startdayofyear','enddayofyear',
			'modified','datelastmodified','decimallatitude','decimallongitude','coordinateuncertaintyinmeters','footprintwkt',
			'coordinateprecision','minimumelevationinmeters','maximumelevationinmeters','observeruid','processingstatus','duplicatequantity',
			'dateentered');
		$rs = $this->conn->query($sql);
		echo '<ol>';
		while($r = $rs->fetch_assoc()){
			$rActive = array_change_key_case($r);
			$occid = $rActive['occid'];
			echo '<li><b>occid: </b>'.$occid.': ';
			$setArr = array();
			$problem = false;

			foreach($rActive as $k => $v){
				if($v && !in_array($k, $excludeFields) && !is_numeric($v)){
					$vCleaned = $this->cleanInStr($v);
					$toUtf8 = Encoding::toUTF8($vCleaned);
					$fixUtf8 = Encoding::fixUTF8($vCleaned);
					if($fix && $fixUtf8 != $vCleaned){
						$problem = true;
					}
					if(!$fix && $toUtf8 != $vCleaned){
						$problem = true;
					}
					if($preview){
						echo '<div style="margin-left:10px;">';
						echo '<b>'.$k.':</b>: '.$v.'; ';
						echo '<b>toUTF8 method:</b> '.$toUtf8.'; ';
						echo '<b>fixUTF8 method:</b> '.$fixUtf8;
						echo '</div>';
					}
					if($fix){
						$setArr[] = $k.' = "'.$fixUtf8.'"';
					}
					else{
						$setArr[] = $k.' = "'.$toUtf8.'"';
					}
				}
			}
			if($setArr){
				$sqlFix = 'UPDATE omoccurrences SET '.implode(',',$setArr).' WHERE occid = '.$occid;
				if($problem){
					echo '<div style="margin-left:10px;"><b>PROBLEM:</b>'.$sqlFix.'</div>';
					if(!$preview){
						if(!$this->conn->query($sqlFix)){
							echo '<div style="margin-left:10px;">ERROR: '.$this->conn->error.'; SQL: '.$sqlFix.'</div>';
						}
					}
				}
			}
			echo '</li>';
		}
		$rs->free();
		echo '</ol>';
	}

	public function cleanDeterminations($preview = 1, $fix = 0, $startDetid = 0, $limit = 100000){
		$sql = 'SELECT detid, identifiedby, dateidentified, sciname, scientificnameauthorship, identificationreferences, identificationremarks, taxonremarks '.
			'FROM omoccurdeterminations ';
		if($startDetid) $sql .= 'WHERE detid > '.$startDetid.' ';
		$sql .= 'ORDER BY detid ';
		if($limit) $sql .= 'LIMIT '.$limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		echo '<ol>';
		while($r = $rs->fetch_assoc()){
			$detid = $r['detid'];
			echo '<li><b>detid:</b> '.$detid.': ';
			$setArr = array();
			$problem = false;

			foreach($r as $k => $v){
				if($v && $k != 'detid'){
					$vCleaned = $this->cleanInStr($v);
					$toUtf8 = Encoding::toUTF8($vCleaned);
					$fixUtf8 = Encoding::fixUTF8($vCleaned);
					if($fix && $fixUtf8 != $vCleaned){
						$problem = true;
					}
					if(!$fix && $toUtf8 != $vCleaned){
						$problem = true;
					}
					if($preview){
						echo '<div style="margin-left:10px;">';
						echo '<b>'.$k.':</b>: '.$v.'; ';
						echo '<b>toUTF8 method:</b> '.$toUtf8.'; ';
						echo '<b>fixUTF8 method:</b> '.$fixUtf8;
						echo '</div>';
					}
					if($fix){
						$setArr[] = $k.' = "'.$fixUtf8.'"';
					}
					else{
						$setArr[] = $k.' = "'.$toUtf8.'"';
					}
				}
			}
			if($setArr){
				$sqlFix = 'UPDATE omoccurdeterminations SET '.implode(',',$setArr).' WHERE detid = '.$detid;
				if($problem){
					echo '<div style="margin-left:10px;"><b>PROBLEM:</b>'.$sqlFix.'</div>';
					if(!$preview){
						if(!$this->conn->query($sqlFix)){
							echo '<div style="margin-left:10px;">ERROR: '.$this->conn->error.'; SQL: '.$sqlFix.'</div>';
						}
					}
				}
			}
			echo '</li>';
		}
		$rs->free();
		echo '</ol>';
	}

	public function cleanTaxa($preview = 1, $fix = 0, $startTid = 0, $limit = 100000){
		$sql = 'SELECT tid, author '.
			'FROM taxa '.
			'WHERE author IS NOT NULL ';
		if($startTid) $sql .= 'AND tid > '.$startTid.' ';
		$sql .= 'ORDER BY tid ';
		if($limit) $sql .= 'LIMIT '.$limit;
		//echo $sql;

		$rs = $this->conn->query($sql);
		echo '<ol>';
		while($r = $rs->fetch_assoc()){
			$tid = $r['tid'];
			echo '<li><b>tid: </b>'.$tid.': ';
			$setArr = array();
			$problem = false;
			foreach($r as $k => $v){
				if($v && $k != 'tid'){
					$vCleaned = $this->cleanInStr($v);
					$toUtf8 = Encoding::toUTF8($vCleaned);
					$fixUtf8 = Encoding::fixUTF8($vCleaned);
					if($fix && $fixUtf8 != $vCleaned){
						$problem = true;
					}
					if(!$fix && $toUtf8 != $vCleaned){
						$problem = true;
					}
					if($preview || $problem){
						echo '<div style="margin-left:10px;">';
						echo '<b>'.$k.':</b>: '.$v.'; ';
						echo '<b>toUTF8 method:</b> '.$toUtf8.'; ';
						echo '<b>fixUTF8 method:</b> '.$fixUtf8;
						echo '</div>';
					}
					if($fix){
						$setArr[] = $k.' = "'.$fixUtf8.'"';
					}
					else{
						$setArr[] = $k.' = "'.$toUtf8.'"';
					}
				}
			}
			if($setArr){
				$sqlFix = 'UPDATE taxa SET '.implode(',',$setArr).' WHERE tid = '.$tid;
				if($problem){
					echo '<div style="margin-left:10px;"><b>PROBLEM:</b> '.$sqlFix.'</div>';
					if(!$preview){
						if(!$this->conn->query($sqlFix)){
							echo '<div style="margin-left:10px;">ERROR: '.$this->conn->error.'; SQL: '.$sqlFix.'</div>';
						}
					}
				}
			}
			echo '</li>';
		}
		$rs->free();
		echo '</ol>';
	}

	private function cleanInStr($str){
		$badwordchars=array("\xe2\x80\x98", // left single quote
							"\xe2\x80\x99", // right single quote
							"\xe2\x80\x9c", // left double quote
							"\xe2\x80\x9d", // right double quote
							"\xe2\x80\x94", // em dash
							"\xe2\x80\xa6" // elipses
		);
		$fixedwordchars=array("'", "'", '"', '"', '-', '...');
		$str = str_replace($badwordchars, $fixedwordchars, $str);
		return $this->conn->real_escape_string(trim($str));
	}

}


/*
Copyright (c) 2008 Sebastian Grignoli
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. Neither the name of copyright holders nor the names of its
   contributors may be used to endorse or promote products derived
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL COPYRIGHT HOLDERS OR CONTRIBUTORS
BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * @author   "Sebastian Grignoli" <grignoli@framework2.com.ar>
 * @package  Encoding
 * @version  1.2
 * @link     https://github.com/neitanod/forceutf8
 * @example  https://github.com/neitanod/forceutf8
 * @license  Revised BSD
  */

class Encoding {

  protected static $win1252ToUtf8 = array(
        128 => "\xe2\x82\xac",

        130 => "\xe2\x80\x9a",
        131 => "\xc6\x92",
        132 => "\xe2\x80\x9e",
        133 => "\xe2\x80\xa6",
        134 => "\xe2\x80\xa0",
        135 => "\xe2\x80\xa1",
        136 => "\xcb\x86",
        137 => "\xe2\x80\xb0",
        138 => "\xc5\xa0",
        139 => "\xe2\x80\xb9",
        140 => "\xc5\x92",

        142 => "\xc5\xbd",


        145 => "\xe2\x80\x98",
        146 => "\xe2\x80\x99",
        147 => "\xe2\x80\x9c",
        148 => "\xe2\x80\x9d",
        149 => "\xe2\x80\xa2",
        150 => "\xe2\x80\x93",
        151 => "\xe2\x80\x94",
        152 => "\xcb\x9c",
        153 => "\xe2\x84\xa2",
        154 => "\xc5\xa1",
        155 => "\xe2\x80\xba",
        156 => "\xc5\x93",

        158 => "\xc5\xbe",
        159 => "\xc5\xb8"
  );

    protected static $brokenUtf8ToUtf8 = array(
        "\xc2\x80" => "\xe2\x82\xac",

        "\xc2\x82" => "\xe2\x80\x9a",
        "\xc2\x83" => "\xc6\x92",
        "\xc2\x84" => "\xe2\x80\x9e",
        "\xc2\x85" => "\xe2\x80\xa6",
        "\xc2\x86" => "\xe2\x80\xa0",
        "\xc2\x87" => "\xe2\x80\xa1",
        "\xc2\x88" => "\xcb\x86",
        "\xc2\x89" => "\xe2\x80\xb0",
        "\xc2\x8a" => "\xc5\xa0",
        "\xc2\x8b" => "\xe2\x80\xb9",
        "\xc2\x8c" => "\xc5\x92",

        "\xc2\x8e" => "\xc5\xbd",


        "\xc2\x91" => "\xe2\x80\x98",
        "\xc2\x92" => "\xe2\x80\x99",
        "\xc2\x93" => "\xe2\x80\x9c",
        "\xc2\x94" => "\xe2\x80\x9d",
        "\xc2\x95" => "\xe2\x80\xa2",
        "\xc2\x96" => "\xe2\x80\x93",
        "\xc2\x97" => "\xe2\x80\x94",
        "\xc2\x98" => "\xcb\x9c",
        "\xc2\x99" => "\xe2\x84\xa2",
        "\xc2\x9a" => "\xc5\xa1",
        "\xc2\x9b" => "\xe2\x80\xba",
        "\xc2\x9c" => "\xc5\x93",

        "\xc2\x9e" => "\xc5\xbe",
        "\xc2\x9f" => "\xc5\xb8"
  );

  protected static $utf8ToWin1252 = array(
       "\xe2\x82\xac" => "\x80",

       "\xe2\x80\x9a" => "\x82",
       "\xc6\x92"     => "\x83",
       "\xe2\x80\x9e" => "\x84",
       "\xe2\x80\xa6" => "\x85",
       "\xe2\x80\xa0" => "\x86",
       "\xe2\x80\xa1" => "\x87",
       "\xcb\x86"     => "\x88",
       "\xe2\x80\xb0" => "\x89",
       "\xc5\xa0"     => "\x8a",
       "\xe2\x80\xb9" => "\x8b",
       "\xc5\x92"     => "\x8c",

       "\xc5\xbd"     => "\x8e",


       "\xe2\x80\x98" => "\x91",
       "\xe2\x80\x99" => "\x92",
       "\xe2\x80\x9c" => "\x93",
       "\xe2\x80\x9d" => "\x94",
       "\xe2\x80\xa2" => "\x95",
       "\xe2\x80\x93" => "\x96",
       "\xe2\x80\x94" => "\x97",
       "\xcb\x9c"     => "\x98",
       "\xe2\x84\xa2" => "\x99",
       "\xc5\xa1"     => "\x9a",
       "\xe2\x80\xba" => "\x9b",
       "\xc5\x93"     => "\x9c",

       "\xc5\xbe"     => "\x9e",
       "\xc5\xb8"     => "\x9f"
    );

  static function toUTF8($text){
  /**
   * Function Encoding::toUTF8
   *
   * This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
   *
   * It assumes that the encoding of the original string is either Windows-1252 or ISO 8859-1.
   *
   * It may fail to convert characters to UTF-8 if they fall into one of these scenarios:
   *
   * See: https://github.com/neitanod/forceutf8
   *
   * @name toUTF8
   * @param string $text  Any string.
   * @return string  The same string, UTF8 encoded
   *
   */

    if(is_array($text))
    {
      foreach($text as $k => $v)
      {
        $text[$k] = self::toUTF8($v);
      }
      return $text;
    } elseif(is_string($text)) {

      $max = strlen($text);
      $buf = "";
      for($i = 0; $i < $max; $i++){
          $c1 = $text{$i};
          if($c1>="\xc0"){ //Should be converted to UTF8, if it's not UTF8 already
            $c2 = $i+1 >= $max? "\x00" : $text{$i+1};
            $c3 = $i+2 >= $max? "\x00" : $text{$i+2};
            $c4 = $i+3 >= $max? "\x00" : $text{$i+3};
              if($c1 >= "\xc0" & $c1 <= "\xdf"){ //looks like 2 bytes UTF8
                  if($c2 >= "\x80" && $c2 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                      $buf .= $c1 . $c2;
                      $i++;
                  } else { //not valid UTF8.  Convert it.
                      $cc1 = (chr(ord($c1) / 64) | "\xc0");
                      $cc2 = ($c1 & "\x3f") | "\x80";
                      $buf .= $cc1 . $cc2;
                  }
              } elseif($c1 >= "\xe0" & $c1 <= "\xef"){ //looks like 3 bytes UTF8
                  if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                      $buf .= $c1 . $c2 . $c3;
                      $i = $i + 2;
                  } else { //not valid UTF8.  Convert it.
                      $cc1 = (chr(ord($c1) / 64) | "\xc0");
                      $cc2 = ($c1 & "\x3f") | "\x80";
                      $buf .= $cc1 . $cc2;
                  }
              } elseif($c1 >= "\xf0" & $c1 <= "\xf7"){ //looks like 4 bytes UTF8
                  if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                      $buf .= $c1 . $c2 . $c3;
                      $i = $i + 2;
                  } else { //not valid UTF8.  Convert it.
                      $cc1 = (chr(ord($c1) / 64) | "\xc0");
                      $cc2 = ($c1 & "\x3f") | "\x80";
                      $buf .= $cc1 . $cc2;
                  }
              } else { //doesn't look like UTF8, but should be converted
                      $cc1 = (chr(ord($c1) / 64) | "\xc0");
                      $cc2 = (($c1 & "\x3f") | "\x80");
                      $buf .= $cc1 . $cc2;
              }
          } elseif(($c1 & "\xc0") == "\x80"){ // needs conversion
                if(isset(self::$win1252ToUtf8[ord($c1)])) { //found in Windows-1252 special cases
                    $buf .= self::$win1252ToUtf8[ord($c1)];
                } else {
                  $cc1 = (chr(ord($c1) / 64) | "\xc0");
                  $cc2 = (($c1 & "\x3f") | "\x80");
                  $buf .= $cc1 . $cc2;
                }
          } else { // it doesn't need convesion
              $buf .= $c1;
          }
      }
      return $buf;
    } else {
      return $text;
    }
  }

  static function toWin1252($text) {
    if(is_array($text)) {
      foreach($text as $k => $v) {
        $text[$k] = self::toWin1252($v);
      }
      return $text;
    } elseif(is_string($text)) {
      return utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), self::toUTF8($text)));
    } else {
      return $text;
    }
  }

  static function toISO8859($text) {
    return self::toWin1252($text);
  }

  static function toLatin1($text) {
    return self::toWin1252($text);
  }

  static function fixUTF8($text){
    if(is_array($text)) {
      foreach($text as $k => $v) {
        $text[$k] = self::fixUTF8($v);
      }
      return $text;
    }

    $last = "";
    while($last <> $text){
      $last = $text;
      $text = self::toUTF8(utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), $text)));
    }
    $text = self::toUTF8(utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), $text)));
    return $text;
  }

  static function UTF8FixWin1252Chars($text){
    // If you received an UTF-8 string that was converted from Windows-1252 as it was ISO8859-1
    // (ignoring Windows-1252 chars from 80 to 9F) use this function to fix it.
    // See: http://en.wikipedia.org/wiki/Windows-1252

    return str_replace(array_keys(self::$brokenUtf8ToUtf8), array_values(self::$brokenUtf8ToUtf8), $text);
  }

  static function removeBOM($str=""){
    if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
      $str=substr($str, 3);
    }
    return $str;
  }

  public static function normalizeEncoding($encodingLabel)
  {
    $encoding = strtoupper($encodingLabel);
    $enc = preg_replace('/[^a-zA-Z0-9\s]/', '', $encoding);
    $equivalences = array(
        'ISO88591' => 'ISO-8859-1',
        'ISO8859'  => 'ISO-8859-1',
        'ISO'      => 'ISO-8859-1',
        'LATIN1'   => 'ISO-8859-1',
        'LATIN'    => 'ISO-8859-1',
        'UTF8'     => 'UTF-8',
        'UTF'      => 'UTF-8',
        'WIN1252'  => 'ISO-8859-1',
        'WINDOWS1252' => 'ISO-8859-1'
    );

    if(empty($equivalences[$encoding])){
      return 'UTF-8';
    }

    return $equivalences[$encoding];
  }

  public static function encode($encodingLabel, $text)
  {
    $encodingLabel = self::normalizeEncoding($encodingLabel);
    if($encodingLabel == 'UTF-8') return Encoding::toUTF8($text);
    if($encodingLabel == 'ISO-8859-1') return Encoding::toLatin1($text);
  }

}
?>