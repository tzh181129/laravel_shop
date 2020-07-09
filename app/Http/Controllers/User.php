<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月1日
 * 账户
 * 简介：主要用于账户管理
 */

namespace App\Http\Controllers;

use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class User extends Controller
{

    /**
     * 账户列表
     */
    public function index(){
        $data=request()->all();
        $username=isset($data['username'])?trim($data['username']):'';
        $where=[];
        if($username){
            $where[]=['username','like','%'.$username.'%'];
        }
        $telphone=isset($data['telphone'])?$data['telphone']:'';
        if($telphone){
            $where[]=['telphone','like','%'.$telphone.'%'];
        }
        $status=isset($data['status'])?$data['status']:'';
        if($status){
            $where['status']=$status;
        }else{
            $where[]=['status','<>',0];
        }
        $page=isset($data['page'])?$data['page']:'0';
        $limit=10;
        $count=DB::table('user')->where($where)->count();
        $userList=DB::table('user')->where($where)->paginate((int)$limit,'*','',(int)$page);
        foreach ($userList as $k=>$vo){
            $userList[$k]->end_time=$vo->end_time?date('Y-m-d',$vo->end_time):'';
            $userList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
            $userList[$k]->show_path=$vo->headimg?asset(Storage::url($vo->headimg)):'';
        }
        $ajaxarr=['code'=>0,'count'=>$count,'data'=>$userList->items()];
        return response()->json($ajaxarr);
    }

    /**
     * 获取角色列表
     */
    public function select_role(){
        $data=request()->all();
        $role=isset($data['role'])?explode(',',$data['role']):'';
        $role_list=DB::table('role')->where('status','<>',0)->select('id as value','name')->get();
        if($role){
            foreach ($role_list as $k=>$vo){
                if(in_array($vo->value,$role)){
                    $role_list[$k]->selected=true;
                }
            }
        }
        return response()->json($role_list);
    }

    /**
     * 上传
     */
    public function upload(){
        $data=request()->all();
        $type=isset($data['type'])?$data['type']:'1';
        if($type == 1) {
            $path = request()->file('file')->store('/public/upload/avatar/' . date('Y-m-d'));
            $avatar = Storage::url($path);//就是很简单的一个步骤
        }else{
            $path = request()->file('file')->store('/public/upload/contract/' . date('Y-m-d'));
            $avatar = Storage::url($path);//就是很简单的一个步骤
        }
        $ajaxarr=['code'=>0,'show_path'=>asset($avatar),'path'=>$path];
        return  response()->json($ajaxarr);
    }

    /**
     * 添加账户
     */
    public function addUser(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'username' => 'required',
            'headimg' => 'required',
            'password' => 'required',
            'truename' => 'required',
            'telphone' => 'required',
            'address' => 'required',
            'contract' => 'required',
            'select' => 'required',
            'end_time' => 'required'
        ],['username.required'=>'账户名称不能为空',
            'headimg.required'=>'账户头像不能为空',
            'password.required'=>'账户密码不能为空',
            'truename.required'=>'账户真实姓名不能为空',
            'telphone.required'=>'账户电话号码不能为空',
            'address.required'=>'账户地址不能为空',
            'contract.required'=>'合同文件不能为空',
            'select.required'=>'账户所属角色不能为空',
            'end_time.required'=>'账户到期时间不能为空',
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            $data['salt']=rand('100000','999999');
            $data['password']=md5($data['salt'].$data['password']);
            $data['end_time']=strtotime($data['end_time']);
            $data['add_time']=time();
            $data['add_id']=1;
            $data['role']=$data['select'];
            unset($data['file']);
            unset($data['select']);
            $add_id=DB::table('user')->insertGetId($data);
            if($add_id){
                $ajaxarr=['code'=>200,'msg'=>'账户添加成功'];
            }else{
                $ajaxarr=['code'=>400,'msg'=>'账户添加失败'];
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 编辑账户
     */
    public function editUser(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'username' => 'required',
                'headimg' => 'required',
                'truename' => 'required',
                'telphone' => 'required',
                'address' => 'required',
                'contract' => 'required',
                'select' => 'required',
                'end_time' => 'required'
            ],['username.required'=>'账户名称不能为空',
                'headimg.required'=>'账户头像不能为空',
                'truename.required'=>'账户真实姓名不能为空',
                'telphone.required'=>'账户电话号码不能为空',
                'address.required'=>'账户地址不能为空',
                'contract.required'=>'合同文件不能为空',
                'select.required'=>'账户所属角色不能为空',
                'end_time.required'=>'账户到期时间不能为空',
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                $data['end_time']=strtotime($data['end_time']);
                $data['role']=$data['select'];
                $data['edit_time']=time();
                $data['edit_id']=1;
                if($data['password'] && $data['password'] != ''){
                    $data['salt']=rand('100000','999999');
                    $data['password']=md5($data['salt'].$data['password']);
                }
                unset($data['file']);
                unset($data['select']);
                $id=$data['id'];
                unset($data['id']);
                $add_id=DB::table('user')->where(['id'=>$id])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'账户编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'账户编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数账户id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 删除或停用账户
     * type == 1 停用 2 恢复 3 删除
     */
    public function delUser(){
        $data=request()->all();
        if(request()->filled('id')){
            $type=isset($data['type'])?$data['type']:'1';
            if($type == 1){
                $update['status'] = 2;
                $update['stop_time']=time();
                $update['stop_id']=1;
            }elseif ($type == 2){
                $update['status'] = 1;
                $update['edit_time']=time();
                $update['edit_id']=1;
            }else {
                $update['status'] = 0;
                $update['del_time'] = time();
                $update['del_id'] = 1;
            }
            $save_id = DB::table('user')->where(['id'=>$data['id']])->update($update);
            if ($save_id) {
                $ajaxarr = ['code' => 200, 'msg' => '操作成功'];
            } else {
                $ajaxarr = ['code' => 400, 'msg' => '操作失败'];
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数账户id丢失'];
        }
        return response()->json($ajaxarr);
    }
}
