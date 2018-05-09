<?php
/**
 * Created by PhpStorm.
 * User: wz
 * Date: 2017/5/10
 * Time: 11:17
 */
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Http\Controllers\Api\Projects','prefix'=>'project'], function ($api) {
        $api->group(['middleware'=>'api.account'], function ($api) {

            $api->post('/create','ProjectListsApiController@create');

            $api->post('/create_test','ProjectListsApiController@create_test');
            $api->get('/proposal','ProjectListsApiController@proposal');
            $api->post('/chooseModeler','ProjectListsApiController@chooseModeler');
            $api->get('/checkAttachment','ProjectListsApiController@checkAttachment');
            $api->post('/uploadAttachment','ProjectListsApiController@uploadAttachment');
            $api->get('/viewconcepts','ProjectListsApiController@viewconcepts');
            $api->get('/viewcontract','ProjectListsApiController@viewcontract');
            $api->post('/submission','ProjectListsApiController@submission');
            $api->get('/dayworks','ProjectListsApiController@dayworks');
            $api->get('/modelformark','ProjectListsApiController@modelformark1');
            //$api->get('/modelformark1','ProjectListsApiController@modelformark1');
            $api->post('/convertconfirm','ProjectListsApiController@convertconfirm');
            $api->get('/submissionResult','ProjectListsApiController@submissionResult');
            $api->get('/progress','ProjectListsApiController@progress');
            $api->post('/creditCard','PayApiController@creditCard');
            $api->get('/payPal','PayApiController@payPal');
            $api->get('/proposalResult','ProjectListsApiController@proposalResult');
            $api->get('/checkSubmission','ProjectListsApiController@checkSubmission');
            $api->get('/proposal_lists','ProjectListsApiController@proposal_lists');
            $api->post('/rai','ProjectListsApiController@Rai');
            $api->get('review','ProjectListsApiController@review');
            $api->get('checkPayResult','ProjectListsApiController@checkPayResult');
            $api->get('testpdf','ProjectListsApiController@testpdf');
            $api->get('finalattach','ProjectListsApiController@finalattach');
            $api->get('prevContract','ProjectListsApiController@prevContract');
            $api->get('/lists','ProjectListsApiController@lists');
            $api->get('/mark/markperm','ProjectListsApiController@markperm');
            $api->post('mark','ProjectListsApiController@buildmark');
            $api->get('marks','ProjectListsApiController@marklists');
            $api->get('mark/records','ProjectListsApiController@records');
            $api->post('mark/check','ProjectListsApiController@check');

            $api->post('delete','ProjectListsApiController@delete');



        });
        $api->group(['middleware'=>'api.reg'], function ($api) {

            $api->get('/cates','ProjectListsApiController@cates');
            $api->get('/pubattr','ProjectListsApiController@pubattr');
            $api->get('/buildprocess','ProjectListsApiController@buildprocess1');
            $api->get('/buildprocess1','ProjectListsApiController@buildprocess1');
            $api->get('flush_test','ProjectListsApiController@flush_test');

        });

    });
    $api->group(['namespace' => 'App\Http\Controllers\Api\Projects','prefix'=>'task'], function ($api) {
        $api->group(['middleware'=>'api.account'], function ($api) {
            $api->get('/apply','TasksApiController@apply');
            $api->post('/offer','TasksApiController@offer');
            $api->post('/uploadDaily','TasksApiController@uploadDaily');
            $api->get('/myBidding','TasksApiController@myBidding');
            $api->get('/getPdf/{id}','TasksApiController@getPdf');
            $api->get('/ckbid','TasksApiController@ckbid');
            $api->post('/withdraw','TasksApiController@withdraw');
            $api->get('/withdrawHistory','TasksApiController@withdrawHistory');
            $api->post('mark/response','TasksApiController@markresponse');

        });
        $api->group(['middleware'=>'api.reg'], function ($api) {
            $api->get('/lists','TasksApiController@lists');
            $api->get('/detail','TasksApiController@detail');
            $api->get('/porosoal','TasksApiController@porosoal');
            $api->get('/testfr','TasksApiController@testFormRequest');

        });

    });
});
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Http\Controllers\Api'], function ($api) {
//namespace声明路由组的命名空间，因为上面设置了"prefix"=>"api",所以以下路由都要加一个api前缀，比如请求/api/users_list才能访问到用户列表接口
        $api->group(['middleware'=>'api.account'], function ($api) {
            $api->post('/work/create','MyWorkApiController@pubwork');
            $api->get('/work/preview','MyWorkApiController@preview');

            $api->post('/work/create/step1','MyWorkApiController@pubstep1');
            $api->post('/work/create/step2','MyWorkApiController@pubstep2');
            $api->post('/work/create/step3','MyWorkApiController@pubstep3');
            $api->post('/work/create/step4','MyWorkApiController@pubstep4');
            $api->post('/work/create/step5','MyWorkApiController@pubstep5');
            $api->get('/work/myworks','MyWorkApiController@myworks');
            $api->post('/work/download','MyWorkApiController@download');
            $api->get('/user/userType','UserController@download');
            $api->post('/user/cover','UsersController@cover');
            $api->post('/work/modelEdit','MyWorkApiController@modelEdit');
            $api->get('/work/modelEdit','MyWorkApiController@modelEditData');


            $api->post('/work/show','MyWorkApiController@showedit');
            $api->post('/work/like','MyWorkApiController@likework');
            $api->post('/work/delete','MyWorkApiController@deleteWork');
            $api->post('/work/deleteModelImage','MyWorkApiController@deleteModelImage');
            $api->post('/work/rate','MyWorkApiController@rate');
            $api->get('/work/mydownloads','MyWorkApiController@mydownloads');
            $api->post('/profile/edit','ProfileApiController@save');
            $api->get('/profile/userInfo','ProfileApiController@userInfo');
            $api->post('/profile/saveLang','ProfileApiController@saveLang');
            $api->post('/profile/savePay','ProfileApiController@savePay');
            $api->get('/profile/payInfo','ProfileApiController@payInfo');
            $api->get('/behavior/recommend','ProfileApiController@recommend');
            $api->post('/behavior/follow','BehaviorController@follow');

            $api->post('/profile/changepasswd','ProfileApiController@changepasswd');
            $api->get('/data/ossToken','MyWorkApiController@ossToken');
            $api->get('/data/ossTokenAtt','MyWorkApiController@ossToken_att');
            $api->get('/data/uploadToken','ModelApiController@getToken');

            $api->get('/work/zip','MyWorkApiController@zip');
            $api->get('/profile/basicInfo','ProfileApiController@basicInfo');
            $api->post('/work/upload','MyWorkApiController@doupload');
            $api->post('/model/comment','BehaviorController@comemnt');
            $api->post('/report','ProfileApiController@report');
            $api->get('/getReason','ProfileApiController@getReason');
//            $api->post('/comment/report','BehaviorController@comemntreport');



            $api->post('/social/facebookconnect','BehaviorController@facebookconnect');
            $api->get('/social/facebookcb','BehaviorController@facebookcb');
            $api->post('/social/fbconnect','BehaviorController@fbconnect');
            $api->post('/social/fbdisconnect','BehaviorController@fbdisconnect');
            $api->get('/social/twitter/connect','OauthController@twitter');
            $api->get('/social/twitter/outhcb','OauthController@outhcb');
            $api->post('/social/twitter/disconnect','OauthController@twdisconnect');
            $api->post('/social/linkedin/disconnect','OauthController@lkddisconnect');
            $api->post('/social/pinterest/outhcb','OauthController@pinterestcb');
            $api->post('/social/pinterest/disconnect','OauthController@disconnectpin');
            $api->post('/social/comment/report','BehaviorController@comemntreport');
            $api->get('/message/list','MessageApiController@lists');
            $api->post('/message/delete','MessageApiController@delete');
            $api->post('/message/markread','MessageApiController@markread');
            $api->get('/auth/information','UsersController@information');
            $api->get('/auth/result','UsersController@result');
            $api->get('/plan/choose','PlanController@choose');
            $api->get('/plan/myplans','PlanController@myplans');
            $api->get('/plan/buy','Projects\PayApiController@payForPlan');
            $api->post('/plan/freeupgrade','PlanController@freeupgrade');
            $api->get('/plan/planstart','PlanController@plantime');
            $api->get('/user/modelersearch','UsersController@modelersearch');
            $api->post('/modelFeedback','UsersController@modelFeedback');//1
            $api->post('/team/createTeam','TeamApiController@createTeam');  //创建Team
            $api->post('/team/searchUser','TeamApiController@searchUSer');  //Team搜索User
            $api->post('/team/inviteUser','TeamApiController@inviteUser');  //Team邀请User
            $api->get('/team/deleteMember','TeamApiController@deleteMember');  //删除成员
            $api->post('/team/saveTeam','TeamApiController@saveTeam');  //保存Team
            $api->get('/team/joinTeam','TeamApiController@joinTeam');  //同意加入Team
            $api->get('/team/rejectTeam','TeamApiController@rejectTeam');  //拒绝加入Team






        });
        $api->group(['middleware'=>'api.reg'], function ($api) {
            $api->post('/profile/editIcon','ProfileApiController@editIcon'); //修改头像
            $api->get('/work/tags','MyWorkApiController@tags');
            $api->get('/social/status','DataApiController@socialstatus');
            $api->get('/data/dict/{cate}','DataApiController@dict');
            $api->get('/data/cities','DataApiController@cities');
            $api->post('/auth/login','AuthApiController@login');//登录
            $api->post('/auth/register','AuthApiController@register');//注册
            $api->post('/auth/active','AuthApiController@activeAccount');//激活
            $api->get('/markets','UsersController@market');//1
            $api->get('/market/newworknum','UsersController@latestworknums');//1
            $api->get('/market/newworks','UsersController@latestworks');//1
            $api->get('/followings','UsersController@myfollowings');//1
            //$api->post('/oauth/twitter',)
            $api->get('/cache1','AuthApiController@testCache1');
            $api->get('/cache2','AuthApiController@testCache2');
            $api->get('/model/detail/{id?}','ModelApiController@ModelDetail');//1
            $api->get('/model/detailm','ModelApiController@ModelDetailm');//1
            $api->get('/model/comments','CommentApiController@commentlists');//1
            $api->post('/comment/reply/delete','CommentApiController@replyDelete');

            $api->get('/search','SearchApiController@search');//1
            $api->get('/recommend','SearchApiController@recommendUserModelfunc'); //推荐模型和人和项目
            //$api->get('/project/recommend','SearchApiController@recommendProjectfunc');//推荐项目
            //$api->get('/users/recommend','SearchApiController@recommendUserfunc');//1
            //$api->get('/recommend/users','SearchApiController@recommendUserModelfunc');//1
            $api->get('/plan/list','PlanController@plans');//1
            $api->get('/user/finduser','UsersController@findUser');


        });
        $api->group(['middleware'=>'api.reg'], function ($api) {
            $api->get('/users/userWorks','UsersController@userWorks');//1
            $api->get('/users/userWorksm','UsersController@userWorksm');//1
            $api->get('/users/likedWorks','UsersController@likedWorks');
            $api->get('/users/followers','UsersController@followers');//1
            $api->get('/users/followings','UsersController@followings');//1
            $api->post('/feedback','UsersController@feedback');//1
            $api->get('/users','UsersController@userInfo');//1
            $api->get('/usersm','UsersController@userInfom');//1
            $api->get('/work/workInfo/{id?}','MyWorkApiController@wkDetail');//1
            $api->get('/user/usercard','UsersController@userCard');//1
            $api->get('/share','ShareController@share');//1
            $api->get('/preshare','ShareController@preshare');//1
            $api->get('/home','HomeApiController@home');//1
            $api->post('/account/findpass','AccountApiController@findpass');//1
            $api->post('/account/resetpass','AccountApiController@resetpass');
            $api->post('/account/ckeckcode','AccountApiController@ckeckcode');
            $api->get('/data/timezone','DataApiController@timezone');
            $api->get('/test/mail','DataApiController@sendmail');
            $api->get('/auth/modeler/share','ShareController@modelerShare');
            $api->get('/partner','ProfileApiController@partner');
            $api->get('/partner/work','ProfileApiController@getPartnerWork');
            $api->get('/search/userData','SearchApiController@searchUserData');
            $api->get('/search/modelData','SearchApiController@searchModelData');
            $api->get('/getUser','SearchApiController@getUser');
            $api->get('/getModel','SearchApiController@getModel');
            $api->get('/getSearch','SearchApiController@getSearch');
            $api->get('/getDemo','MyWorkApiController@demo');
            $api->get('/visitor','HomeApiController@getVisitor');
//            $api->get('/listTeam','TeamApiController@listTeam');
            $api->get('/team/listTeam','TeamApiController@listTeam');  //team信息
            $api->get('/data/getFormat','DataApiController@getFormat');  //获取format

        });

    });
});