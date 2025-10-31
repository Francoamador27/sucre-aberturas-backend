<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consul extends Model
{
    protected $table = 'consult';          // nombre real de la tabla
    protected $primaryKey = 'idconslt';   // PK real
    public $timestamps = false;           // la tabla no usa created_at/updated_at

    protected $fillable = [
        'mtcl', 'idpa', 'state', 'fere', 'numdiente',
    ];
}
