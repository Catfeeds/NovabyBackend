<?php
/**
 * Created by PhpStorm.
 * User: wunan
 * Date: 2017/10/9
 * Time: 下午5:05
 */
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'App\Http\Controllers\Api\Novahub','prefix'=>'novahub'], function ($api) {
        $api->group(['middleware'=>'api.account'], function ($api) {
            $api->post('/projects/create','ProjectApiController@createProject');   //创建项目
            $api->post('/projects/saveProject','ProjectApiController@saveProject');   //保存项目信息
            $api->post('/projects/saveQuote','ProjectApiController@saveQuote');   //保存Quote
            $api->get('/projects/projectRole','HomeApiController@userRoleAndPermission');   //判断用户角色
            $api->get('/projects','HomeApiController@project');     //我的项目列表
            $api->get('/projects/projectUpdateStatus','HomeApiController@projectUpdateStatus');     //更改项目更新标记
            $api->get('/projects/recommendList','ProjectApiController@recommendList');     //推荐列表
            $api->post('/projects/requirement','ProjectApiController@requirement');     //上传原画
            $api->get('/projects/getProjectInfo','HomeApiController@getProjectInformation');     //获取项目信息
            $api->get('/projects/getMembers','HomeApiController@getMembers');     //获取项目人员
            $api->get('/projects/getRequirement','HomeApiController@getRequirement');     //获取原画需求状态
            $api->get('/projects/getQuote','HomeApiController@getQuote');     //获取项目quote状态
            $api->get('/projects/importTeam','HomeApiController@importTeam');     //获取团队人员
            $api->post('/projects/saveImport','HomeApiController@saveImport');     //保存导入的团队人员
            $api->post('/projects/projectInvite','ProjectApiController@invite');     //项目邀请
            $api->post('/projects/InviteMember','HomeApiController@inviteMember');     //project设置邀请成员
            $api->get('/projects/applyStatus','UploadApiController@applyStatus');     //获取乙方报价状态
            $api->get('/projects/projectIntention','UploadApiController@apply');     //项目申请
            $api->get('/projects/getNDA','UploadApiController@getNDA');     //获取NDA
            $api->post('/projects/projectNDA','UploadApiController@NDA');     //乙方签订NDA
            $api->post('/projects/projectApply','UploadApiController@offer');     //项目报价
            $api->get('/projects/applyList','ProjectApiController@applyList');     //报价列表
            $api->get('/projects/payTerm','ProjectApiController@payTerm');     //获取支付合同
            $api->post('/projects/agreePayTerm','ProjectApiController@agreePayTerm');     //同意支付合同
            $api->get('/projects/modelList','ProjectApiController@modelList');     //项目的模型列表
            $api->get('/projects/model','ProjectApiController@modelInformation');   //项目单个模型信息
            $api->post('/projects/modelUpload','UploadApiController@projectModelUpload'); //项目模型文件上传
            $api->post('/projects/modelSave','UploadApiController@buildUpload');     //项目模型转换
            $api->post('/projects/modelPublish','UploadApiController@buildPublish');      //项目模型提交
            $api->post('/projects/modelAccept','ProjectApiController@accept');    //甲方通过单个模型
            $api->get('/projects/projectResult','HomeApiController@getResult');   //获取项目结果
            $api->post('/projects/projectReview','ProjectApiController@PassOrFail');   //项目审核
            $api->post('/projects/projectRate','ProjectApiController@Rate');     //项目评分
            $api->get('/projects/projectContract','ProjectApiController@Contract');      //项目支付信息
            $api->get('/projects/chat','ProjectApiController@getChatList');      //获取会话留言记录
            $api->post('/projects/createChat','ProjectApiController@createChat');      //创建会话
            $api->post('/projects/chatSend','ProjectApiController@chatSend');      //发送留言会话
            $api->post('/projects/projectHelp','HomeApiController@projectHelp');    //项目Help
            $api->get('/projects/projectOver','HomeApiController@projectEnd');    //项目是否最后一天
            $api->get('/projects/projectStatus','HomeApiController@projectStatus');    //项目是否开始制作过程
//            $api->get('/projects/searchUser','HomeApiController@searchUser');    //搜索用户
            $api->post('/projects/saveMember','HomeApiController@saveMember');    //保存项目成员
            $api->post('/projects/getRole','HomeApiController@getRole');    //获取角色
            $api->post('/projects/removeMember','HomeApiController@removeMember');    //移除用户
            $api->post('/projects/getWorkBench','HomeApiController@getWorkBench');    //获取工作台状态
            $api->get('/projects/getFeedBackInfo','HomeApiController@getFeedBackInfo');    //获取FeedBack页面
            $api->get('/projects/getFeedBack','HomeApiController@getFeedBack');    //获取FeedBack列表
            $api->get('/projects/feedBackDetail','HomeApiController@feedbackDetail');    //获取FeedBack详情
            $api->post('/projects/updateStatus','HomeApiController@updateStatus');    //更新feedback的状态
            $api->get('/projects/deleteProject','ProjectApiController@deleteProject');    //删除项目
            //$api->get('/projects/listComments','HomeApiController@listComments');    //列出标注留言
            $api->post('/projects/sendComment','HomeApiController@sendComment');    //发送标注留言
            $api->get('/service/userService','HomeApiController@userServices');    //用户服务权限
            $api->post('/service/validateKey','HomeApiController@validateKey');    //判断服务key是否正确
        });
        $api->group(['middleware'=>'api.reg'], function ($api) {
            $api->get('/projectCount','HomeApiController@projectCount');    //我的项目数量
        });
    });
});