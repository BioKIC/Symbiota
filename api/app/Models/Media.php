<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model{

	protected $table = 'images';
	protected $primaryKey = 'imgid';

	public $timestamps = false;

	protected $fillable = [
		'url', ' thumbnailUrl', 'originalUrl', 'archiveUrl', 'photographer', 'photographerUid', 'imageType', 'format', 'caption', 'owner', 'sourceUrl', 'referenceUrl', 'copyright',
		'rights', 'accessRights', 'locality', 'occid', 'notes', 'anatomy', 'username', 'sourceIdentifier', 'hashFunction', 'hashValue', 'mediaMD5', 'dynamicProperties',
		'defaultDisplay', 'sortSequence', 'sortOccurrence'
	];

	protected $hidden = ['dynamicProperties', 'username', 'photographerUid', 'sortOccurrence', 'defaultDisplay'];
	private $serverDomain;

	public function __construct(){
		$this->setServerDomain();
	}

	public function occurrence() {
		return $this->belongsTo(Occurrence::class, 'occid', 'occid');
	}

	//Accessor functions
	public function getUrlAttribute($value){
		if(substr($value, 0, 1) == '/') $value = $this->serverDomain . $value;
		return $value;
	}

	public function getThumbnailurlAttribute($value){
		if(substr($value, 0, 1) == '/') $value = $this->serverDomain . $value;
		return $value;
	}

	public function getOriginalurlAttribute($value){
		if(substr($value, 0, 1) == '/') $value = $this->serverDomain . $value;
		return $value;
	}

	private function setServerDomain(){
		$domain = 'http://';
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domain = 'https://';
		if(!empty($GLOBALS['SERVER_HOST'])){
			if(substr($GLOBALS['SERVER_HOST'], 0, 4) == 'http') $domain = $GLOBALS['SERVER_HOST'];
			else $domain .= $GLOBALS['SERVER_HOST'];
		}
		else $domain .= $_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 && !strpos($domain, ':'.$_SERVER['SERVER_PORT'])){
			$domain .= ':'.$_SERVER['SERVER_PORT'];
		}
		$domain = filter_var($domain, FILTER_SANITIZE_URL);
		$this->serverDomain = $domain;
	}
}