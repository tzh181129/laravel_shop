<?php

/**
 * 开发者：浮生若梦
 * 开发时间：2020年7月2日
 * 主要用于购物平台的商品管理
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Goods extends Controller
{
    /**
     * 商品分类管理
     */
    public function classify_list(){
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
            $classifyList=DB::table('goods_classify')->where($where)->select()->get();
            foreach ($classifyList as $k=>$vo){
                $classifyList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
                $classifyList[$k]->p_name=DB::table('goods_classify')->where(['id'=>$vo->pid])->where('status','<>',0)->value('name')?DB::table('goods_classify')->where(['id'=>$vo->pid])->where('status','<>',0)->value('name'):'';
                $classifyList[$k]->haveChild=DB::table('goods_classify')->where(['pid'=>$vo->id])->where('status','<>',0)->count()?true:false;
                if($vo->level == 2){
                    $classifyList[$k]->show_path=$vo->img_path?asset(Storage::url($vo->img_path)):'';
                }
            }
        }else{
            $where[]=['pid','=','0'];
            $classifyList=DB::table('goods_classify')->where($where)->select()->get();
            foreach ($classifyList as $k=>$vo){
                $classifyList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
                $classifyList[$k]->haveChild=DB::table('goods_classify')->where(['pid'=>$vo->id])->where('status','<>',0)->count()?true:false;
                $classifyList[$k]->p_name='';
            }
        }
        $ajaxarr=['code'=>0,'data'=>$classifyList];
        return response()->json($ajaxarr);
    }

    /**
     * 选择分类
     */
    public function select_classify(){
        $data=request()->all();
        $classify_id=isset($data['classify_id'])?$data['classify_id']:'';
        $type=isset($data['type'])?$data['type']:'1';     //1为分类的选择上级分类  2为商品的选择分类
        if($type == 1) {
            $classify_list = DB::table('goods_classify')->where('status', '<>', 0)->where('pid', '=', 0)->select('id', 'name')->get();
            foreach ($classify_list as $k => $vo) {
                if ($vo->id == $classify_id) {
                    $classify_list[$k]->selected = 'selected';
                } else {
                    $classify_list[$k]->selected = '';
                }

                $sub_classify_list = DB::table('goods_classify')->where('status', '<>', 0)->where('pid', '=', $vo->id)->select('id', 'name')->get();
                foreach ($sub_classify_list as $key => $val) {
                    if ($val->id == $classify_id) {
                        $sub_classify_list[$key]->selected = 'selected';
                    } else {
                        $sub_classify_list[$key]->selected = '';
                    }
                }
                $classify_list[$k]->sub_classify_list = $sub_classify_list;
            }
        }else{
            $classify_data=DB::table('goods_classify')->where('status','<>',0)->select('id','pid','name')->get();
            $classify_list=DB::table('goods_classify')->where('status', '<>', 0)->where('pid', '<>', 0)->where('level','=',2)->select('id','pid', 'name')->get();
            foreach ($classify_list as $k=>$vo){
                if ($vo->id == $classify_id) {
                    $classify_list[$k]->selected = 'selected';
                } else {
                    $classify_list[$k]->selected = '';
                }
                //获取当前分类所属所有上级分类
                $classify_ids=$this->get_top_pid($classify_data,$vo->pid);
                $classify_ids[]=$vo->id;
                $classify_name=DB::table('goods_classify')->whereIn('id',$classify_ids)->where('status','<>',0)->pluck('name')?DB::table('goods_classify')->whereIn('id',$classify_ids)->where('status','<>',0)->pluck('name')->toArray():[];
                $classify_list[$k]->classify_name = implode('-',$classify_name);
            }
        }
        return response()->json($classify_list);
    }

    /**
     * 用递归获根据id获取所有的上级
     * @param $cate
     * @param $id
     * @return array
     */
     public function get_top_pid($cate,$pid){
        $arr=array();
        foreach($cate as $v){
            if($v->id==$pid){
                $arr[]=$v->id;
                $arr=array_merge($this->get_top_pid($cate,$v->pid),$arr);
            }
        }
        return $arr;
    }

    /**
     * 添加商品分类
     */
    public function addClassify(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'name' => 'required'
        ],[
            'name.required'=>'分类名称不能为空'
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            if($data['pid'] == 0){
                $data['level']=0;
            }else{
                $data['level']=DB::table('goods_classify')->where(['id'=>$data['pid']])->value('level')+1;
            }
            $img_path=isset($data['img_path'])?trim($data['img_path']):'';
            if($data['level'] == 2 && $img_path == ''){
                $ajaxarr=['code'=>100,'msg'=>'分类图片不能为空'];
            }else{
                $data['user_id']=1;
                $data['add_time']=time();
                $data['add_id']=1;
                unset($data['file']);
                $add_id=DB::table('goods_classify')->insertGetId($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'分类添加成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'分类添加失败'];
                }
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 编辑商品分类
     */
    public function editClassify(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'name' => 'required'
            ],[
                'name.required'=>'分类名称不能为空'
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                if($data['pid'] == 0){
                    $data['level']=0;
                }else{
                    $data['level']=DB::table('goods_classify')->where(['id'=>$data['pid']])->value('level')+1;
                }
                $data['edit_time']=time();
                $data['edit_id']=1;
                $id=$data['id'];
                unset($data['id']);
                $add_id=DB::table('goods_classify')->where(['id'=>$id])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'分类编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'分类编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数角色id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 删除商品分类
     */
    public function delClassify(){
        $data=request()->all();
        if(request()->filled('id')){
            $sub_classify_num=DB::table('goods_classify')->where(['pid'=>$data['id'],'status'=>1])->count();
            if($sub_classify_num > 0){
                $ajaxarr = ['code' => 400, 'msg' => '删除失败，请先删除下级分类'];
            }else {
                //判断分类下商品
                $goods_num=DB::table('goods')->where(['classif_id'=>$data['id']])->where('status','<>',0)->count();
                if($goods_num > 0){
                    $ajaxarr = ['code' => 400, 'msg' => '删除失败，请先删除分类下商品'];
                }else {
                    $data['status'] = 0;
                    $data['del_time'] = time();
                    $data['del_id'] = 1;
                    $id = $data['id'];
                    unset($data['id']);
                    $save_id = DB::table('goods_classify')->where(['id' => $id])->update($data);
                    if ($save_id) {
                        $ajaxarr = ['code' => 200, 'msg' => '删除成功'];
                    } else {
                        $ajaxarr = ['code' => 400, 'msg' => '删除失败'];
                    }
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数节点id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 商品管理
     */
    public function goods_list(){
        $data=request()->all();
        $search=isset($data['search'])?trim($data['search']):'';
        $where=[];
        if($search){
            $where[]=['name','like','%'.$search.'%'];
        }
        $id=isset($data['id'])?$data['id']:'';
        if($id){
            $where[]=['id','=',$id];
        }
        $status=isset($data['status'])?$data['status']:'';
        if($status){
            $where['status']=$status;
        }else{
            $where[]=['status','<>',0];
        }
        $page=isset($data['page'])?$data['page']:'0';
        $limit=10;
        $count=DB::table('goods')->where($where)->count();
        $goodsList=DB::table('goods')->where($where)->paginate((int)$limit,'*','',(int)$page);
        foreach ($goodsList as $k=>$vo){
            $goodsList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
            $goodsList[$k]->show_path=$vo->img_path?asset(Storage::url($vo->img_path)):'';
            $goodsList[$k]->classify_name=DB::table('goods_classify')->where(['id'=>$vo->classify_id,'status'=>1])->value('name')?DB::table('goods_classify')->where(['id'=>$vo->classify_id,'status'=>1])->value('name'):'';
            $banner_path=$vo->banner_path?explode(',',$vo->banner_path):[];
            $banner_show_path=[];
            foreach ($banner_path as $key=>$val){
                $banner_show_path[$key]['show_path']=asset(Storage::url($val));
                $banner_show_path[$key]['banner_path']=$val;
            }
            $goodsList[$k]->banner_show_path=$banner_show_path;
        }
        $ajaxarr=['code'=>0,'count'=>$count,'data'=>$goodsList->items()];
        return response()->json($ajaxarr);
    }

    /**
     * 添加商品
     */
    public function addGoods(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'name' => 'required',
            'classify_id' => 'required',
            'img_path' => 'required',
            'price' => 'required',
            'stock' => 'required'
        ],['name.required'=>'商品名称不能为空',
            'classify_id.required'=>'商品所属分类不能为空',
            'img_path.required'=>'商品截图不能为空',
            'price.required'=>'商品现价不能为空',
            'stock.required'=>'商品现有库存不能为空',
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            $data['banner_path']=isset($data['banner_path'])?implode(',',$data['banner_path']):'';
            $data['add_time']=time();
            $data['add_id']=1;
            $data['user_id']=1;
            unset($data['file']);
            $add_id=DB::table('goods')->insertGetId($data);
            if($add_id){
                $ajaxarr=['code'=>200,'msg'=>'商品添加成功'];
            }else{
                $ajaxarr=['code'=>400,'msg'=>'商品添加失败'];
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 上传图片
     */
    public function upload_good(){
        $data=request()->all();
        $type=isset($data['type'])?$data['type']:'1';
        if($type == 1) {
            $path = request()->file('file')->store('/public/upload/classify/' . date('Y-m-d'));
            $avatar = Storage::url($path);//就是很简单的一个步骤
        }else{
            $path = request()->file('file')->store('/public/upload/goods/' . date('Y-m-d'));
            $avatar = Storage::url($path);//就是很简单的一个步骤
        }
        $ajaxarr=['code'=>0,'show_path'=>asset($avatar),'path'=>$path,'data'=>['src'=>asset($avatar)]];
        return  response()->json($ajaxarr);
    }

    /**
     * 编辑图片
     */
    public function editGoods(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'name' => 'required',
                'classify_id' => 'required',
                'img_path' => 'required',
                'price' => 'required',
                'stock' => 'required'
            ],['name.required'=>'商品名称不能为空',
                'classify_id.required'=>'商品所属分类不能为空',
                'img_path.required'=>'商品截图不能为空',
                'price.required'=>'商品现价不能为空',
                'stock.required'=>'商品现有库存不能为空',
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                $data['banner_path']=isset($data['banner_path'])?implode(',',$data['banner_path']):'';
                $data['edit_time']=time();
                $data['edit_id']=1;
                unset($data['id']);
                unset($data['file']);
                $add_id=DB::table('goods')->where(['id'=>request()->all('id')])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'商品编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'商品编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数商品id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 上架下架删除商品
     *
     */
    public function delGoods(){
        $data=request()->all();
        if(request()->filled('id')){
            $type=isset($data['type'])?$data['type']:'1';    //type 1 下架 2 上架 3删除
            if($type == 1){
                $update['status'] = 2;
                $update['edit_time']=time();
                $update['edit_id']=1;
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
            $ajaxarr=['code'=>100,'msg'=>'参数商品id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 购买记录
     */
    public function pay_list(){
        $data=request()->all();
        $search=isset($data['search'])?trim($data['search']):'';
        $where=[];
        if($search){
            $where[]=['body','like','%'.$search.'%'];
        }
        $status=isset($data['status'])?$data['status']:'';
        if($status){
            if($status == 2) {       //支付成功
                $where['status'] = 2;
            }else if ($status == 3){    //已发货
                $where[]=['status','=',4];
            }else if($status == 4){     //已收货
                $where[]=['status','=',5];
            }
        }else{
            $where[]=['status','=',2];
        }
        $page=isset($data['page'])?$data['page']:'0';
        $limit=10;
        $count=DB::table('order')->where($where)->count();
        $orderList=DB::table('order')->where($where)->orderBy('order_time','desc')->paginate((int)$limit,'*','',(int)$page);
        foreach ($orderList as $k=>$vo){
            $orderList[$k]->add_time=isset($vo->add_time)?date('Y-m-d H:i',$vo->add_time):'';
            $orderList[$k]->order_time=isset($vo->order_time)?date('Y-m-d H:i',$vo->order_time):'';
            if($vo->status == 2){
                $orderList[$k]->status_title='<font color="#7fffd4">已支付</font>';
            }elseif ($vo->status == 4){
                $orderList[$k]->status_title='<font color="#f08080">已发货</font>';
            }elseif ($vo->status == 5){
                $orderList[$k]->status_title='<font color="red">已收货</font>';
            }
        }
        $ajaxarr=['code'=>0,'count'=>$count,'data'=>$orderList->items()];
        return response()->json($ajaxarr);
    }

    /**
     * 订单详情
     */
    public function order_detail(){

    }

    /**
     * 支付配置
     */
    public function order_config(){
        $data=request()->all();
        $user_id = 1;
        $type=isset($data['type'])?$data['type']:1;
        if($type == 1) {
            $orderConfig = DB::table('order_config')->where(['user_id' => $user_id])->first() ? DB::table('order_config')->where(['user_id' => $user_id])->first() : [];
            return response()->json($orderConfig);
        }else{
            $validator = Validator::make($data, [
                'appid' => 'required',
                'appsecret' => 'required',
                'mch_id' => 'required',
                'key' => 'required'
            ],['appid.required'=>'微信小程序Appid不能为空',
                'appsecret.required'=>'微信小程序appsecret不能为空',
                'mch_id.required'=>'商户号mch_id不能为空',
                'key.required'=>'商户号key不能为空'
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                unset($data['type']);
                if(DB::table('order_config')->where(['user_id' => $user_id])->first()){
                    $data['edit_id']=$user_id;
                    $data['edit_time']=time();
                    $add_id=DB::table('order_config')->where(['user_id'=>$user_id])->update($data);
                }else{
                    $data['user_id']=$user_id;
                    $data['add_id']=$user_id;
                    $data['add_time']=time();
                    $add_id=DB::table('order_config')->insertGetId($data);
                }
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'操作成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'操作失败'];
                }
            }
            return response()->json($ajaxarr);
        }
    }

    /**
     * 轮播管理
     */
    public function banner_list(){
        $data=request()->all();
        $page=isset($data['page'])?$data['page']:'0';
        $limit=10;
        $count=DB::table('banner')->where('status','<>',0)->count();
        $bannerList=DB::table('banner')->where('status','<>',0)->paginate((int)$limit,'*','',(int)$page);
        foreach ($bannerList as $k=>$vo){
            $bannerList[$k]->add_time=$vo->add_time?date('Y-m-d',$vo->add_time):'';
            $bannerList[$k]->show_path=$vo->img_path?asset(Storage::url($vo->img_path)):'';
        }
        $ajaxarr=['code'=>0,'count'=>$count,'data'=>$bannerList->items()];
        return response()->json($ajaxarr);
    }

    /**
     * 添加轮播
     */
    public function addBanner(){
        $data=request()->all();
        $validator = Validator::make($data, [
            'img_path' => 'required'
        ],[
            'img_path.required'=>'轮播图不能为空'
        ]);
        if ($validator->fails()) {
            $message=$validator->errors();
            $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
        }else{
            $data['add_time']=time();
            $data['add_id']=1;
            unset($data['file']);
            $add_id=DB::table('banner')->insertGetId($data);
            if($add_id){
                $ajaxarr=['code'=>200,'msg'=>'添加成功'];
            }else{
                $ajaxarr=['code'=>400,'msg'=>'添加失败'];
            }
        }
        return response()->json($ajaxarr);
    }

    /**
     * 编辑轮播
     */
    public function editBanner(){
        $data=request()->all();
        if(request()->filled('id')){
            $validator = Validator::make($data, [
                'img_path' => 'required'
            ],[
                'img_path.required'=>'轮播图不能为空'
            ]);
            if ($validator->fails()) {
                $message=$validator->errors();
                $ajaxarr=['code'=>100,'msg'=>current(current($message))[0]];
            }else{
                $data['edit_time']=time();
                $data['edit_id']=1;
                unset($data['id']);
                unset($data['file']);
                $add_id=DB::table('banner')->where(['id'=>request()->all('id')])->update($data);
                if($add_id){
                    $ajaxarr=['code'=>200,'msg'=>'编辑成功'];
                }else{
                    $ajaxarr=['code'=>400,'msg'=>'编辑失败'];
                }
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数轮播图id丢失'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 删除轮播
     */
    public function delBanner(){
        $data=request()->all();
        if(request()->filled('id')){
            $data['status'] = 0;
            $data['del_time'] = time();
            $data['del_id'] = 1;
            $id = $data['id'];
            unset($data['id']);
            $save_id = DB::table('banner')->where(['id' => $id])->update($data);
            if ($save_id) {
                $ajaxarr = ['code' => 200, 'msg' => '删除成功'];
            } else {
                $ajaxarr = ['code' => 400, 'msg' => '删除失败'];
            }
        }else{
            $ajaxarr=['code'=>100,'msg'=>'参数轮播图id丢失'];
        }
        return response()->json($ajaxarr);
    }
}
