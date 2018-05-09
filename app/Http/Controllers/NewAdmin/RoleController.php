<?php

namespace App\Http\Controllers\NewAdmin;

use App\Model\Permission;
use App\Model\Role;
use App\Model\RolePermission;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $roles = Role::all();
        return view('newadmin.role.index')->with('roles',$roles);
    }

    public function edit($id)
    {
        $role = Role::with('rolePermission')->find($id);
        $permission = Permission::get();
        $menu = $this->Menu($permission);
        $rolePermission = $role->rolePermission;
        $menu = $this->getTree($menu,$rolePermission);
        return view('newadmin.role.edit')->with(['role'=>$role,'menu'=>$menu,'id'=>$id]);
    }
    public function save(Request $request)
    {
        $menus = $request->get('menus');
        $feature = $request->get('feature');
        RolePermission::where('role_id',$request->get('id'))->delete();
        foreach ($menus as $item){
           RolePermission::updateOrCreate(['role_id'=>$request->get('id'),'permission_id'=>$item]);
        }
        foreach ($feature as $key=>$value){
            RolePermission::updateOrCreate(['role_id'=>$request->get('id'),'permission_id'=>$key,'read'=>isset($value['read'])?1:0,'operate'=>isset($value['operate'])?1:0]);
        }
        return redirect()->back();
    }
    function getTree($menus,$rolePermission)
    {
        $html = '';
        foreach ($menus as $k => $v) {
            if($v->type==1 || $v->type==2){
                $html .= '<li style="float: none;">'.$v['name_cn'].'  <input id="checked" type="checkbox" name="menus[]" value="'.$v->id.'"';
                foreach ($rolePermission as $item){
                    if ($item->permission_id == $v->id){
                        $html.= 'checked="checked"';
                    }
                }
                $html .='/>';
            }else{
                $html .= '<li style="float: none;">'.$v['name_cn'].':  可读<input id="features" type="checkbox" name="feature['.$v->id.'][read]" value="1"';
                foreach ($rolePermission as $item){
                    if ($item->permission_id == $v->id  && $item->read==1){
                        $html.= 'checked="checked"';
                    }
                }
                $html .= '/>';
                $html.='可操作<input id="features" type="checkbox" name="feature['.$v->id.'][operate]" value="1"';
                foreach ($rolePermission as $item){
                    if ($item->permission_id == $v->id && $item->operate==1){
                        $html.= 'checked="checked"';
                    }
                }
                $html.='/> ';
            }
            $html .= $this->getTree($v->children,$rolePermission);
            $html = $html.'</li>';
        }
        return $html ? '<ul>'.$html.'</ul>' : $html ;
    }
    public function Menu($menus)
    {
        $roots = $menus->filter(function ($item){
            return !$item->pid;
        });
        $tree = $roots->map(function($root)use($menus){
            return $this->tree($menus, $root);
        });
        return $tree;
    }
    public function tree($menus,$root)
    {
        $children = $menus->filter(function ($item)use($root){
            return $item->pid == $root->id;
        });
        $children->map(function($child)use($menus){
            $this->tree($menus, $child);
        });
        $root->children = $children;//子类存储在父类中
        return $root;
    }
}
