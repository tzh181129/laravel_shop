<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月1日
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Index extends Controller
{
    public function index(){

    }

    public function getMenu(){
        $menu_list=DB::table('menu')->where(['status'=>1,'pid'=>0])->orderBy('sort', 'DESC')->select('id','title','icon','jump')->get();
        foreach ($menu_list as $k=>$vo){
//            $list=DB::table('menu')->where(['status'=>1,'pid'=>$vo->id])->orderBy('sort', 'DESC')->select('title','icon','jump')->get();
//            $menu_list[$k]->list=$list->toArray();
            $menu_list[$k]->list=DB::table('menu')->where(['status'=>1,'pid'=>$vo->id])->orderBy('sort', 'DESC')->select('title','icon','jump')->get();
        }
        $ajaxarr=['code'=>0,'data'=>$menu_list];
        return response()->json($ajaxarr);
    }

}
