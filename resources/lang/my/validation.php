<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute ကို လက်ခံရပါမည်။',
    'accepted_if' => ':other သည် :value ဖြစ်သောအခါ :attribute ကို လက်ခံရပါမည်။',
    'active_url' => ':attribute သည် မှန်ကန်သော URL မဟုတ်ပါ။',
    'after' => ':attribute သည် :date ပြီးနောက် ရက်စွဲတစ်ခု ဖြစ်ရပါမည်။',
    'after_or_equal' => ':attribute သည် :date ပြီးနောက် သို့မဟုတ် ညီမျှသော ရက်စွဲဖြစ်ရမည်။',
    'alpha' => ':attribute တွင် စာလုံးများသာ ပါဝင်ရပါမည်။',
    'alpha_dash' => ':attribute တွင် စာလုံးများ၊ နံပါတ်များ၊ ဒက်ရှ်များနှင့် အောက်အမှတ်များသာ ပါဝင်ရပါမည်။',
    'alpha_num' => ':attribute တွင် စာလုံးများနှင့် နံပါတ်များသာ ပါဝင်ရပါမည်။',
    'array' => ':attribute သည် array တစ်ခု ဖြစ်ရမည်။',
    'before' => ':attribute သည် :date မတိုင်မီ ရက်စွဲတစ်ခု ဖြစ်ရမည်။',
    'before_or_equal' => ':attribute သည် :date မတိုင်မီ သို့မဟုတ် ညီမျှသော ရက်စွဲတစ်ခု ဖြစ်ရမည်။',
    'between' => [
        'numeric' => ':attribute သည် :min နှင့် :max ကြား ဖြစ်ရမည်။',
        'file' => ':attribute သည် :min နှင့် :max ကီလိုဘိုက်ကြားရှိရမည်။',
        'string' => ':attribute သည် :min နှင့် :max စာလုံးများကြား ဖြစ်ရမည်။',
        'array' => ':attribute တွင် :min နှင့် :max အရာများကြားရှိရမည်။',
    ],
    'boolean' => ':attribute အကွက်သည် မှန် သို့မဟုတ် အမှား ဖြစ်ရမည်။',
    'confirmed' => ':attribute အတည်ပြုချက်နှင့် မကိုက်ညီပါ။',
    'current_password' => 'စကားဝှက် မမှန်ပါ။',
    'date' => ':attribute သည် တရားဝင်ရက်စွဲမဟုတ်ပါ။',
    'date_equals' => ':attribute သည် :date နှင့် ညီမျှသော ရက်စွဲဖြစ်ရမည်။',
    'date_format' => ':attribute သည် :format ဖော်မတ်နှင့် မကိုက်ညီပါ။',
    'declined' => ':attribute ကို ငြင်းပယ်ရပါမည်။',
    'declined_if' => ':other သည် :value ဖြစ်သောအခါ :attribute အား ငြင်းပယ်ရပါမည်။',
    'different' => ':attribute နှင့် :other သည် ကွဲပြားရပါမည်။',
    'digits' => ':attribute သည် :digits ဂဏန်းများ ဖြစ်ရမည်။',
    'digits_between' => ':attribute သည် :min နှင့် :max ဂဏန်းများအကြား ဖြစ်ရမည်။',
    'dimensions' => ':attribute တွင် မမှန်ကန်သော ပုံအတိုင်းအတာများ ရှိသည်။',
    'distinct' => ':attribute အကွက်တွင် ထပ်နေသောတန်ဖိုးတစ်ခုရှိသည်။',
    'email' => ':attribute သည် တရားဝင်အီးမေးလ်လိပ်စာဖြစ်ရမည်။',
    'ends_with' => ':attribute သည် အောက်ပါအရာများထဲမှ တစ်ခုနှင့် အဆုံးသတ်ရပါမည်- :values။',
    'enum' => 'ရွေးချယ်ထားသော :attribute သည် မမှန်ကန်ပါ။',
    'exists' => 'ရွေးချယ်ထားသော :attribute သည် မမှန်ကန်ပါ။',
    'file' => ':attribute သည် ဖိုင်ဖြစ်ရမည်။',
    'filled' => ':attribute အကွက်တွင် တန်ဖိုးတစ်ခုရှိရမည်။',
    'gt' => [
        'numeric' => ':attribute သည် :value ထက် ကြီးရမည်။',
        'file' => ':attribute သည် :value ကီလိုဘိုက်ထက် ကြီးရမည်။',
        'string' => ':attribute သည် :value စာလုံးထက် ကြီးရမည်။',
        'array' => ':attribute တွင် :value ခုထက်ပိုသော အရာများ ရှိရပါမည်။',
    ],
    'gte' => [
        'numeric' => ':attribute သည် :value ထက် ကြီးသည် သို့မဟုတ် ညီမျှရပါမည်။',
        'file' => ':attribute သည် :value ကီလိုဘိုက်ထက် ကြီးသည် သို့မဟုတ် ညီမျှရပါမည်။',
        'string' => ':attribute သည် စာလုံး :value ထက်ကြီးရမည် သို့မဟုတ် ညီရမည်။',
        'array' => ':attribute တွင် :value အရာများ သို့မဟုတ် ထို့ထက်ပို၍ ရှိရမည်။',
    ],
    'image' => ':attribute သည် ပုံဖြစ်ရမည်။',
    'in' => 'ရွေးချယ်ထားသော :attribute သည် မမှန်ကန်ပါ။',
    'in_array' => ':attribute အကွက်သည် :other တွင် မရှိပါ။',
    'integer' => ':attribute သည် ကိန်းပြည့်ဖြစ်ရမည်။',
    'ip' => ':attribute သည် တရားဝင် IP လိပ်စာဖြစ်ရမည်။',
    'ipv4' => ':attribute သည် တရားဝင် IPv4 လိပ်စာဖြစ်ရမည်။',
    'ipv6' => ':attribute သည် တရားဝင် IPv6 လိပ်စာဖြစ်ရမည်။',
    'json' => ':attribute သည် တရားဝင် JSON စာကြောင်းဖြစ်ရပါမည်။',
    'lt' => [
        'numeric' => ':attribute သည် :value ထက်နည်းရမည်။',
        'file' => ':attribute သည် :value ကီလိုဘိုက်ထက်နည်းရမည်။',
        'string' => ':attribute သည် :value စာလုံးထက်နည်းရမည်။',
        'array' => ':attribute တွင် :value ထက်နည်းသော အရာများ ရှိရပါမည်။',
    ],
    'lte' => [
        'numeric' => ':attribute သည် :value ထက်နည်းရမည် သို့မဟုတ် ညီမျှရမည်။',
        'file' => ':attribute သည် :value ကီလိုဘိုက်ထက်နည်းရမည် သို့မဟုတ် ညီမျှရမည်။',
        'string' => ':attribute သည် စာလုံး :value ထက်နည်းရမည် သို့မဟုတ် ညီရမည်။',
        'array' => ':attribute တွင် :value အရာများထက် မပိုရပါ။',
    ],
    'mac_address' => ':attribute သည် တရားဝင် MAC လိပ်စာဖြစ်ရမည်။',
    'max' => [
        'numeric' => ':attribute သည် :max ထက်မကြီးရပါ။',
        'file' => ':attribute သည် :max ကီလိုဘိုက်ထက်မကြီးရပါ။',
        'string' => ':attribute သည် အက္ခရာ :max ထက်မကြီးရပါ။',
        'array' => ':attribute တွင် :max အရာများထက် မပိုရပါ။',
    ],
    'mimes' => ':attribute သည် ဖိုင်အမျိုးအစားဖြစ်သည်- :values ဖြစ်ရမည်။',
    'mimetypes' => ':attribute သည် ဖိုင်အမျိုးအစားဖြစ်သည်- :values ဖြစ်ရမည်။',
    'min' => [
        'numeric' => ':attribute သည် အနည်းဆုံး :min ဖြစ်ရပါမည်။',
        'file' => ':attribute သည် အနည်းဆုံး :min ကီလိုဘိုက်ရှိရမည်။',
        'string' => ':attribute သည် အနည်းဆုံး :min စာလုံး ဖြစ်ရမည်။',
        'array' => ':attribute တွင် အနည်းဆုံး :min အရာများ ရှိရမည်။',
    ],
    'multiple_of' => ':attribute သည် :value ၏ တိုးကိန်းဖြစ်ရပါမည်။',
    'not_in' => 'ရွေးချယ်ထားသော :attribute သည် မမှန်ကန်ပါ။',
    'not_regex' => ':attribute ဖော်မတ်သည် မမှန်ကန်ပါ။',
    'numeric' => ':attribute သည် နံပါတ်တစ်ခု ဖြစ်ရပါမည်။',
    'password' => 'စကားဝှက် မမှန်ပါ။',
    'present' => ':attribute အကွက်သည် ရှိနေရပါမည်။',
    'prohibited' => ':attribute အကွက်ကို တားမြစ်ထားသည်။',
    'prohibited_if' => ':other သည် :value ဖြစ်သောအခါ :attribute အကွက်ကို တားမြစ်ထားသည်။',
    'prohibited_unless' => ':attribute အကွက်သည် :other တွင် :values မဟုတ်ပါက :attribute အကွက်ကို တားမြစ်ထားသည်။',
    'prohibits' => ':attribute အကွက်သည် :other ရှိနေခြင်းမှ တားမြစ်ထားသည်။',
    'regex' => ':attribute ဖော်မတ်သည် မမှန်ကန်ပါ။',
    'required' => ':attribute အကွက် လိုအပ်သည်။',
    'required_array_keys' => ':attribute အကွက်တွင်- :values အတွက် ထည့်သွင်းမှုများ ပါဝင်ရပါမည်။',
    'required_if' => ':other သည် :value ဖြစ်သောအခါ :attribute အကွက် လိုအပ်သည်။',
    'required_unless' => ':other သည် :values တွင်မဟုတ်ပါက :attribute အကွက် လိုအပ်ပါသည်။',
    'required_with' => ':values ရှိနေသောအခါတွင် :attribute အကွက် လိုအပ်သည်။',
    'required_with_all' => ':values ရှိနေသောအခါတွင် :attribute အကွက် လိုအပ်သည်။',
    'required_without' => ':values မရှိသည့်အခါ :attribute အကွက် လိုအပ်သည်။',
    'required_without_all' => ':values တစ်ခုမှ မရှိသည့်အခါ :attribute အကွက် လိုအပ်ပါသည်။',
    'same' => ':attribute နှင့် :other သည် တူညီရပါမည်။',
    'size' => [
        'numeric' => ':attribute သည် :size ဖြစ်ရမည်။',
        'file' => ':attribute သည် :size ကီလိုဘိုက် ဖြစ်ရမည်။',
        'string' => ':attribute သည် :size စာလုံးဖြစ်ရမည်။',
        'array' => ':attribute တွင် :size အရာများ ပါဝင်ရမည်။',
    ],
    'starts_with' => ':attribute သည် အောက်ပါအရာများထဲမှ တစ်ခုနှင့် စတင်ရပါမည်- :values။',
    'string' => ':attribute သည် စာကြောင်းတစ်ခု ဖြစ်ရမည်။',
    'timezone' => ':attribute သည် တရားဝင် အချိန်ဇုန် ဖြစ်ရမည်။',
    'unique' => ':attribute ကို ယူထားပြီးသား။',
    'uploaded' => ':attribute သည် အပ်လုဒ်လုပ်၍မရပါ။',
    'url' => ':attribute သည် တရားဝင် URL ဖြစ်ရမည်။',
    'uuid' => ':attribute သည် တရားဝင် UUID ဖြစ်ရမည်။',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'စိတ်ကြိုက်စာတို',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
