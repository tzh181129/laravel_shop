<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月1日
 * 登录
 * 简介：主要用于登录及获取菜单等
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;

class Login extends Controller
{
    /**
     * 登录
     */
    public function index(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'username' => 'required',
            'password' => 'required'
        ],['username.required'=>'账户名称不能为空',
            'password.required'=>'账户密码不能为空',
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['state'=>100,'messge'=>current(current($message))[0]];
        }else{
            $user_data=DB::table('user')->where(['username'=>$data['username']])->where('status','<>',0)->first();
            if($user_data){
                $user_data=get_object_vars($user_data);
                if($user_data['password'] == md5($data['password'])){
                    if($user_data['end_time'] > time()){
                        if($user_data['status'] == 1){
                            $jwt=new JWT();
                            $payload = [
                                "iss"=>"",  //签发者 可以为空
                                "iat" => time(), //签发时间
                                "nbf" => time(), //在什么时候jwt开始生效  （这里表示生成100秒后才生效）
                                "exp" => time()+7200, //token 过期时间
                                "sub" => $user_data['id'] //该JWT所面向的用户
                            ];
                            $token=$jwt->encode($payload,'tzh181129');
                            $ajaxarr=['state'=>200,'token'=>$token,'message'=>'登录成功'];
                        }else{
                            $ajaxarr=['state'=>400,'message'=>'账号已停用'];
                        }
                    }else{
                        $ajaxarr=['state'=>400,'message'=>'账号已到期'];
                    }
                }else {
                    $ajaxarr = ['state' => 400, 'message' => '密码错误'];
                }
            }else{
                $ajaxarr=['state'=>400,'message'=>'账户不存在'];
            }
        }
        return response()->json($ajaxarr);
    }
}


