<?php
/**
 * Created by PhpStorm.
 * User: wz
 * Date: 2017/4/10
 * Time: 14:42
 */

namespace App\Services;


class CreateProgress
{
    public static function url($relstate,$currstatus,$id){
       // dd($_SERVER);
       // exit;
        $html = '';
        if($relstate==1){
            if($id==0){
                $html = "<li class='red-color'><a href='http://".$_SERVER['HTTP_HOST']."/project/create/new' class='curr'>Specify Project Requirement</a></li>";
                //$html = "<li class='red-color'><a href='".route('pub_step1',$id)."' class='curr'>Specify Project Requirement</a></li>";
            }else{
                $html = "<li class='red-color'><a href='".route('pub_step1',$id)."' class='curr'>Specify Project Requirement</a></li>";
            }
            $html .= "<li><a>Set Proposal Time</a></li>".
            "<li><a>Set Trial Work</a></li>".
            "<li><a>Set Contact</a></li>".
            "<li><a>Submit Project</a></li>";



        }elseif($relstate==2){
            $html = "<li class='red-color'><a href='".route('pub_step1',$id)."' class='passed'>Specify Project Requirement</a></li>".
                "<li class='red-color'><a href='".route('pub_step2',$id)."' class='curr'>Set Proposal Time</a></li>".
                "<li><a href='#'>Set Trial Work</a></li>".
                "<li><a href='#'>Set Contact</a></li>".
                "<li><a href='#'>Submit Project</a></li>";

        }elseif($relstate==3){
            $html = "<li class='red-color'><a href='".route('pub_step1',$id)."' class='passed'>Specify Project Requirement</a></li>".
                "<li class='red-color'><a href='".route('pub_step2',$id)."' class='passed'>Set Proposal Time</a></li>".
                "<li class='red-color'><a href='".route('pub_step3',$id)."' class='curr'>Set Trial Work</a></li>".
                "<li><a>Set Contact</a></li>".
                "<li><a>Submit Project</a></li>";

        }elseif($relstate==4){
            $html = "<li class='red-color'><a href='".route('pub_step1',$id)."' class='passed'>Specify Project Requirement</a></li>".
                "<li class='red-color'><a href='".route('pub_step2',$id)."' class='passed'>Set Proposal Time</a></li>".
                "<li class='red-color'><a href='".route('pub_step3',$id)."' class='passed'>Set Trial Work</a></li>".
                "<li class='red-color'><a href='".route('pub_step4',$id)."' class='curr'>Set Contact</a></li>".
                "<li><a href='#'>Submit Project</a></li>";

        }elseif($relstate==5){
            $html = "<li class='red-color'><a href='".route('pub_step1',$id)."' class='passed'>Specify Project Requirement</a></li>".
                "<li class='red-color'><a href='".route('pub_step2',$id)."' class='passed'>Set Proposal Time</a></li>".
                "<li class='red-color'><a href='".route('pub_step3',$id)."' class='passed'>Set Trial Work</a></li>".
                "<li class='red-color'><a href='".route('pub_step4',$id)."' class='passed'>Set Contact</a></li>".
                "<li class='red-color'><a href='".route('pub_step5',$id)."' class='curr'>Submit Project</a></li>";
        }
        return $html;
}
    public static function url2($relstate,$currstatus,$id){
        $html = '';
        if($relstate==1){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='curr'>Requirement</a></li>".
                "<li><a href='#'>Proposal</a></li>".
                "<li><a href='#'>Trial</a></li>".
                "<li><a href='#'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";



        }elseif($relstate==2){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('prjs2',$id)."' class='curr'>Proposal</a></li>".
                "<li><a href='#'>Trial</a></li>".
                "<li><a href='#'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";

        }elseif($relstate==3){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('prjs2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('prjs3',$id)."' class='curr'>Trial</a></li>".
                "<li><a href='#'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";

        }elseif($relstate==4){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('prjs2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('prjs3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('prjs4',$id)."' class='curr'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";

        }elseif($relstate==5){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('prjs2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('prjs3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('prjs4',$id)."' class='passed'>Contract</a></li>".
                "<li class='red-color'><a href='".route('prjs5',$id)."' class='curr'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";
        }elseif($relstate==6){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('prjs2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('prjs3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('prjs4',$id)."' class='passed'>Contract</a></li>".
                "<li class='red-color'><a href='".route('prjs5',$id)."' class='passed'>Payment</a></li>".
                "<li class='red-color'><a href='".route('prjs6',$id)."' class='curr'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";
        }elseif($relstate==7){
            $html = "<li class='red-color'><a href='".route('prjs1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('prjs2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('prjs3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('prjs4',$id)."' class='passed'>Contract</a></li>".
                "<li class='red-color'><a href='".route('prjs5',$id)."' class='passed'>Payment</a></li>".
                "<li class='red-color'><a href='".route('prjs6',$id)."' class='passed'>Building</a></li>".
                "<li class='red-color'><a href='".route('prjs7',$id)."' class='curr'>Submission</a></li>";
        }

        return $html;
    }

    public static function url3($relstate,$currstatus,$id){
        $html = '';
        if($relstate==1){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='curr'>Requirement</a></li>".
                "<li><a href='#'>Proposal</a></li>".
                "<li><a href='#'>Trial</a></li>".
                "<li><a href='#'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";



        }elseif($relstate==2){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('taskstep2',$id)."' class='curr'>Proposal</a></li>".
                "<li><a href='#'>Trial</a></li>".
                "<li><a href='#'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";

        }elseif($relstate==3){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('taskstep2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('taskstep3',$id)."' class='curr'>Trial</a></li>".
                "<li><a href='#'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";

        }elseif($relstate==4){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('taskstep2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('taskstep3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('taskstep4',$id)."' class='curr'>Contract</a></li>".
                "<li><a href='#'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";

        }elseif($relstate==5){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('taskstep2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('taskstep3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('taskstep4',$id)."' class='passed'>Contract</a></li>".
                "<li class='red-color'><a href='".route('taskstep5',$id)."' class='curr'>Payment</a></li>".
                "<li><a href='#'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";
        }elseif($relstate==6){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('taskstep2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('taskstep3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('taskstep4',$id)."' class='passed'>Contract</a></li>".
                "<li class='red-color'><a href='".route('taskstep5',$id)."' class='passed'>Payment</a></li>".
                "<li class='red-color'><a href='".route('taskstep6',$id)."' class='curr'>Building</a></li>".
                "<li><a href='#'>Submission</a></li>";
        }elseif($relstate==7){
            $html = "<li class='red-color'><a href='".route('taskstep1',$id)."' class='passed'>Requirement</a></li>".
                "<li class='red-color'><a href='".route('taskstep2',$id)."' class='passed'>Proposal</a></li>".
                "<li class='red-color'><a href='".route('taskstep3',$id)."' class='passed'>Trial</a></li>".
                "<li class='red-color'><a href='".route('taskstep4',$id)."' class='passed'>Contract</a></li>".
                "<li class='red-color'><a href='".route('taskstep5',$id)."' class='passed'>Payment</a></li>".
                "<li class='red-color'><a href='".route('taskstep6',$id)."' class='passed'>Building</a></li>".
                "<li class='red-color'><a href='".route('taskstep7',$id)."' class='curr'>Submission</a></li>";
        }

        return $html;
    }

}