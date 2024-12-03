<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MorphologyCharacter extends Model{

	protected $table = 'kmcharacters';
	protected $primaryKey = 'cid';
	public $timestamps = false;

	protected $fillable = [];

	protected $hidden = ['difficultyRank', 'activationCode', 'enteredUid'];

	public function states(){
		return $this->hasMany(MorphologyState::class, 'cid', 'cid');
	}
}