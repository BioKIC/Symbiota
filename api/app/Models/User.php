<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract{
	use Authenticatable, Authorizable, HasFactory;

	protected $table = 'users';
	protected $primaryKey = 'uid';
	public $timestamps = false;

	protected $fillable = [  ];

	protected $hidden = [ 'password' ];

	public function roles(){
		return $this->hasMany(UserRole::class, 'uid', 'uid');
	}

	public function accessTokens(){
		return $this->hasMany(UserRole::class, 'uid', 'uid');
	}
}
