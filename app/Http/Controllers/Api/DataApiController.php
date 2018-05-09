<?php

namespace App\Http\Controllers\Api;

use App\libs\Format;
use App\libs\StaticConf;
use App\Model\Cate;
use App\Model\Country;
use App\Model\Field;
use App\Model\Job;
use App\Model\Tag;
use App\Model\Timezone;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Support\Facades\Input;

class DataApiController extends BaseApiController
{
    public function  dict($cate=''){
        $lang = $this->lang;
        $dict = $cate;
        $dicts = ['country','job','field','format','tag','modelattr','modelcate','feature','licence','reportreason','category','city','model','companyInfo','file','image'];
        if(in_array($dict,$dicts)){
            switch ($lang){
                case 'zh':
                    switch($dict){
                        case 'model':
                            $format =['obj','fbx','stl','3d','gltf','dae','3ds','blend'];
                            return $this->jsonOk('ok',['format'=>$format]);
                                break;
                        case 'country':
                            $table = new Country([],$lang);
                            $country = $table->select('id','name')->where('pid',0)->orderBy('name','ASC')->get();
                            return $this->jsonOk('ok',['country'=>$country]);
                            break;
                        case 'job':
                            $job =  Job::all();
                            return $this->jsonOk('ok',['job'=>$job]);
                            break;
                        case 'field':
                            $field =  Field::select('id','name_cn')->get()->map(function ($item){
                                $item->name = $item->name_cn;
                                unset($item->name_cn);
                                return $item;
                                });
                            return $this->jsonOk('ok',['field'=>$field]);
                            break;
                        case 'tag':
                            $tag =  Tag::orderBy('tag_name','ASC')->all();
                            return $this->jsonOk('ok',['tag'=>$tag]);
                            break;
                        case 'format':
                            $format =  Cate::select('cate_id','cate_name')->where('cate_pid',4)->where('cate_active',0)->orderBy('cate_order','ASC')->get();
                            return $this->jsonOk('ok',['format'=>$format]);
                            break;
                        case 'category':
                            $category =  Cate::select('cate_id','cate_name')->where('cate_pid',1)->where('cate_active',0)->orderBy('cate_order','ASC')->get();
                            return $this->jsonOk('ok',['category'=>$category]);
                            break;
                        case 'licence':
                            $licence = [
                                ['id'=>1, 'name'=>'Attribution'],
                                ['id'=>2, 'name'=>'Non-Commercial'],
                                ['id'=>3, 'name'=>'Non-derivatives'],
                                ['id'=>4, 'name'=>'Share alike'],
                            ];
                            return $this->jsonOk('ok',['licence'=>$licence]);
                            break;
                        case 'modelattr':
                                $category =  Cate::select('cate_id','cate_name_cn')
                                    ->where('cate_pid',1)
                                    ->where('cate_active',0)
                                    ->where('cate_isgame','=',null)
                                    ->orderBy('cate_order','ASC')
                                    ->get()->map(function ($item){
                                        $item->cate_name = $item->cate_name_cn;
                                        unset($item->cate_name_cn);
                                        return $item;
                                    });
                                $licence = [
                                    ['id'=>1, 'name'=>'Attribution'],
                                    ['id'=>2, 'name'=>'Non-Commercial'],
                                    ['id'=>3, 'name'=>'Non-derivatives'],
                                    ['id'=>4, 'name'=>'Share alike'],
                                ];
                            return $this->jsonOk('ok',['category'=>$category,'licence'=>$licence]);
                            break;
                        case 'companyInfo':
                            $company_size = StaticConf::$company_size_zh;
                            $company_type = StaticConf::$company_type_zh;
                            $english_level =  [
                                0=>['id'=>0,'name'=>'不限制英语水平'],
                                1=>['id'=>1,'name'=>'初级英语水平'],
                                2=>['id'=>2,'name'=>'可以对话'],
                                3=>['id'=>3,'name'=>'流利'],
                                4=>['id'=>4,'name'=>'英语母语'],
                            ];;
                            return $this->jsonOk('ok',['company_size'=>$company_size,'company_type'=>$company_type,'english_level'=>$english_level]);
                            break;
                    }
                    break;
                default:
                    switch($dict){
                        case 'model':
                            $format = ['obj','fbx','stl','3d','gltf','dae','3ds','blend'] ;
                            return $this->jsonOk('ok',['format'=>$format]);
                            break;
                        case 'country':
                            $table = new Country([],$lang);
                            $country = $table->select('id','name')->where('pid',0)->orderBy('name','ASC')->get();
                            return $this->jsonOk('ok',['country'=>$country]);
                            break;
                        case 'job':
                            $job =  Job::all();
                            return $this->jsonOk('ok',['job'=>$job]);
                            break;
                        case 'field':
                            $field =  Field::select('id','name')->get();
                            return $this->jsonOk('ok',['field'=>$field]);
                            break;
                        case 'tag':
                            $tag =  Tag::orderBy('tag_name','ASC')->all();
                            return $this->jsonOk('ok',['tag'=>$tag]);
                            break;
                        case 'format':
                            $format =  Cate::select('cate_id','cate_name')->where('cate_pid',4)->where('cate_active',0)->orderBy('cate_order','ASC')->get();
                            return $this->jsonOk('ok',['format'=>$format]);
                            break;
                        case 'category':
                            $category =  Cate::select('cate_id','cate_name')->where('cate_pid',1)->where('cate_active',0)->orderBy('cate_order','ASC')->get();
                            return $this->jsonOk('ok',['category'=>$category]);
                            break;
                        case 'licence':
                            $licence = [
                                ['id'=>1, 'name'=>'Attribution'],
                                ['id'=>2, 'name'=>'Non-Commercial'],
                                ['id'=>3, 'name'=>'Non-derivatives'],
                                ['id'=>4, 'name'=>'Share alike'],
                            ];
                            return $this->jsonOk('ok',['licence'=>$licence]);
                            break;
                        case 'modelattr':
                                $category =  Cate::select('cate_id','cate_name')
                                    ->where('cate_pid',1)
                                    ->where('cate_active',0)
                                    ->where('cate_isgame','=',null)
                                    ->orderBy('cate_order','ASC')
                                    ->get();
                                $licence = [
                                    ['id'=>1, 'name'=>'Attribution'],
                                    ['id'=>2, 'name'=>'Non-Commercial'],
                                    ['id'=>3, 'name'=>'Non-derivatives'],
                                    ['id'=>4, 'name'=>'Share alike'],
                                ];
                            return $this->jsonOk('ok',['category'=>$category,'licence'=>$licence]);
                            break;
                        case 'companyInfo':
                            $company_size = StaticConf::$company_size;
                            $company_type = StaticConf::$company_type;
                            $english_level =  [
                                0=>['id'=>0,'name'=>'Any level'],
                                1=>['id'=>1,'name'=>'Basic'],
                                2=>['id'=>2,'name'=>'Conversational'],
                                3=>['id'=>3,'name'=>'Fluent'],
                                4=>['id'=>4,'name'=>'Native or bilingual'],
                            ];
                            return $this->jsonOk('ok',['company_size'=>$company_size,'company_type'=>$company_type,'english_level'=>$english_level]);
                            break;
                    }
                    break;
            }
        }else{
            return $this->jsonErr('error','No this dict');
        }
    }
    public function cities(){
        $id=Input::get('id',0);
        if($id==0){
            return $this->jsonErr("not found");
        }
        $table = new Country([],$this->lang);
        $cities = $table->select('id','name')->where('pid',$id)->orderby('name','asc')->distinct('name')->get();
        if(count($cities)>0){
            return $this->jsonOk("ok",['cities'=>$cities]);
        }else{
            return $this->jsonErr("not found");
        }
    }
    public function socialstatus(){
        $id = Input::get('id',0);
        $_uid = 0;
        $me = 0;
        if($id){
            $_uid = $id;
        }else{
            if($this->_user){
                $_uid = $this->_user->user_id;
            }else{
                $_uid = 0;
            }
        }
        $user = User::find($_uid);
        if(!$user){
            return $this->jsonErr("user not found");
        }
        $_data=[
            'user_facebook'=>0,
            'user_twitter'=>0,
            'user_linkedin'=>0,
            'user_pinterest'=>0,

        ];
        $_data['user_facebook'] = $user->user_facebook_id?1:0;
        $_data['user_twitter']  = $user->user_twitter_id?1:0;
        $_data['user_linkedin']    =$user->user_linkedin_id?1:0;
        $_data['user_pinterest'] = $user->user_pinterest_id?1:0;
        return $this->jsonOk("ok",['status'=>$_data]);
    }
    public function timezone(){
        $timezone = Timezone::all();
        return $this->jsonOk("ok",['timezone'=>$timezone]);
    }

    /**
     * @return mixed
     */
    public function getFormat()
    {
        $type = Input::get('type','model');
        switch ($type)
        {
            case 'model':
                $format = Format::$model ;
                return $this->jsonOk('ok',['format'=>$format]);
                break;
                break;
            case 'image':
                $format = Format::$image ;
                return $this->jsonOk('ok',['format'=>$format]);
                break;
            case 'file':
                $format = Format::$file ;
                return $this->jsonOk('ok',['format'=>$format]);
                break;
        }
    }
}
