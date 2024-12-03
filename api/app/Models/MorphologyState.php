<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MorphologyState extends Model{

	protected $table = 'kmcs';
	protected $primaryKey = 'stateID';
	public $timestamps = false;

	protected $fillable = [];

	protected $hidden = [];


}