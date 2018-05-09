$(function () {
    /**菜单*/
    var $submenu = $('.submenu');
    var $mainmenu = $('.mainmenu');
    $submenu.hide();
    $submenu.on('click','li', function() {
        $submenu.siblings().find('li').removeClass('chosen');
        $(this).addClass('chosen');
    });
    $mainmenu.on('click', 'li', function() {
        $(this).next('.submenu').slideToggle().siblings('.submenu').slideUp();
    });
    /**标签颜色变化*/
    var tags = document.getElementsByClassName("tag");
    for(var k =0 ;k<tags.length;k++)
    {    //颜色字符串
        var colorStr="";
        //字符串的每一字符的范围
        var randomArr=['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
        //产生一个六位的字符串
        for(var i=0;i<6;i++){
            //15是范围上限，0是范围下限，两个函数保证产生出来的随机数是整数
            colorStr+=randomArr[Math.ceil(Math.random()*(15-0)+0)];
        }
        tags[k].style.backgroundColor = '#'+colorStr;
    }
    /**category创建**/
    var categoryCreate = $('.category-create');
    $('.category-click').click(function () {
        categoryCreate.show();
    });
    $('.category-close').click(function () {
        categoryCreate.hide();
        // homeTrans.hide();
    });
    /**category*/
    $('.cate_name').blur(function () {
        var content = $(this).val();
        var id = $(this).parent('td').children('.id').val();
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/category/store',
            data:{
                'cate_name':content,
                'id' :id
            },
            success: function () {
                 // window.location = location;
            },
            error:function (error) {
                 // window.location = location;
            }
        });
    });
    $('.cate_name_cn').blur(function () {
        var content = $(this).val();
        var id = $(this).parent('td').children('.id').val();
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/category/store',
            data:{
                'cate_name_cn':content,
                'id' :id
            },
            success: function () {
                // window.location = location;
            },
            error:function (error) {
                // window.location = location;
            }
        });
    });
    $('.cate_active').click(function () {
        var content = $(this).val();
        var id = $(this).parent('td').children('.id').val();
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/category/store',
            data:{
                'cate_active':content,
                'id' :id
            },
            success: function (data) {
                // window.location = location;
            },
            error:function (error) {
                // window.location = location;
            }
        });
    });
    $('.cate_order').blur(function () {
        var content = $(this).val();
        var id = $(this).parent('td').children('.id').val();
        console.info(content,id);
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/category/store',
            data:{
                'cate_order':content,
                'id' :id
            },
            success: function () {
                // window.location = location;
            },
            error:function (error) {
                // window.location = location;
            }
        });
    });
    /**user时间插件**/
    $('.add-on').datepicker();
    /**user推荐**/
    var recommend = $('.user-recommend');
    $('.user-click').click(function () {
        recommend.show();
    });
    $('.user').click(function () {
        recommend.hide();
    });
    /**work推荐**/
    var homeRecommend = $('.work-recommend');
    $('.work-click').click(function () {
        homeRecommend.show();
    });
    $('.recommend-close').click(function () {
        homeRecommend.hide();
        // homeTrans.hide();
    });
    /**转换*/
    var homeTrans = $('.work-trans');
    $('.work-trans-click').click(function () {
        homeTrans.show();
        $('.id').val($(this).children('.work_id').val());
    });
    $('.trans-close').click(function () {
         homeTrans.hide();
    });
    /**审核*/
    var Auth = $('.work-auth');
    $('.work-auth-click').click(function () {
        Auth.show();
        $('.auth-id').val($(this).children('.work_id').val());
    });
    $('.auth-close').click(function () {
        Auth.hide();
    });
    /**notify显示不同的content*/
    $('.notify-box ul').on('click','.notify_id',function(){
        var id = $(this).children('#id').val();
        var content= $(this).children('#content').val();
        $('.notify-content textarea').html(content);
        $('.notify-content').children('#notify_id').val(id);
    });
    $('.notify-content textarea').blur(function () {
        var content = $('.notify-content textarea').val();
        var id = $('.notify-content').children('#notify_id').val();
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/notify/store',
            data:{
                'content':content,
                'id':id
            },
            success: function (data) {
                window.location = location;
            },
            error:function (error) {
                var json = JSON.parse(error.responseText);
                err(json.content.toString());
                $('.notify-content textarea').css('border','1px solid red');
            }
        });
    });
    function err(data)
    {
        $('.error').each(function(){
            $(this).css('display','block');
            $(this).html(data);
            var time =3;
            timeOut();
            function timeOut(){
                if(time == 0) {
                    $('.error').fadeOut(100,function(){
                        $(this).css('display','none');
                        $('.notify-content textarea').css('border','1px solid #C0C0C0');
                        window.location = location;
                    });
                }else{
                    setTimeout(function(){
                        time=time-1;
                        timeOut();
                    },1000);
                }
            }
        });
    }
    var notifyTemplate = $('.notify-template');
    $('.notify-click').click(function () {
        notifyTemplate.show();
    });
    $('.notify').click(function () {
        notifyTemplate.hide();
    });
    var mailTemplate = $('.mail-template');
    $('.mail-click').click(function () {
        mailTemplate.show();
    });
    $('.mail').click(function () {
        mailTemplate.hide();
    });
    /**notify创建**/
    $(".notify-create #all").click(function(){
        $('.text').css('display','none');
        $('.group').children('#user_id').val('all');
    });
    $(".notify-create #one").click(function(){
        $('.text').css('display','inline-block');

    });
    $('.notify-create .text').keyup(function () {
            var value = $('.notify-create .text').val();
            $.ajax({
                type: 'get',
                url: '/admin/notify/getUser/'+value,
                success: function (data) {
                    $('.user-search').show();
                    $('.user-search ul').html(data.li);
                    $('.user-search ul li').on('click',function () {
                        var id = $(this).children('#id').val();
                        var name = $(this).text();
                        $('.group').children('#user_id').val(id);
                        $('.group').children('.text').val(name);
                        $('.user-search').hide();

                    })
                }
            });
    });
    /**mail显示不同的content*/
        $('.mail-box').on('click','.mail_id',function(){
            var id = $(this).children('#id').val();
            var content= $(this).children('#content').val();
            $('.mail-content textarea').html(content);
            $('.mail-content').children('#mail_id').val(id);
    });
    $('.mail-content textarea').blur(function () {
        var content = $('.mail-content textarea').val();
        var id = $('.mail-content').children('#mail_id').val();
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/mail/store',
            data:{
                'content':content,
                'id':id
            },
            success: function (data) {
                window.location = location;
            },
            error:function (error) {
                var json = JSON.parse(error.responseText);
                err(json.content.toString());
                $('.mail-content textarea').css('border','1px solid red');
            }
        });
    });
    /**mail创建**/
    $(".mail-create #all").click(function(){
        $('.text').css('display','none');
        $('.group').children('#user_id').val('all');
    });
    $(".mail-create #one").click(function(){
        $('.text').css('display','inline-block');

    });
    $('.mail-create .text').keyup(function () {
        var value = $('.mail-create .text').val();
        $.ajax({
            type: 'get',
            url: '/admin/mail/getUser/'+value,
            success: function (data) {
                $('.user-search').show();
                $('.user-search ul').html(data.li);
                $('.user-search ul li').on('click',function () {
                    var id = $(this).children('#id').val();
                    var name = $(this).text();
                    $('.group').children('#user_id').val(id);
                    $('.group').children('.text').val(name);
                    $('.user-search').hide();

                })
            }
        });
    });

    /**举报原因**/
    $('.reason_active').click(function () {
        var content = $(this).val();
        var id = $(this).parent('td').children('.id').val();
        $.ajax({
            headers: {'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')},
            type: 'post',
            url: '/admin/report/reason/store',
            data:{
                'id' :id,
                'content':content
            },
            success: function (data) {
                // window.location = location;
            },
            error:function (error) {
                // window.location = location;
            }
        });
    });
    var reasonTemplate = $('.reason-template');
    $('.reason-click').click(function () {
        reasonTemplate.show();
    });
    $('.reason').click(function () {
        reasonTemplate.hide();
    });

    /**认证**/
    var authTemplate = $('.auth-template');
    $('.auth-click').click(function () {
        authTemplate.show();
    });
    $('.auth').click(function () {
        authTemplate.hide();
    });
});
