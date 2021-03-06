<?php
/**
 * Created by PhpStorm.
 * User: wunan
 * Date: 2017/6/1
 * Time: 上午11:45
 */


Route::group(['prefix'=> 'admin','namespace'=>'NewAdmin'],function($router){
    /**登录*/
    $router->get('login','LoginController@getlogin');
    $router->post('login','LoginController@postlogin');
    $router->get('logout','LoginController@logout');
    /**后台首页*/
    $router->get('index','AdminController@index');
    $router->get('rai/index','RaiController@index');
    /**banner图*/
    $router->get('banner/index','AdminController@banner');
    $router->get('banner/create','AdminController@create');
    $router->post('banner/save','AdminController@save');
    $router->post('banner/savewords','AdminController@savewords');
    $router->get('banner/destroy/{id}','AdminController@destroy');
    /**模型*/
    $router->get('work/index/{id}','WorkController@index');
    $router->get('work/review/{id}/{staus}/{type}','WorkController@review');
    $router->get('work/detail/{id}','WorkController@detail');
    $router->get('work/recommend/{id}/{type}','WorkController@recommend');
    $router->post('work/homeRecommend','WorkController@homeRecommend');
    $router->get('work/market/{id}','WorkController@market');
    $router->get('work/ignore/{id}','WorkController@ignore');
    $router->get('work/del/{id}','WorkController@destroy');
    $router->post('work/trans','WorkController@trans');
    $router->post('work/reviewFaild','WorkController@reviewFaild');
    /**订单*/
    $router->get('pay/index','AdminController@pay');
    $router->get('pay/plan','AdminController@planPay');
    $router->get('cash/index','WalletController@index');
    $router->post('cash/search','WalletController@search');
    $router->get('cash/status/{id}/{status}','WalletController@status');
    /**举报*/
    $router->get('report/index/{id}','ReportController@index');
    $router->get('report/reason','ReportController@reason');
    $router->post('report/reason/store','ReportController@store');
    $router->post('report/reason/template','ReportController@template');
    $router->get('report/del/{id}/{type}','ReportController@delete');
    $router->get('report/ignore/{id}/{type}','ReportController@ignore');
    /**Category*/
    $router->get('category/index','AdminController@category');
    $router->post('category/store','AdminController@store');
    $router->post('category/createCategory','AdminController@createCategory');
    /**用户*/
    $router->get('user/index','UserController@index');
    $router->get('modeler/index','UserController@modeler');
    $router->get('company/index','UserController@company');
    $router->get('user/search/{date}/{type}','UserController@search');
    $router->post('user/checkDate','UserController@checkDate');
    $router->post('user/recommend','UserController@recommend');
    $router->get('user/default/{id}/{type}','UserController@defaultAvatar');
    $router->get('user/partner/{id}/{type}','UserController@becomePartner');
    /**消息*/
    $router->get('notify/index','NotifyController@index');
    $router->get('notify/create','NotifyController@create');
    $router->post('notify/postNotify','NotifyController@postNotify');
    $router->post('notify/template','NotifyController@template');
    $router->post('notify/store','NotifyController@store');
    $router->get('notify/destroy','NotifyController@destroy');
    $router->get('notify/getUser/{name}','NotifyController@getUser');
    /**邮件*/
//    $router->get('mail/index','MailController@index');
    $router->get('mail/create','MailController@create');
    $router->post('mail/postMail','MailController@postMail');
    $router->post('mail/template','MailController@template');
    $router->post('mail/store','MailController@store');
    $router->post('mail/edit','MailController@edit');
    $router->get('mail/destroy/{id}','MailController@destroy');
    $router->get('mail/getUser/{name}','MailController@getUser');
    /**项目*/
    $router->get('project/index','ProjectController@index');
    $router->get('project/trans/{id}','ProjectController@trans');
    $router->get('project/detail/{id}','ProjectController@detail');
    $router->post('project/addUser','ProjectController@addUser');
    $router->post('project/uploadAndTrans','ProjectController@uploadAndTrans');
    /**留言*/
    $router->get('feedback/index','AdminController@feedback');
    $router->get('feedback/detail/{id}','AdminController@feedDetail');
    /**认证*/
    $router->get('auth/modeler','AuthController@index');
    $router->get('auth/company','AuthController@company');
    $router->get('modeler/pass/{id}','AuthController@pass');
    $router->get('modeler/fail/{id}','AuthController@fail');
    $router->get('company/pass/{id}','AuthController@companyPass');
    $router->get('company/fail/{id}','AuthController@companyFail');
    $router->get('auth/config','AuthController@config');
    $router->post('auth/saveIntroduction','AuthController@saveIntroduction');
    $router->post('auth/saveFunction','AuthController@saveFunction');
    /**角色*/
    $router->get('role/index','RoleController@index');
    $router->get('role/edit/{id}','RoleController@edit');
    $router->post('role/save','RoleController@save');
    /**权限*/

});