<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers;

/**
 * Description of DemoController
 *
 * @author wz
 */
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
class DemoController extends Controller{
    //put your code here
    public function demo(){
        return view("demo.demo");
    }
    public function demo1(){
        echo '[{"age" : 24, "sex" : "boy", "name" : "huangxueming"},{"age" : 26, "sex" : "boy", "name" : "huangxueming2"}]';
    }
    public function demo2(){
        echo '[{"age" : 65, "sex" : "boy2", "name" : "huangxueming2"},{"age" : 26, "sex" : "boy", "name" : "huangxueming2"}]';
    }
    public function demo3(){
        echo '[{"age" : 244, "sex" : "boy4", "name" : "huangxueming4"},{"age" : 264, "sex" : "boy4", "name" : "huangxueming4"}]';
    }
    public function demo4(){
        return request()->json(['name'=>'wz3','age'=>203]);
    }
}
