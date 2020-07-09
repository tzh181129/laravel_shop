<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', 'Login@index');                    //登录
Route::get('/getMenu', 'Index@getMenu');                //获取菜单
Route::get('/user_list', 'User@index');                 //账户管理列表
Route::get('/select_role', 'User@select_role');         //选择角色列表
Route::post('/upload_user', 'User@upload');             //上传
Route::get('/addUser', 'User@addUser');                 //添加账户
Route::get('/delUser', 'User@delUser');                 //账户状态操作
Route::get('/editUser', 'User@editUser');               //账户状态操作

Route::get('/node_list', 'Node@index');                 //节点列表
Route::get('/select_node', 'Node@select_node');         //选择节点
Route::get('/addNode', 'Node@addNode');                 //添加节点
Route::get('/editNode', 'Node@editNode');               //编辑节点
Route::get('/delNode', 'Node@delNode');                 //删除节点

Route::get('/role_list','Role@index');                  //角色列表
Route::get('/addRole','Role@addRole');                  //添加角色
Route::get('/editRole', 'Role@editRole');               //编辑角色
Route::get('/authRole', 'Role@authRole');               //角色授权
Route::get('/delRole', 'Role@delRole');                 //删除角色

Route::get('/menu_list', 'Menu@index');                 //菜单列表
Route::get('/select_menu', 'Menu@select_menu');         //选择菜单
Route::get('/select_menu_node', 'Menu@select_node');    //菜单选择节点
Route::get('/addMenu', 'Menu@addMenu');                 //添加菜单
Route::get('/editMenu', 'Menu@editMenu');               //编辑菜单
Route::get('/delMenu', 'Menu@delMenu');                 //删除菜单

Route::get('/classify_list','Goods@classify_list');     //商品分类管理
Route::get('/select_classify','Goods@select_classify'); //商品分类管理
Route::get('/addClassify','Goods@addClassify');         //添加商品分类
Route::get('/editClassify', 'Goods@editClassify');      //编辑商品分类
Route::get('/delClassify', 'Goods@delClassify');        //删除商品分类

Route::get('/goods_list','Goods@goods_list');           //商品管理
Route::post('/upload_good','Goods@upload_good');         //添加商品
Route::get('/addGoods','Goods@addGoods');               //添加商品
Route::get('/editGoods', 'Goods@editGoods');            //编辑商品
Route::get('/delGoods', 'Goods@delGoods');              //上架下架删除商品

Route::get('/pay_list','Goods@pay_list');           //购买记录
Route::get('/order_detail','Goods@order_detail');           //查看订单详情
Route::get('/order_config','Goods@order_config');           //支付配置

Route::get('/banner_list','Goods@banner_list');           //轮播管理
Route::get('/addBanner','Goods@addBanner');               //添加轮播
Route::get('/editBanner', 'Goods@editBanner');            //编辑轮播
Route::get('/delBanner', 'Goods@delBanner');              //删除轮播
