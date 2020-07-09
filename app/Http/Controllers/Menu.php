<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月1日
 * 菜单
 * 简介：主要用于菜单管理
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Menu extends Controller
{

    /**
     * 菜单管理
     */
    public function index(){
        $data=request()->all();
        $id=isset($data['id'])?$data['id']:'';
        $search=isset($data['search'])?trim($data['search']):'';
        $where=[];
        if($search){
            $where[]=['name','like','%'.$search.'%'];
        }
        $where[]=['status','<>',0];
        if($id){
            $where[]=['pid','=',$id];
            $menuList=DB::table('menu')->where($where)->orderBy('sort','desc')->select()->get();
            foreach ($menuList as $k=>$vo){
                $menuList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
                $menuList[$k]->p_name=DB::table('menu')->where(['id'=>$vo->pid])->where('status','<>',0)->value('title')?DB::table('menu')->where(['id'=>$vo->pid])->where('status','<>',0)->value('title'):'';
            }
        }else{
            $where[]=['pid','=','0'];
            $menuList=DB::table('menu')->where($where)->orderBy('sort','desc')->select()->get();
            foreach ($menuList as $k=>$vo){
                $menuList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
                $menuList[$k]->haveChild=DB::table('menu')->where(['pid'=>$vo->id])->where('status','<>',0)->count()?true:false;
                $menuList[$k]->p_name='';
            }
        }
        $ajaxarr=['code'=>0,'data'=>$menuList];
        return response()->json($ajaxarr);
    }

    /**
     * 选择父级菜单
     */
    public function select_menu(){
        $data=request()->all();
        $menu_id=isset($data['menu_id'])?$data['menu_id']:'';
        $menu_list=DB::table('menu')->where('status','<>',0)->where('pid','=',0)->select('id','title')->get();
        if($menu_id){
            foreach ($menu_list as $k=>$vo){
                if($vo->id==$menu_id){
                    $menu_list[$k]->selected='selected';
                }else{
                    $menu_list[$k]->selected='';
                }
            }
        }
        return response()->json($menu_list);
    }

    /**
     * 选择节点
     */
    public function select_node(){
        $data=request()->all();
        $type=isset($data['type'])?$data['type']:'1';
        if($type == 1) {
            $menu_id = isset($data['menu_id']) ? $data['menu_id'] : '';
            if ($menu_id) {
                $menu_data = DB::table('menu')->where(['id' => $menu_id])->first();
                $node_list = DB::table('node')->where(['pid' => 0])->where('status', '<>', 0)->select(['id', 'name'])->get();
                $sub_node_list=[];
                foreach ($node_list as $k => $vo) {
                    if ($menu_data->node_id == $vo->id) {
                        $node_list[$k]->selected = 'selected';
                    } else {
                        $node_list[$k]->selected = '';
                    }
                    if ($node_list[$k]->selected != '') {
                        $sub_node_list = DB::table('node')->where(['pid' => $vo->id])->where('status', '<>', 0)->select(['id', 'name'])->get();
                        foreach ($sub_node_list as $key => $val) {
                            if ($menu_data->sub_node_id == $val->id) {
                                $sub_node_list[$key]->selected = 'selected';
                            } else {
                                $sub_node_list[$key]->selected = '';
                            }
                        }
                    }
                }
                $ajaxarr = ['code' => 200, 'node_list' => $node_list, 'sub_node_list' => $sub_node_list];
            } else {
                $node_list = DB::table('node')->where(['pid' => 0])->where('status', '<>', 0)->select(['id', 'name'])->get();
                $ajaxarr = ['code' => 200, 'node_list' => $node_list];
            }
        }else{
            $node_id=isset($data['node_id'])?$data['node_id']:'';
            $node_list = DB::table('node')->where(['pid' => $node_id])->where('status', '<>', 0)->select(['id', 'name'])->get();
            $ajaxarr = ['code' => 200, 'node_list' => $node_list];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 添加菜单
     */
    public function addMenu(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'title' => 'required',
            'node_id' => 'required',
            'sub_node_id' => 'required'
        ],['title.required'=>'菜单名称不能为空',
            'node_id.required'=>'节点不能为空',
            'sub_node_id.required'=>'二级节点不能为空'
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            $data['add_time']=time();
            $data['add_id']=1;
            $add_id=DB::table('menu')->insertGetId($data);
            if($add_id){
                $ajaxarr=['code'=>200,'msg'=>'菜单添加成功'];
            }else{
                $ajaxarr=['code'=>400,'msg'=>'菜单添加失败'];
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 编辑节点
     */
    public function editMenu(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'title' => 'required',
                'node_id' => 'required',
                'sub_node_id' => 'required'
            ],['title.required'=>'菜单名称不能为空',
                'node_id.required'=>'节点不能为空',
                'sub_node_id.required'=>'二级节点不能为空'
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                $data['edit_time']=time();
                $data['edit_id']=1;
                $id=$data['id'];
                unset($data['id']);
                $add_id=DB::table('menu')->where(['id'=>$id])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'菜单编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'菜单编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数菜单id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 删除菜单
     */
    public function delMenu(){
        $data=request()->all();
        if(request()->filled('id')){
            $sub_node_num=DB::table('menu')->where(['pid'=>$data['id'],'status'=>1])->count();
            if($sub_node_num > 0){
                $ajaxarr = ['code' => 400, 'msg' => '删除失败，请先删除下级菜单'];
            }else {
                $data['status'] = 0;
                $data['del_time']=time();
                $data['del_id']=1;
                $id=$data['id'];
                unset($data['id']);
                $save_id = DB::table('menu')->where(['id'=>$id])->update($data);
                if ($save_id) {
                    $ajaxarr = ['code' => 200, 'msg' => '删除成功'];
                } else {
                    $ajaxarr = ['code' => 400, 'msg' => '删除失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数菜单id丢失'];
        }
        return response()->json($ajaxarr);
    }
}
