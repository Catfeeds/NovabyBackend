<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PrjChat extends Model
{
    protected $table = 'prj_chats';
    public $timestamps = false;

    public $fillable = [
        'id'
        ,'prj_id'
        ,'chat_from_uid'
        ,'chat_to_uid'
        ,'talk_key'
        ,'content'
        ,'created_at'
    ];
}
