<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model{

	protected $table = 'userroles';
	protected $primaryKey = 'userroleid';
	public $timestamps = false;

	protected $fillable = [];

	protected $hidden = [];

	public function user() {
		return $this->belongsTo(User::class, 'uid', 'uid');
	}
}