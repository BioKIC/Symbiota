<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccessToken extends Model{

	protected $table = 'useraccesstokens';
	protected $primaryKey = 'tokenID';
	public $timestamps = false;

	protected $fillable = [ 'token', 'device', 'experationDate' ];

	protected $hidden = [ 'token' ];

	public function user() {
		return $this->belongsTo(User::class, 'uid', 'uid');
	}
}