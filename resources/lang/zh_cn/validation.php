<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | following language lines contain default error messages used by
    | validator class. Some of these rules have multiple versions such
    | as size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute 必须同意.',
    'active_url'           => ':attribute 不是一个有效的URL.',
    'after'                => ':attribute must be a date after :date.',
    'alpha'                => ':attribute may only contain letters.',
    'alpha_dash'           => ':attribute may only contain letters, numbers, and dashes.',
    'alpha_num'            => ':attribute 必须是字母或数字.',
    'array'                => ':attribute must be an array.',
    'before'               => ':attribute must be a date before :date.',
    'between'              => [
        'numeric' => ':attribute 必须在 :min 和 :max 之间.',
        'file'    => ':attribute must be between :min and :max kilobytes.',
        'string'  => ':attribute must be between :min and :max characters.',
        'array'   => ':attribute must have between :min and :max items.',
    ],
    'boolean'              => ':attribute field must be true or false.',
    'confirmed'            => ':attribute confirmation does not match.',
    'date'                 => ':attribute is not a valid date.',
    'date_format'          => ':attribute does not match format :format.',
    'different'            => ':attribute and :other must be different.',
    'digits'               => ':attribute must be :digits digits.',
    'digits_between'       => ':attribute must be between :min and :max digits.',
    'dimensions'           => ':attribute has invalid image dimensions.',
    'distinct'             => ':attribute field has a duplicate value.',
    'email'                => ':attribute 必须是一个有效的邮箱地址.',
    'exists'               => 'selected :attribute is invalid.',
    'filled'               => ':attribute field is required.',
    'image'                => ':attribute must be an image.',
    'in'                   => 'selected :attribute is invalid.',
    'in_array'             => ':attribute field does not exist in :other.',
    'integer'              => ':attribute 必须是整数类型.',
    'ip'                   => ':attribute must be a valid IP address.',
    'json'                 => ':attribute must be a valid JSON string.',
    'max'                  => [
        'numeric' => ':attribute 不能大于 :max.',
        'file'    => ':attribute may not be greater than :max kilobytes.',
        'string'  => ':attribute may not be greater than :max characters.',
        'array'   => ':attribute may not have more than :max items.',
    ],
    'mimes'                => ':attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => ':attribute must be at least :min.',
        'file'    => ':attribute must be at least :min kilobytes.',
        'string'  => ':attribute must be at least :min characters.',
        'array'   => ':attribute must have at least :min items.',
    ],
    'not_in'               => 'selected :attribute is invalid.',
    'numeric'              => ':attribute 必须是数字.',
    'present'              => ':attribute field must be present.',
    'regex'                => ':attribute format is invalid.',
    'required'             => ':attribute 不能为空.',
    'required_if'          => ':attribute field is required when :other is :value.',
    'required_unless'      => ':attribute field is required unless :other is in :values.',
    'required_with'        => ':attribute field is required when :values is present.',
    'required_with_all'    => ':attribute field is required when :values is present.',
    'required_without'     => ':attribute field is required when :values is not present.',
    'required_without_all' => ':attribute field is required when none of :values are present.',
    'same'                 => ':attribute and :other must match.',
    'size'                 => [
        'numeric' => ':attribute must be :size.',
        'file'    => ':attribute must be :size kilobytes.',
        'string'  => ':attribute must be :size characters.',
        'array'   => ':attribute must contain :size items.',
    ],
    'string'               => ':attribute must be a string.',
    'timezone'             => ':attribute 必须是字符串.',
    'unique'               => ':attribute 已存在.',
    'url'                  => ':attribute format is invalid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'firstname' => '姓氏',
        'email'=>'邮箱',
        'password'=>'密码',
        'company_name'=>'公司名',
        'web'=>'网站',
        'year'=>'公司成立时间',
        'lastname' => '名字',
        'country' => '国家/地区',
        'city' => '城市',
        'fileds' => '专长',
        'year_founded' => '成立时间',
        'homeid' => '网站',
        'gender' => '性别',
        'hourly' => '时薪',
    ],

];
