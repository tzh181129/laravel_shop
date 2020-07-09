<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月1日
 * 角色
 * 简介：主要用于角色
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Role extends Controller
{

    /**
     * 角色列表
     */
    public function index(){
        $data=request()->all();
        $search=isset($data['search'])?trim($data['search']):'';
        $where=[];
        if($search){
            $where[]=['name','like','%'.$search.'%'];
        }
        $where[]=['status','<>',0];
        $count=DB::table('role')->where($where)->count();
        $roleList=DB::table('role')->where($where)->select()->get();
        foreach ($roleList as $k=>$vo){
            $roleList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
        }
        $ajaxarr=['code'=>0,'count'=>$count,'data'=>$roleList];
        return response()->json($ajaxarr);
    }

    /**
     * 添加角色
     */
    public function addRole(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'name' => 'required'
        ],[
            'name.required'=>'角色名称不能为空'
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            $data['add_time']=time();
            $data['add_id']=1;
            $add_id=DB::table('role')->insertGetId($data);
            if($add_id){
                $ajaxarr=['code'=>200,'msg'=>'角色添加成功'];
            }else{
                $ajaxarr=['code'=>400,'msg'=>'角色添加失败'];
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 编辑角色
     */
    public function editRole(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'name' => 'required'
            ],[
                'name.required'=>'角色名称不能为空'
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                $data['edit_time']=time();
                $data['edit_id']=1;
                $id=$data['id'];
                unset($data['id']);
                $add_id=DB::table('role')->where(['id'=>$id])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'角色编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'角色编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数角色id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 删除角色
     */
    public function delRole(){
        $data=request()->all();
        if(request()->filled('id')){
            $data['status'] = 0;
            $data['del_time']=time();
            $data['del_id']=1;
            $id=$data['id'];
            unset($data['id']);
            $save_id = DB::table('role')->where(['id'=>$id])->update($data);
            if ($save_id) {
                $ajaxarr = ['code' => 200, 'msg' => '删除成功'];
            } else {
                $ajaxarr = ['code' => 400, 'msg' => '删除失败'];
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数节点id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 角色授权
     */
    public function authRole(){
        $data=request()->all();
        if(request()->filled('id')){
            $type=isset($data['type'])?$data['type']:'1';
            if($type == 1){
                //获取当前角色的规则
                $role_node=DB::table('role')->where(['id'=>$data['id']])->value('node');
                $role_node=isset($role_node)?explode(',',$role_node):[];
                $node=DB::table('node')->where('pid','=',0)->where('status','<>',0)->select(['id','name'])->get();
                foreach ($node as $k=>$vo){
                    $sub_node=DB::table('node')->where('pid','=',$vo->id)->where('status','<>',0)->select(['id','name'])->get();
                    foreach ($sub_node as $key=>$val){
                        $sub_node[$key]->checked=in_array($val->id,$role_node)?'checked':'';
                    }
                    $node[$k]->sub_node=$sub_node;
                }
                $ajaxarr=['code'=>200,'node'=>$node];
            }else{
                $data['node']=isset($data['node'])?implode(',',$data['node']):'';
                $data['edit_time']=time();
                $data['edit_id']=1;
                unset($data['type']);
                $id=$data['id'];
                unset($data['id']);
                $add_id=DB::table('role')->where(['id'=>$id])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'角色授权成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'角色授权失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数角色id丢失'];
        }
        return response()->json($ajaxarr);
    }
}
