<?php
/**
 * Created by PhpStorm.
 * User: wz
 * Date: 2017/2/15
 * Time: 10:24
 */

namespace App\Services;


class TaskProgress
{
    public static function currentProgress($currProgress,$id){
        $html = '<li><a href="'.route('taskdetail',$id).'">Trial</a></li>
            <li><a href="'.route('tasktrial',$id).'">Pay</a></li>
            <li><a href="'.route('taskbuild',$id).'">Build</a></li>
            <li><a href="'.route('taskreview',$id).'">Review</a></li>';
        return $html;

    }

}