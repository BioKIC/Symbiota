<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExsiccataNumber extends Model {
    protected $table = 'omexsiccatinumbers';
    protected $primaryKey = 'omenid';
    public $timestamps = false;
    protected $fillable = ['exsNumber', 'notes', 'initialTimestamp'];

    public function exsiccata() {
        return $this->belongsTo(Exsiccata::class, 'ometid', 'ometid');
    }
}

?>
