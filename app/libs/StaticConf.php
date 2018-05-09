<?php
/**
 * Created by PhpStorm.
 * User: wz
 * Date: 2017/8/3
 * Time: 14:23
 */

namespace App\libs;


class StaticConf
{

/*英文*/
    /**
     * 模型精度
     * @var array
     */
    public static $resolution = [
      1=>['id'=>1,'name'=>'High'],
      2=>['id'=>2,'name'=>'Medium'],
      3=>['id'=>3,'name'=>'Low'],
    ];
    /**
     * 单价
     * @var array
     */
   public static $budget = [
       1=>['id'=>1, 'name'=>'1-200'],
       ['id'=>2, 'name'=>'201-500'],
       ['id'=>3, 'name'=>'501-1000'],
       ['id'=>4, 'name'=>'1001-2000'],
       ['id'=>5, 'name'=>'more than 2000']
   ];
    /**
     * 项目属性（公开or私密）
     * @var array
     */
   public static  $project_visibility = [
     1=>['id'=>1,'name'=>'Artists using novaby.com and public engines can find this project'], //公开
     2=>['id'=>3,'name'=>'Only artists I have invited can find this project']               //私密
   ];
    /**报价时间
     * @var array
     */
    public static $bidding_times = [
        1=>['id'=>1, 'name'=>'1 Day'],
        ['id'=>2, 'name'=>'2 Days'],
        ['id'=>3, 'name'=>'3 Days'],
        ['id'=>4, 'name'=>'4 Days'],
        ['id'=>5, 'name'=>'5 Days'],
    ];
    /**
     * 周期
     * @var array
     */
    public static $expect_times = [
        5=>['id'=>5, 'name'=>'More than 6 months'],
        4=>['id'=>4, 'name'=>'3-6 months'],
        3=>['id'=>3, 'name'=>'1-3 months'],
        2=>['id'=>2, 'name'=>'less than 1 month'],
        1=>['id'=>1, 'name'=>'less than 1 week'],
    ];
    /**
     * 游戏引擎
     * @var array
     */
    public static$engine = [
        1=>['cate_id'=>1,'cate_name'=>'Unity'],
        ['cate_id'=>2,'cate_name'=>'UE4'],
        ['cate_id'=>3,'cate_name'=>'Other'],
    ];
    public static $model_tags = [
    1=> ['tag_id'=>1,'tag_name'=>'AR/VR'],
        ['tag_id'=>2,'tag_name'=>'Game'],
        ['tag_id'=>3,'tag_name'=>'AR/VR'],
        ['tag_id'=>4,'tag_name'=>'AR/VR'],
    ];
    /**
     * 工作经验
     * @var array
     */
    public static $work_exp = [
            '0'=>'',
            '1'=>'1-3 year',
            '2'=>'3-5 year',
            '3'=>'more than 5 year'
    ];
    /**
     * 企业规模
     * @var array
     */
    public static $company_size = [
        1=>['id'=>1,'name'=>'0-1 employees'],
            ['id'=>2,'name'=>'2-10 employees'],
            ['id'=>3,'name'=>'11-50 employees'],
            ['id'=>4,'name'=>'51-200 employees'],
            ['id'=>5,'name'=>'201-500 employees'],
            ['id'=>6,'name'=>'501-1000 employees'],
            ['id'=>7,'name'=>'1001-5000 employees'],
            ['id'=>8,'name'=>'5001-10000 employees'],
            ['id'=>9,'name'=>'10000+ employees'],

    ];
    /**
     * 企业类型
     * @var array
     */
    public static $company_type = [
        1=>['id'=>1,'name'=>'Public company'], //上市公司
            ['id'=>2,'name'=>'Educational institute'], //教育研究
            ['id'=>3,'name'=>'Self-employed'],  //个体经营
            ['id'=>4,'name'=>'Non-profit'],  //非营利组织
            ['id'=>5,'name'=>'Sole proprietorship'],  //独资企业
            ['id'=>6,'name'=>'Privately held'],  //私人
            ['id'=>7,'name'=>'partnership'],  //合伙
    ];
    /**
     * 每小时费用
     * @var array
     */
    public  static $hourly_rate = [
        '0'=> 'Any hourly rate',
        '1'=> '$10 and below',
        '2'=> '$10 - $30',
        '3'=> '$30 - $60',
        '4'=> '$60 and above',
    ];
    /**
     * 英语级别
     * @var array
     */
    public static $english_level = [
        '0'=>'Any english level',
        '1'=>'Basic',
        '2'=>'Conversational',
        '3'=>'Fluent',
        '4'=>'Native or Bilingual',
    ];
    /**
     * 获得金额
     * @var array
     */
    public static $earned_amount = [
        '0' => 'Any earned amount',
        '1' => '$1+ earned',
        '2' => '$100+ earned',
        '3' => '$1k+ earned',
        '4' => '$10k+ earned',
        '5' => 'No earning yet',
    ];
    /**
     * 项目好评率
     * @var array
     */
    public static $project_success = [
        '0' => 'Any project success',
        '1' => '80% & up',
        '2' => '90% & up'
    ];
    /**
     * 账号类型
     * @var array
     */
    public static $modeler_type = [
        ['id'=>0,'name'=>'Any freelancer type'],
        ['id'=>3,'name'=>'Independent freelancers'],
        ['id'=>4,'name'=>'Agency freelancers']
    ];
    /**
     * 上次活跃时间
     * @var array
     */
    public static $last_activity = [
        '0' => 'Any time',
        '1' => 'Last active within 2 Weeks',
        '2' => 'Last active within 1 Month',
        '3' => 'Last active within 2 Month'
    ];
    /**
     * 有无模型
     * @var array
     */
    public static $model_3D = [
        ['id'=>1,'name'=>'Have a model'],
        ['id'=>2,'name'=>'No model']
    ];
    /**
     * 有无视频
     * @var array
     */
    public static $video = [
        ['id'=>1,'name'=>'Have a video'],
        ['id'=>2,'name'=>'No video']
    ];
    /**
     * 下载权限
     * @var array
     */
    public static $download_permit = [
        ['id'=>1,'name'=>'Yes'],
        ['id'=>2,'name'=>'No']
    ];
    /**
     * 发布时间
     * @var array
     */
    public static $last_uploaded = [
        '0' => 'All',
        '1' => 'In 1 week',
        '2' => 'In 1 months',
        '3' => 'In 3 months'
    ];
    /**
     * 模型格式
     * @var array
     */
    public static $format = [
        ['id'=>'1','name'=>'.max'],
        ['id'=>'2','name'=>'.ma/mb'],
        ['id'=>'3','name'=>'.obj'],
        ['id'=>'4','name'=>'.3ds'],
        ['id'=>'5','name'=>'.c4d'],
        ['id'=>'6','name'=>'.fbx'],
        ['id'=>'7','name'=>'Other'],
    ];


/*中文*/
    /**
     * 模型精度
     * @var array
     */
    public static $resolution_zh = [
        1=>['id'=>1,'name'=>'高'],
        2=>['id'=>2,'name'=>'中'],
        3=>['id'=>3,'name'=>'低'],
    ];
    /**
     * 项目属性（公开or私密）
     * @var array
     */
    public static  $project_visibility_zh = [
        1=>['id'=>1,'name'=>'任何使用novaby.com的用户和搜索引擎都能看到这个项目'], //公开
        2=>['id'=>3,'name'=>'只有我邀请的艺术家才能看到这个项目']               //私密
    ];
    /**
     * 周期
     * @var array
     */
    public static $expect_times_zh = [
        5=>['id'=>5, 'name'=>'超过6个月'],
        4=>['id'=>4, 'name'=>'3-6个月'],
        3=>['id'=>3, 'name'=>'1-3个月'],
        2=>['id'=>2, 'name'=>'少于1个月'],
        1=>['id'=>1, 'name'=>'少于1星期'],
    ];
    /**
     * 单价
     * @var array
     */
    public static $budget_zh = [
        1=>['id'=>1, 'name'=>'1-200'],
        2=>['id'=>2, 'name'=>'201-500'],
        3=>['id'=>3, 'name'=>'501-1000'],
        4=>['id'=>4, 'name'=>'1001-2000'],
        5=>['id'=>5, 'name'=>'超过2000']
    ];
    /**
     * 工作经验
     * @var array
     */
    public static $work_exp_zh = [
        '0'=>'',
        '1'=>'1-3年',
        '2'=>'3-5年',
        '3'=>'超过5年'
    ];
    /**
     * 企业规模
     * @var array
     */
    public static $company_size_zh = [
        1=> ['id'=>1,'name'=>'0-1人'],
            ['id'=>2,'name'=>'2-10人'],
            ['id'=>3,'name'=>'11-50人'],
            ['id'=>4,'name'=>'51-200人'],
            ['id'=>5,'name'=>'201-500人'],
            ['id'=>6,'name'=>'501-1000人'],
            ['id'=>7,'name'=>'1001-5000人'],
            ['id'=>8,'name'=>'5001-10000人'],
            ['id'=>9,'name'=>'10000+人'],
    ];
    /**
     * 企业类型
     * @var array
     */
    public static $company_type_zh = [
        1=> ['id'=>1,'name'=>'上市公司'], //上市公司
            ['id'=>2,'name'=>'教育机构'], //教育研究
            ['id'=>3,'name'=>'个体户'],  //个体经营
            ['id'=>4,'name'=>'公益组织'],  //非营利组织
            ['id'=>5,'name'=>'独资企业'],  //独资企业
            ['id'=>6,'name'=>'私人公司'],  //私人
            ['id'=>7,'name'=>'合资公司'],  //合伙
    ];
    /**
     * 每小时费用
     * @var array
     */
    public  static $hourly_rate_zh = [
        '0'=> '不限制时薪',
        '1'=> '不高于10美元',
        '2'=> '10美元-30美元',
        '3'=> '30美元-60美元',
        '4'=> '60美元及其以上',
    ];
    /**
     * 英语级别
     * @var array
     */
    public static $english_level_zh = [
        '0'=>'不限制英语水平',
        '1'=>'初级英语水平',
        '2'=>'可以对话',
        '3'=>'流利',
        '4'=>'英语母语',
    ];
    /**
     * 获得金额
     * @var array
     */
    public static $earned_amount_zh = [
        '0' => '不限制总收益值',
        '1' => '1美元以上',
        '2' => '100美元以上',
        '3' => '1000美元以上',
        '4' => '10000美元以上',
        '5' => '还没有收益',
    ];
    /**
     * 项目成功率
     * @var array
     */
    public static $project_success_zh = [
        '0' => '不限制成功率',
        '1' => '80%及以上',
        '2' => '90%及以上'
    ];
    /**
     * 账号类型
     * @var array
     */
    public static $modeler_type_zh = [
        ['id'=>0,'name'=>'任意模型师类别'],
        ['id'=>3,'name'=>'独立模型师'],
        ['id'=>4,'name'=>'公司模型师']
    ];
    /**
     * 上次活跃时间
     * @var array
     */
    public static $last_activity_zh = [
        '0' => '任意时间',
        '1' => '最近两周登录过',
        '2' => '最近1个月登录过',
        '3' => '最近2个月登录过'
    ];
    /**
     * 有无模型
     * @var array
     */
    public static $model_3D_zh = [
        ['id'=>1,'name'=>'有3D'],
        ['id'=>2,'name'=>'无3D']
    ];
    /**
     * 有无视频
     * @var array
     */
    public static $video_zh = [
        ['id'=>1,'name'=>'有视频'],
        ['id'=>2,'name'=>'无视频']
    ];
    /**
     * 下载权限
     * @var array
     */
    public static $download_permit_zh = [
        ['id'=>1,'name'=>'是'],
        ['id'=>2,'name'=>'否']
    ];
    /**
     * 发布时间
     * @var array
     */
    public static $last_uploaded_zh = [
        '0' => '全部',
        '1' => '1周以内',
        '2' => '1月以内',
        '3' => '3月以内'
    ];
    /**
     * 模型格式
     * @var array
     */
    public static $format_zh = [
        ['id'=>'1','name'=>'.max'],
        ['id'=>'2','name'=>'.ma/mb'],
        ['id'=>'3','name'=>'.obj'],
        ['id'=>'4','name'=>'.3ds'],
        ['id'=>'5','name'=>'.c4d'],
        ['id'=>'6','name'=>'.fbx'],
        ['id'=>'7','name'=>'其它'],
    ];

    /**
     * 商业项目
     * @var array
     */
    public static $feedback_outsourcing = [
            ['id'=>0,'name'=>'All'],
            ['id'=>1,'name'=>'Client'],
            ['id'=>2,'name'=>'Modeler'],
        ];

    /**商业项目中文
     * @var array
     */
    public static $feedback_outsourcing_zh = [
            ['id'=>0,'name'=>'全部'],
            ['id'=>1,'name'=>'甲方'],
            ['id'=>2,'name'=>'团队'],
        ];
    /**
     * 标注状态
     * @var array
     */
    public static $status = [
        ['id'=>0,'name'=>'Pending'],
        ['id'=>1,'name'=>'Processing'],
        ['id'=>2,'name'=>'Completed'],
        ['id'=>3,'name'=>'Abort'],
        ['id'=>4,'name'=>'Re-Open'],
    ];

    /**标注状态中文
     * @var array
     */
    public static $status_zh = [
        ['id'=>0,'name'=>'等待中'],
        ['id'=>1,'name'=>'处理中'],
        ['id'=>2,'name'=>'已完成'],
        ['id'=>3,'name'=>'已终止'],
        ['id'=>4,'name'=>'重新处理'],
    ];
    /**
     * 标注审核
     * @var array
     */
    public static $audition = [
        ['id'=>0,'name'=>'Auditing'],
        ['id'=>1,'name'=>'Approval'],
        ['id'=>2,'name'=>'Reverse'],
    ];

    /**标注审核中文
     * @var array
     */
    public static $audition_zh = [
        ['id'=>0,'name'=>'等待审核'],
        ['id'=>1,'name'=>'同意'],
        ['id'=>2,'name'=>'拒绝'],
    ];

    const services =
    [
        1=>'internalProjects',
    ];
}