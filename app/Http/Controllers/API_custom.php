<?php

/**
 * 开发者：浮生若梦
 * 开发日期：2020年7月8日
 * 主要用于小程序接口
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Wechat;

class API_custom extends Controller
{
    /**
     * 获取openid
     */
    public function getOpenid()
    {
        $data = request()->all();
        $validator = Validator::make($data, [
            'code' => 'required'
        ], [
            'code.required' => '微信code丢失'
        ]);
        if ($validator->fails()) {
            $message = $validator->errors();
            $ajaxarr = ['code' => 100, 'msg' => current(current($message))[0]];
        } else {
            $code = isset($data['code']) ? $data['code'] : '';
            $config = DB::table('order_config')->where(['id' => 1])->first();
            $appid = isset($config['appid']) ? $config['appid'] : '';
            $appsecret = isset($config['appsecret']) ? $config['appsecret'] : '';
            $wechat = new Wechat($appid, $appsecret);
            $result = $wechat->getOpenid($code);
            var_dump($result);
            $openid = $result['openid'];
            $custom_id = DB::table('custom')->insertGetId(['openid' => $openid, 'add_time' => time()]);
            $ajaxarr = ['code' => 200, 'openid' => $openid, 'custom_id' => $custom_id];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 小程序首页
     */
    public function home()
    {
        //轮播
        $banner_list = DB::table('banner')->where('status', '<>', 0)->orderBy('sort', 'desc')->select('img_path')->get();
        foreach ($banner_list as $k => $vo) {
            $banner_list[$k]->show_path = $vo->img_path ? asset(Storage::url($vo->img_path)) : '';
        }
        //第三级分类
        $classify_list = DB::table('goods_classify')->where('status', '<>', 0)->where('level', '=', 2)->select('id', 'name', 'img_path')->get();
        foreach ($classify_list as $k => $vo) {
            $classify_list[$k]->show_path = $vo->img_path ? asset(Storage::url($vo->img_path)) : '';
        }
        //热卖
        $goods_list = DB::table('goods')->where('status', '=', 1)->orderBy('sell_stock', 'desc')->limit(4)->select('id', 'name', 'img_path', 'price', 'old_price', 'stock')->get();
        foreach ($goods_list as $k => $vo) {
            $goods_list[$k]->show_path = $vo->img_path ? asset(Storage::url($vo->img_path)) : '';
        }
        //推荐
        $recommend_goods_list = DB::table('goods')->where('status', '=', 1)->limit(16)->select('id', 'name', 'img_path', 'price', 'old_price', 'stock')->get();
        foreach ($recommend_goods_list as $k => $vo) {
            $recommend_goods_list[$k]->show_path = $vo->img_path ? asset(Storage::url($vo->img_path)) : '';
        }
        $ajaxarr = ['banner_list' => $banner_list, 'classify_list' => $classify_list, 'goods_list' => $goods_list, 'recommend_goods_list' => $recommend_goods_list];
        return response()->json($ajaxarr);
    }

    /**
     * 分类
     */
    public function classify_list()
    {

    }

    /**
     * 商品列表
     */
    public function goods_list()
    {

    }

    /**
     * 商品详情
     */
    public function goods_detail()
    {

    }

    /**
     * 购物车
     */
    public function goods_cart()
    {
        $data = request()->all();
        if (request()->filled('custom_id')) {
            $cart_list = DB::table('order_cart')->where(['custom_id' => $data['custom_id'], 'status' => 1])->select('id', 'custom_id', 'goods_id', 'num')->get();
            foreach ($cart_list as $k => $vo) {
                $goods_data = DB::table('goods')->where(['id' => $vo->goods_id])->select('name', 'img_path', 'old_price', 'price', 'stock')->first();
                $goods_data['img_path'] = $goods_data['img_path'] ? asset(Storage::url($goods_data['img_path'])) : '';
            }
            //推荐商品
            $recommend_goods_list = DB::table('goods')->where('status', '=', 1)->limit(16)->select('id', 'name', 'img_path', 'price', 'old_price', 'stock')->get();
            foreach ($recommend_goods_list as $k => $vo) {
                $recommend_goods_list[$k]->show_path = $vo->img_path ? asset(Storage::url($vo->img_path)) : '';
            }
            $ajaxarr = ['code' => 200, 'cart_list' => $cart_list, 'recommend_goods_list' => $recommend_goods_list];
        } else {
            $ajaxarr = ['code' => 100, 'msg' => '客户id未获取到'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 更新客户资料 主要用于获取头像和昵称
     */
    public function update_informate()
    {
        $data = request()->all();
        if (request()->filled('custom_id')) {
            $save_id = DB::table('custom')->where(['id' => $data['custom_id']])->update(['wx_name' => $data['username'], 'wx_headimg' => $data['headimg'], 'edit_time' => time()]);
            if ($save_id) {
                $ajaxarr = ['code' => 200, 'msg' => '资料更新成功'];
            } else {
                $ajaxarr = ['code' => 100, 'msg' => '资料更新失败'];
            }
        } else {
            $ajaxarr = ['code' => 100, 'msg' => '客户id未获取到'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 我的订单
     * type 分类 2 待发货 3待收货 4 待评价 5 已完成
     */
    public function my_order()
    {
        $data = request()->all();
        if (request()->filled('custom_id')) {
            $type = isset($data['type']) ? $data['type'] : '1';
            $page = isset($data['page']) ? $data['page'] : '0';
            $limit = 10;
            $evaluate_order_ids = DB::table('evaluate')->where(['custom_id' => $data['custom_id']])->pluck('order_id', true) ? DB::table('evaluate')->where(['custom_id' => $data['custom_id']])->pluck('order_id', true) : [];
            if ($type == 2) {
                $where[] = ['status', '=', 2];
            } elseif ($type == 3) {
                $where[] = ['status', '=', 4];
            } elseif ($type == 4) {
                $where[] = ['status', '=', 5];
                $where[] = ['id', 'not in', implode(',', $evaluate_order_ids)];
            } elseif ($type == 5) {
                $where[] = ['id', 'in', implode(',', $evaluate_order_ids)];
            }
            $orderList = DB::table('order')->where($where)->paginate((int)$limit, '*', '', (int)$page);
            foreach ($orderList as $k => $vo) {
                $goods = DB::table('order_goods')->where(['order_id' => $vo->id])->where('status', '=', 1)->select('goods_id', 'goods_name', 'goods_img_path', 'goods_price', 'num')->get();
                foreach ($goods as $key => $val) {
                    $goods[$k]->show_path = $vo->goods_img_path ? asset(Storage::url($vo->goods_img_path)) : '';
                }
                $orderList->goods = $goods;
            }
            $ajaxarr = ['code' => 200, 'orderList' => $orderList];
        } else {
            $ajaxarr = ['code' => 100, 'msg' => '客户id未获取到'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $data = request()->all();
        if (request()->filled('order_id')) {
            $order_data = DB::table('order')->where(['id' => $data['order_id']])->first();
            $goods_list = DB::table('order_goods')->where(['order_id' => $data['order_id']])->where('status', '=', 1)->select('goods_id', 'goods_name', 'goods_img_path', 'goods_price', 'num')->get();
            $order_data['goods_list'] = $goods_list;
            $evaluate_data = DB::table('evaluate')->where(['order_id' => $data['order_id']])->first();
            if ($evaluate_data) {
                $order_data['status_title'] = '已完成';
            } else {
                if ($order_data['status'] == 2) {
                    $order_data['status_title'] = '待发货';
                } elseif ($order_data['status'] == 4) {
                    $order_data['status_title'] = '待收货';
                } else {
                    $order_data['status_title'] = '待评价';
                }
            }
            $order_data['evaluate_data'] = $evaluate_data;
            $ajaxarr = ['code' => 200, 'order_data' => $order_data];
        } else {
            $ajaxarr = ['code' => 100, 'msg' => '订单id未获取到'];
        }
        return response()->json($ajaxarr);
    }

    /**
     * 收货 评价
     * type  1 收货 2 评价
     */
    public function receive_assess()
    {
        $data = request()->all();
        if (request()->filled('order_id')) {
            $type = isset($data['type']) ? $data['type'] : 1;
            if ($type == 2) {
                $save_id = DB::table('order')->where(['id' => $data['order_id']])->update(['status' => 5, 'receipt_time' => time()]);
                if ($save_id) {
                    $ajaxarr = ['code' => 200, 'msg' => '收货成功'];
                } else {
                    $ajaxarr = ['code' => 400, 'msg' => '收货失败'];
                }
            } else {
                $validator = Validator::make($data, [
                    'score' => 'required',
                    'custom_id' => 'required'
                ], [
                    'score.required' => '评分不能为空',
                    'custom_id.required' => '客户id丢失'
                ]);
                if ($validator->fails()) {
                    $message = $validator->errors();
                    $ajaxarr = ['code' => 100, 'msg' => current(current($message))[0]];
                } else {
                    if (DB::table('evaluate')->where(['order_id' => $data['order_id']])->first()) {
                        $save_id = DB::table('evaluate')->where(['order_id' => $data['order_id']])->update(['score' => $data['score'], 'evaluate' => $data['evaluate'], 'evaluate_img' => $data['evaluate_img'], 'edit_time' => time()]);
                    } else {
                        $save_id = DB::table('evaluate')->insertGetId(['order_id' => $data['order_id'], 'custom_id' => $data['custom_id'], 'score' => $data['score'], 'evaluate' => $data['evaluate'], 'evaluate_img' => $data['evaluate_img'], 'add_time' => time()]);
                    }
                    if ($save_id) {
                        $ajaxarr = ['code' => 200, 'msg' => '评价成功'];
                    } else {
                        $ajaxarr = ['code' => 400, 'msg' => '评价失败'];
                    }
                }
            }
        } else {
            $ajaxarr = ['code' => 100, 'msg' => '订单id未获取到'];
        }
        return response()->json($ajaxarr);
    }
}
