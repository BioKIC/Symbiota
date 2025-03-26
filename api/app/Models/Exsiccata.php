<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exsiccata extends Model {
    protected $table = 'omexsiccatititles';
    protected $primaryKey = 'ometid';
    protected $hidden = ['lasteditedby'];
    protected $fillable = []; // @TODO maybe add to this
    protected $maps = [];
    protected $appends = [];

    public function numbers(){
        return $this->hasMany(ExsiccataNumber::class, 'ometid', 'ometid');
    }
}
