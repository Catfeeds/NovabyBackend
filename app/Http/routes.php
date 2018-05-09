<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$lang=isset($_COOKIE['lang']);
if($lang){
    $locale='zh_cn';
}else{
    $locale='en';
}
App::setLocale($locale);
// WebSocket 测试
Route::get('/web_socket','WorkController@socket');

Route::resource("api/target",'ApiController');
Route::get('all','NovaController@home');
Route::get('/','IndexController@newhome')->name('newhome');
Route::get('/{name}','IndexController@userHome')->where('name', '[A-Za-z ]{4,16}');
Route::get('pi',function(){
    phpinfo();
});
Route::get('pi1',function(){

    return response(json_encode(['name'=>'aaa']),200)->header("Content-Type",'text/json');
});
Route::get('ws',function(){
    $client = new \Predis\Client("");
    $client->set('foo', 'bar');
    echo $value = $client->get('foo');
    echo "<br/>";

    echo "start <br/>";
    $msg =['name'=>'hello','time'=>time()];
    //$user = \App\Model\User::find(10000);
    Event::fire(new \App\Events\WorkPubEvent(3,$msg));

});
Route::get('ws1',function(){
   return view('ws.ws1');
});
Route::get('p12','IndexController@uploadtest');
/*
Route::group(['prefix'=>'m'],function(){
    Route::get('/home','NovaController@newhome');
    Route::get('/model/{id}','NovaController@newmodel')->name('newviewmodel');
});
*/
/**支付回调*/
Route::get('/pay/success/{id}/{uid}/{modeler_id}','Api\Projects\PayApiController@paySuccess');
Route::get('/pay/error/{id}','Api\Projects\PayApiController@payError');


Route::get('/pay/payforplansuccess/{pid}/{month}/{userid}','Api\Projects\PayApiController@payforplansuccess');
Route::get('/pay/payforplanerror','Api\Projects\PayApiController@payforplanerror');
Route::get('/userWorks/{id}','Api\Projects\ProjectListsApiController@userWorks');


Route::resource('api1/appdata', 'AppDataController', ['only' => ['store']]);
Route::get('api1/spa/page/dashboard', 'UserController@dashboard');
Route::get('api1/spa/page/like', 'UserController@like');
Route::get('api1/spa/page/profile', 'UserController@profile');
Route::get('api1/spa/page/analytics/{name}', 'UserController@analytics');
Route::post('api1/register','RegisterController@register');
Route::post('api1/chkreg','RegisterController@chkreg');

Route::post('api1/login','RegisterController@login');
Route::get('user/hometest', 'UserController@home');
Route::post('api/lists','NovaController@lists');
Route::get('/homepage/{home}','UserController@homePage');
//Route::get('auth/login', 'Auth\AuthController@getLogin');
//Route::get('auth/register','Auth\AuthController@getRegister');
//Route::post('auth/login','Auth\AuthController@postLogin');
//Route::post('auth/register','Auth\AuthController@postRegister');
//Route::get('auth/logout','Auth\AuthController@getLogout');
//Route::get('user/register','UserController@registerForm');
//Route::post('user/register','UserController@register');
//Route::get('user/login','UserController@loginForm');
//Route::post('user/editHead','PostController@editHead');
//Route::post('user/edit','UserController@edit');
//Route::post('user/editPrice','UserController@editPrice');
//Route::post('user/login',['as'=>'login','uses'=>'UserController@login']);
//Route::post('post/deleteItem','PostController@itemDel');
//Route::get('user/profile','UserController@profile');
//Route::get('post/new','PostController@newposts');
//Route::post('post/new','PostController@upload');
//Route::get('post/newItem/{id}','PostController@newItem');
//Route::post('post/newItem/{id}','PostController@postnewItem');
//Route::get('list','NovaController@list');
//Route::get('lists','NovaController@lists');
//Route::get('view/{id}','NovaController@view');
//Route::get('post/lists','PostController@lists');

//Route::post('post/newpost','PostController@newpost');

Route::group(['prefix'=>'auth'],function(){
    Route::get('register1','UserController@registerForm1');
    Route::get('register','UserController@registerForm');
    Route::get('expressreg','UserController@expressreg');

    Route::post('expressregister','UserController@expressregister');
    Route::get('expressregresult','UserController@expressregResult');

});



Route::get('user/{id}/{cate?}','IndexController@userHome');
Route::get('follow/{id}/{cate?}','UserController@userfollows');
Route::get('model/{id}','NovaController@model')->name('viewmodel');
Route::get('model/detail/{id}','WorkController@detail');
Route::get('project/detail/{id}','WorkController@projectDetail');
Route::get('modelshow/{id}','NovaController@modelshow')->name('viewmodel');

Route::group(['prefix'=>'useraccount'],function(){
    Route::post('sendmail','UserController@sendmail');
    Route::post('resetpass','UserController@resetpass');
    Route::get('verifyEmail','NovaController@verifyCode');
    Route::post('ckEmailAndCode','UserController@ckEmailAndCode');


    Route::post('applywithoutapply','UserController@applywithoutapply');

    Route::get('surveySuccess','UserController@surveySuccess');
    Route::get('surveysucc','UserController@surveysucc');
    Route::get('findpassword','UserController@forget');
    Route::post('ckinvite','UserController@ckinvite');
});
Route::group(['prefix'=>'oauth'],function(){
    Route::get('twitter','OauthController@twitter');
    Route::get('twcb','OauthController@twittercallback');
    Route::post('disconnect','OauthController@disconnect');
    //Route::get('twcb','OauthController@twcb');
    Route::get('linkedincb','OauthController@linkedincb');
    Route::get('linkedinAuthOk','OauthController@linkedinAuthOk');
    Route::post('fbconnect','OauthController@fbconnect');
    Route::get('pinconnect','OauthController@pinconnect');


});
Route::group(['prefix'=>'pay','middleware'=>'user.account'],function(){
    Route::get('paypal','PaypalController@paypal');
    Route::get('alipay','UserController@alipay');
    Route::post('couponPay','PayApiController@couponPay');
    Route::get('paysuccess','PaypalController@paypalresult');
    Route::get('rechargesuccess','PaypalController@rechargeresult');
    Route::get('paypal/trialpay/{id}','PaypalController@trialpay')->name('trialpay_paypal');
    Route::get('paypal/buildpay/{id}','PaypalController@buildpay')->name('buildpay_paypal');
    Route::get('paypal/trialresult','PaypalController@trialresult')->name('trialresult');
    Route::get('paypal/buildresult','PaypalController@buildresult')->name('buildresult');
    Route::post('paypal/creditCard','PaypalController@creditCard')->name('creditCard');
    Route::post('paypal/creditCardPayfortrial','PaypalController@creditCardPayfortrial')->name('creditCardPayfortrial');
    Route::post('paypal/creditCardPayforcontract','PaypalController@creditCardPayforcontract')->name('creditCardPayforcontract');
    Route::get('paypal/paypalPayfortrial/{pid}','PaypalController@paypalPayfortrial')->name('paypalPayfortrial');
    Route::get('paypal/paypalPayforcontract/{pid}','PaypalController@paypalPayforcontract')->name('paypalPayforcontract');
    Route::get('/paypal/paysuccessfortrial','PaypalController@paysuccessfortrial');

});
Route::group(['prefix'=>'behavior','middleware'=>'web'],function(){
    Route::post('sendmsg','BehaviorController@sendmsg')->name('sendMsg');
    Route::post('like','BehaviorController@like')->name('like');
    Route::post('comment','BehaviorController@pubcomment')->name('postComment');
    Route::post('follow','BehaviorController@followuser')->name('followsb');
    Route::get('notices','BehaviorController@updateNotice');
    Route::get('markmsg','BehaviorController@markmsg');
    Route::post('star','BehaviorController@star');
    Route::get('getChat','BehaviorController@userchat');
    Route::post('changeEmail','BehaviorController@changeEmail');
    Route::post('wish','BehaviorController@wish');
    Route::post('prj_apply','BehaviorController@prj_apply');
});
Route::group(['prefix'=>'account','middleware'=>'user.account'],function(){
    Route::get('profile','AccountController@userprofile')->name('profile');
    Route::get('setting','AccountController@usersetting')->name('setting');
    Route::get('logout','AccountController@logout')->name('logout');
    Route::post('editIcon','AccountController@editIcon');
    Route::post('editprofile','AccountController@editprofile');
    Route::post('editaccount','AccountController@editaccount');
    Route::post('editpayaccount','AccountController@editpayaccount');
    Route::get('apply/stepOne','AccountController@stepOne');
    Route::post('apply','AccountController@applyInfo');
    Route::get('applysuccess','AccountController@applysuccess');
    Route::get('applyfail','AccountController@applyfail');
    Route::get('applyresult','AccountController@applyresult');
    Route::get('wallet','AccountController@wallet')->name('wallet');
    Route::post('verify','AccountController@verifyEmail');
    Route::post('ckverify','AccountController@ckverify');
    Route::get('invite', 'AccountController@invite')->name('invite');
    Route::post('invite', 'AccountController@doInvite');
    Route::post('recharge', 'AccountController@genrecharge');
    Route::post('getModelerReward','AccountController@modelerReward');
    Route::get('survey','AccountController@apply');
    Route::post('doapply','AccountController@doApply');
    Route::post('skipapply','AccountController@skipapply');
    Route::get('ossToken','PostController@ossToken');
    Route::get('profile/avatar','AccountController@avatar')->name('avatar');
    Route::get('profile/basic','AccountController@basic')->name('basic');
    Route::get('profile/professional','AccountController@professional')->name('professional');
    Route::get('profile/payment','AccountController@payment')->name('payment');
    Route::get('profile/social','AccountController@social')->name('social');
    Route::get('profile/security','AccountController@security')->name('security');
    Route::post('profile/professional','AccountController@updateprofessional');
    Route::post('profile/editprofile','AccountController@updatebasic');
});
Route::group(['prefix'=>'order','middleware'=>'user.account'],function(){
    Route::post('cart','OrderController@cart');
    Route::post('removeCart','OrderController@cartremove');
    Route::get('recharge/{id}','PaypalController@recharge');
});
Route::group(['prefix'=>'product','middleware'=>'user.account'],function(){
    Route::get('products','ProductController@products')->name('products');
    Route::get('purchases/{id?}','ProductController@purchases')->name('purchases');
    Route::get('sales','ProductController@sales')->name('sales');
    Route::get('publish','PostController@publish')->name('publish');
    //Route::get('new','PostController@newProduct')->name('newproduct');
    Route::post('publish','PostController@dopublish')->name('doPublish');
    Route::get('checkout/{id?}','ProductController@checkout')->name('checkout');
    Route::post('delete','ProductController@elementDelete')->name('deleteElement');
    Route::get('saleDetail/{id}','ProductController@saleDetail');
    Route::get('getEdit/{id}','PostController@getEdit');
    Route::post('new/step1','PostController@step1')->name('step1');
    Route::post('new/step2','PostController@step2')->name('step2');
    Route::post('new/step3','PostController@step3')->name('step3');
    Route::post('new/step4','PostController@step4')->name('step4');
    Route::post('new/step5','PostController@step5')->name('step5');
    Route::get('new/step1/{id?}','PostController@step1_view')->name('step1_view');
    Route::get('new/step2/{id?}','PostController@step2_view')->name('step2_view');
    Route::get('new/step3/{id?}','PostController@step3_view')->name('step3_view');
    Route::get('new/step4/{id?}','PostController@step4_view')->name('step4_view');
    Route::get('new/step5/{id?}','PostController@step5_view')->name('step5_view');
    Route::post('ck_step1','PostController@ck_step1')->name('ck_step1');
    Route::post('ck_step2','PostController@ck_step2')->name('ck_step2');
    Route::post('ck_step3','PostController@ck_step3')->name('ck_step3');
    Route::post('ck_step4','PostController@ck_step4')->name('ck_step4');
});
Route::group(['prefix'=>'projects','middleware'=>'user.account'],function(){
   Route::get('new/{id?}','ProjectsController@create')->name('projectNew');
   Route::get('detail/{id}','ProjectsController@detail')->name('projectDetail');
   Route::post('save','ProjectsController@save')->name('save');
   Route::post('list/{id}','ProjectsController@prjLists')->name('list');
   Route::get('trial/{id}','ProjectsController@trial')->name('projectTrial');
   Route::post('trialpub','ProjectsController@trialpub')->name('trialpub');
   Route::get('trialtask/{id}','ProjectsController@trialtask')->name('trialtask');
   Route::get('pay/{id}','ProjectsController@choicepay')->name('choicepay');
   Route::get('directpay/{id}','ProjectsController@directchoicepay')->name('directchoicepay');
   Route::get('build/{id}','ProjectsController@build')->name('projectBuild');
   Route::get('all/{id?}','ProjectsController@all')->name('all');
   Route::post('getEdit','ProjectsController@edit')->name('eidt');
   Route::get('trialpay/{id}','ProjectsController@trialpay')->name('trialpay');
   Route::post('getTrialInfo','ProjectsController@getTrialInfo')->name('getTrialInfo');
   Route::get('success/{id}','ProjectsController@pubsuccess')->name('pubsuccess');
   Route::get('skip/{id}','ProjectsController@skip')->name('skip');
   Route::post('skip','ProjectsController@doskip')->name('doskip');
   Route::post('payforbuild','ProjectsController@payforbuild')->name('payforbuild');
   Route::get('buildpay/{id}','ProjectsController@buildpay')->name('buildpay');
   Route::get('progress/{id}','ProjectsController@progress')->name('prjprogress');
   Route::get('review/{id}','ProjectsController@review')->name('projectReview');
   Route::get('failed/{id}','ProjectsController@failed')->name('failed');
   Route::post('chat','ProjectsController@chat')->name('chat');
   Route::post('prjdays','ProjectsController@prjdays')->name('prjdays');
   Route::post('trialstar','ProjectsController@trialstar')->name('trialstar');
   Route::get('phpinfo','ProjectsController@phpinfo');
   Route::post('skip','ProjectsController@doSkip');
   Route::get('payresult/{id}','ProjectsController@payresult');

   Route::post('workrate','ProjectsController@workrate');
   Route::post('workreport','ProjectsController@workreport');
});
Route::group(['prefix'=>'tasks'],function(){
    Route::get('lists/{id?}','TasksController@index');
    Route::get('getlists','TasksController@getlists');


    Route::get('detail/{id}','TasksController@detail');
});
Route::group(['prefix'=>'task','middleware'=>'user.account'],function(){
    Route::get('lists/{id?}','TaskController@all')->name('lists');
    Route::get('bid/{id}','TaskController@bid')->name('bid');
    Route::post('bid','TaskController@pubbid')->name('pubbid');
    Route::get('detail/{id}','TaskController@detail')->name('taskdetail');
    Route::get('trial/{id}','TaskController@trial')->name('tasktrial');
    Route::get('trialsubmit/{id}','TaskController@trialsubmit')->name('tasktrialsubmit');
    Route::get('trialresult/{id}','TaskController@trialresult')->name('tasktrialresult');
    Route::get('build/{id}','TaskController@build')->name('taskbuild');
    Route::post('submittrial','TaskController@submittrial')->name('submittrial');
    Route::get('review/{id}','TaskController@review')->name('taskreview');
    Route::get('calender','TaskController@calender')->name('calender');
    Route::post('ignore','TaskController@ignore')->name('ignore');
    Route::post('builddalypub','TaskController@builddalypub')->name('builddalypub');
    Route::get('taskprogress/{id}','TaskController@taskprogress')->name('taskprogress');
    Route::post('taken','TaskController@taken')->name('taken');
    Route::get('payresult/{id}','TaskController@payresult');
});
Route::group(['prefix'=>'data'],function(){
    Route::get('elementlists','NovaController@elementlists');
    Route::get('comments','UserController@getComments');
    Route::get('userlists','IndexController@userlists');
    Route::get('checkapply', 'IndexController@checkapply');
    Route::get('setLang/{id}','IndexController@ClientLang');
    Route::get('ckdraft','IndexController@ckdraft');
    Route::get('testToken','IndexController@ossToken');
});
Route::group(['prefix'=>'following'],function(){
    Route::get('list','NovaController@follow')->name('followlist');
    Route::get('followlist','NovaController@followlist')->name('followUsersList');
    Route::get('data','NovaController@data');
});
Route::group(['prefix'=>'search'],function(){
    Route::get('model/{kw?}','SearchController@searchModel');
    Route::get('user/{kw?}','SearchController@searchUser');
});
Route::group(['prefix'=>'about'],function(){
    Route::get('/privacy', 'AboutController@privacy')->name('privacy');
    Route::get('/about', 'AboutController@about')->name('about');
    Route::get('/term', 'AboutController@term')->name('term');
    Route::get('/License', 'AboutController@help')->name('License');
    Route::get('/feedback', 'AboutController@feedback')->name('feedback');
    Route::post('/feedback', 'AboutController@dofeedback')->name('dofeedback');
    Route::get('/feedbacksuccess', 'AboutController@feedbacksuccess')->name('feedbacksuccess');
});
Route::group(['prefix'=>'_admin_2016','namespace'=>'Admin', 'middleware'=>'admin.auth'], function(){
    Route::get('products','ElementController@elementList')->name('products_list');
    Route::get('members','MemberController@userLists')->name('members_list');
    Route::get('procuct/{id}','ElementController@opElement');
    Route::get('apply','MemberController@applyList');
    Route::get('model/{id}','ElementController@model');
    Route::get('index','ElementController@index')->name('dashboard');
    Route::get('apply/{uid}/3', 'MemberController@applyallowed')->name('applyallow');
    Route::post('deny', 'MemberController@applyrefused')->name('applyrefused');
    Route::get('survey', 'MemberController@survey')->name('survey');
    Route::get('like/{id}/{num}','ElementController@like');
    Route::get('monthlist','ElementController@monthlist')->name('monthlist');
    Route::get('monthDetail/{time}','ElementController@monthDetail')->name('monthDetail');
    Route::get('tradelist','TradeController@tradelist')->name('tradelist');
    Route::get('reglists','MemberController@reglists')->name('reglists');
    Route::get('regmonthDetail/{time}','MemberController@regmonthDetail')->name('regmonthDetail');
    Route::get('ordermonthDetail/{time}','TradeController@ordermonthDetail')->name('ordermonthDetail');
    Route::get('surveylists','SurveyController@surveylists')->name('surveylists');
    Route::get('surveymonthDetail/{time}','SurveyController@surveymonthDetail')->name('surveymonthDetail');
    Route::get('viewsurvey/{id}','SurveyController@viewsurvey')->name('viewsurvey');
    Route::get('sendInvite/{email}','DataController@sendEmail')->name('sendInvite');
    Route::get('homeConfig','DataController@homeConfig')->name('homeConfig');
    Route::get('test','DataController@test')->name('test');
    Route::post('testup','DataController@testup')->name('testup');
    Route::get('/','AdminAuthController@index')->name('index');
    Route::post('setconfig','DataController@setCfg')->name('setconfig');
    Route::get('applymgr','ProjectController@applymgr')->name('applymgr');
    Route::post('passapply','ProjectController@passapply')->name('passapply');
});
/*Route::get('_admin_2016/login',function(){
   return view('admin.login');
});
*/
Route::post('_admin_2016/login','Admin\AdminAuthController@dologin');

Route::group(['prefix'=>'/project/create','middleware'=>'user.account'],function(){
    Route::get('cates','Project\ProjectController@cates');
    Route::get('new/{id?}','Project\ProjectController@createNewView')->name('pub_step1');
    Route::post('new','Project\ProjectController@createNew');
    Route::get('settime/{id?}','Project\ProjectController@settime')->name('pub_step2');
    Route::post('settime','Project\ProjectController@setprjtime');
    Route::post('setcontact','Project\ProjectController@setContact');
    Route::post('settrial','Project\ProjectController@setTrial');
    Route::get('settrial/{id?}','Project\ProjectController@setTrialView')->name('pub_step3');
    Route::get('setcontact/{id?}','Project\ProjectController@setContactView')->name('pub_step4');
    Route::post('setcontact','Project\ProjectController@setContact');
    Route::get('publish/{id?}','Project\ProjectController@publishView')->name('pub_step5');
    Route::post('publish','Project\ProjectController@publish');
    Route::post('publishsave','Project\ProjectController@publishsave');
    Route::post('bidlist','Project\ProjectController@bidlist');
    Route::get('contact','Project\ProjectController@contactDetail');
    Route::get('modeltest/{id}','Project\ProjectController@modeltest');
});
Route::get('/project/list','Project\ProjectlistController@lists');

Route::post('/project/list','Project\ProjectlistController@prjlists');
Route::group(['prefix'=>'/project/progress','middleware'=>'user.account'],function(){
    Route::get('requirement/{id?}','Project\ProjectStatusController@requirement')->name('prjs1');
    Route::get('proposal/{id?}','Project\ProjectStatusController@proposal')->name('prjs2');
    Route::get('trial/{id?}','Project\ProjectStatusController@trial')->name('prjs3');
    Route::get('trialpay/{id?}','Project\ProjectStatusController@trialpay')->name('prjs41');
    Route::get('contract/{id?}','Project\ProjectStatusController@contract')->name('prjs4');
    Route::get('payment/{id?}','Project\ProjectStatusController@payment')->name('prjs5');
    Route::get('building/{id?}','Project\ProjectStatusController@building')->name('prjs6');
    Route::get('submission/{id?}','Project\ProjectStatusController@submission')->name('prjs7');
    Route::get('submissiondeny/{id?}','Project\ProjectStatusController@submissionDeny')->name('deny');
    Route::get('chatlist/{id?}','Project\ProjectStatusController@chatlist')->name('chatlist');
    Route::post('publish','Project\ProjectStatusController@pubrequirement');
    Route::post('proposal','Project\ProjectStatusController@pubproposal');
    Route::post('selectTrial','Project\ProjectStatusController@selectTrial');

    Route::post('contract','Project\ProjectStatusController@pubcontract');
    Route::post('toContract','Project\ProjectStatusController@tocontract');
    Route::post('payment','Project\ProjectStatusController@pubpayment');
    Route::post('submission','Project\ProjectStatusController@pubsubmission');
    Route::post('rate','Project\ProjectStatusController@rate');
    Route::post('chat','Project\ProjectStatusController@sendchat');
    Route::post('daywork','Project\ProjectStatusController@daywork');
    Route::post('prjdays','Project\ProjectStatusController@prjdays');
    Route::post('prjnext','Project\ProjectStatusController@prjnext');
    Route::post('pubtrial','Project\ProjectStatusController@pubtrial');
    Route::post('report','Project\ProjectStatusController@workreport');
    Route::post('trialRate','Project\ProjectStatusController@trialRate');
    Route::post('contractbyBuyer','Project\ProjectStatusController@contractbyBuyer');
    Route::post('skip','Project\ProjectStatusController@skip');

});
Route::group(['prefix'=>'/tasks/progress','middleware'=>'user.account'],function(){
    Route::get('requirement/{id?}','Project\TaskStatusController@requirement')->name('taskstep1');
    Route::get('proposal/{id?}','Project\TaskStatusController@proposal')->name('taskstep2');
    Route::get('trial/{id?}','Project\TaskStatusController@trial')->name('taskstep3');
    //Route::get('trialpay/{id?}','Project\TaskStatusController@trialpay')->name('taskstep5');
    Route::get('contract/{id?}','Project\TaskStatusController@contract')->name('taskstep4');
    Route::get('payment/{id?}','Project\TaskStatusController@payment')->name('taskstep5');
    Route::get('building/{id?}','Project\TaskStatusController@building')->name('taskstep6');
    Route::get('submission/{id?}','Project\TaskStatusController@submission')->name('taskstep7');

    Route::post('bidding','Project\TaskStatusController@subBidding')->name('subbid');
    Route::post('pubtrial','Project\TaskStatusController@pubtrial')->name('pubtrial');
    Route::post('taketrial','Project\TaskStatusController@taketrial')->name('taketrial');
    Route::post('toContract','Project\TaskStatusController@toContract')->name('toContract');
    Route::post('buildpub','Project\TaskStatusController@buildpub')->name('buildpub');
    Route::post('prjnext','Project\TaskStatusController@prjnext')->name('prjnext');
    Route::post('toTrial','Project\TaskStatusController@toTrial')->name('toTrial');
    Route::post('contract','Project\TaskStatusController@doContract')->name('doContract');
    Route::post('toBuild','Project\TaskStatusController@toBuild')->name('toBuild');
    Route::get('/uploadtest','Project\TaskStatusController@upload');
    Route::post('/uploadtest','Project\TaskStatusController@doupload');
    Route::get('/fbtest','Project\TaskStatusController@fbtest');




});


require __DIR__.'/Routes/api.php';
require __DIR__.'/Routes/admin.php';
require __DIR__.'/Routes/novahub.php';



