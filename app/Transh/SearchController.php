<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Session;
class SearchController extends Controller
{

    private $info;
    public function __construct(){
        $this->info=Session::get('userInfo',null);
    }

    public function searchModel($kw=''){
        $cart_info=[];
        $notices=[];
        if($this->info){
            $notices=$this->getNoticesLists($this->info->user_id);
            $cart_info=$this->getCart($this->info->user_id);
        }
        return view('search.searchModel',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Search Models','search'=>$kw]);

    }
    public function searchUser($kw=''){
        $cart_info=[];
        $notices=[];
        if($this->info){
            $notices=$this->getNoticesLists($this->info->user_id);
            $cart_info=$this->getCart($this->info->user_id);
        }
        return view('search.searchUser',['user'=>$this->info,'notices'=>$notices,'cart_info'=>$cart_info,'title'=>'Search Users','search'=>$kw]);
    }
}
