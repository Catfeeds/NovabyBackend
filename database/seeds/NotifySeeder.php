<?php

use Illuminate\Database\Seeder;

class NotifySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //生成基本消息和邮件模版
        $title = array(
            '1'=>'注册',
            '2'=>'审核',
            '3' =>'修改密码',
            '4'=>'作品进入market',
        );
        $type = array(
            '1'=>'1',
            '2'=>'2',
            '3' =>'3',
            '4'=>'4',
        );
        $content = array(
            '1'=>'恭喜，注册成功',
            '2'=>'恭喜，模型审核成功',
            '3' =>'恭喜，修改密码成功',
            '4'=>'恭喜，作品进入market成功',
        );
        for($i= 1 ;$i <= count($title);$i++)
        {
            $notify = new \App\Model\Notify();
            $notify->title = $title[$i];
            $notify->type = $type[$i];
            $notify->content = $content[$i];
            $notify->save();
            $mail = new \App\Model\Mail();
            $mail->title = $title[$i];
            $mail->type = $type[$i];
            $mail->content = $content[$i];
            $mail->save();
        }

    }
}
