<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WorkDetail extends Model
{
    protected $table = 'work_detail';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = false;

    public function Work()
    {
        return $this->belongsTo('App\Model\Work','id','work_detail');
    }

    public function Daily()
    {
        return $this->belongsTo('App\Model\BuildDaily','id','bd_attachment_trans');
    }
}
