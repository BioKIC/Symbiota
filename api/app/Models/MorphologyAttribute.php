<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MorphologyAttribute extends Model{

	protected $table = 'kmdescr';
	//protected $primaryKey = 'attributeID';
	public $timestamps = false;

	protected $fillable = [];

	protected $hidden = ['pseudoTrait', 'frequency','seq','txt'];
	public static $snakeAttributes = false;

	public function taxonomy(){
		return $this->belongsTo(Taxonomy::class, 'tid', 'tid');
	}
}