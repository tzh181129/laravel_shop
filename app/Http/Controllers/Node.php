<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月1日
 * 节点
 * 简介：主要用于权限节点
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Node extends Controller
{

    /**
     * 节点显示
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
            $count=DB::table('node')->where($where)->count();
            $nodeList=DB::table('node')->where($where)->select()->get();
            foreach ($nodeList as $k=>$vo){
                $nodeList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
                $nodeList[$k]->p_name=DB::table('node')->where(['id'=>$vo->pid])->where('status','<>',0)->value('name')?DB::table('node')->where(['id'=>$vo->pid])->where('status','<>',0)->value('name'):'';
            }
        }else{
            $where[]=['pid','=','0'];
            $count=DB::table('node')->where($where)->count();
            $nodeList=DB::table('node')->where($where)->select()->get();
            foreach ($nodeList as $k=>$vo){
                $nodeList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
                $nodeList[$k]->haveChild=DB::table('node')->where(['pid'=>$vo->id])->where('status','<>',0)->count()?true:false;
                $nodeList[$k]->p_name='';
            }
        }
        $ajaxarr=['code'=>0,'data'=>$nodeList];
        return response()->json($ajaxarr);
    }

    /**
     * 选择节点
     */
    public function select_node(){
        $data=request()->all();
        $node_id=isset($data['node_id'])?explode(',',$data['node_id']):'';
        $node_list=DB::table('node')->where('status','<>',0)->where('pid','=',0)->select('id','name')->get();
        if($node_id){
            foreach ($node_list as $k=>$vo){
                if(in_array($vo->id,$node_id)){
                    $node_list[$k]->selected='selected';
                }else{
                    $node_list[$k]->selected='';
                }
            }
        }
        return response()->json($node_list);
    }

    /**
     * 添加节点
     */
    public function addNode(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'name' => 'required',
            'link' => 'required'
        ],['name.required'=>'节点名称不能为空',
            'link.required'=>'节点链接不能为空'
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            $data['add_time']=time();
            $data['add_id']=1;
            $add_id=DB::table('node')->insertGetId($data);
            if($add_id){
                $ajaxarr=['code'=>200,'msg'=>'节点添加成功'];
            }else{
                $ajaxarr=['code'=>400,'msg'=>'节点添加失败'];
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 编辑节点
     */
    public function editNode(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'name' => 'required',
                'link' => 'required'
            ],['name.required'=>'节点名称不能为空',
                'link.required'=>'节点链接不能为空'
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                $data['edit_time']=time();
                $data['edit_id']=1;
                unset($data['id']);
                $add_id=DB::table('node')->where(['id'=>request()->all('id')])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'节点编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'节点编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数节点id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 删除节点
     */
    public function delNode(){
        $data=request()->all();
        if(request()->filled('id')){
            $sub_node_num=DB::table('node')->where(['pid'=>$data['id'],'status'=>1])->count();
            if($sub_node_num > 0){
                $ajaxarr = ['code' => 400, 'msg' => '删除失败，请先删除下级节点'];
            }else {
                $data['status'] = 0;
                $data['del_time']=time();
                $data['del_id']=1;
                unset($data['id']);
                $save_id = DB::table('node')->where(['id'=>request()->all('id')])->update($data);
                if ($save_id) {
                    $ajaxarr = ['code' => 200, 'msg' => '删除成功'];
                } else {
                    $ajaxarr = ['code' => 400, 'msg' => '删除失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数节点id丢失'];
        }
        return response()->json($ajaxarr);
    }
}
