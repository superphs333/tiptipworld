<?php

return [
    'providers' => [
        'google' => [
            'name' => 'Google',
            'description' => 'Google 계정으로 로그인하고 계정 복구 수단으로 사용할 수 있습니다.',
            'icon' => 'G',
            'icon_class' => 'border border-slate-200 bg-white text-slate-700',
            'button_class' => 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-indigo-500',
            'avatar_filename' => 'google-profile',
            'login_error' => '구글 로그인에 실패했습니다. 다시 시도해 주세요.',
            'lookup_by_email' => true,
            'missing_email_error' => '구글 계정 이메일을 가져올 수 없습니다.',
            'requires_email' => true,
            'scopes' => ['openid', 'email', 'profile'],
            'verify_email' => true,
            'with' => ['access_type' => 'offline'],
        ],
        'kakao' => [
            'name' => '카카오',
            'description' => '카카오 계정으로 빠르게 로그인할 수 있도록 연결합니다.',
            'icon' => 'K',
            'icon_class' => 'border border-yellow-200 bg-yellow-300 text-yellow-950',
            'button_class' => 'border-yellow-300 bg-yellow-300 text-yellow-950 hover:bg-yellow-400 focus:ring-yellow-500',
            'avatar_filename' => 'kakao-profile',
            'login_error' => '카카오 로그인에 실패했습니다. 다시 시도해 주세요.',
            'lookup_by_email' => true,
            'missing_email_error' => '카카오 계정 이메일을 가져올 수 없습니다.',
            'requires_email' => true,
            'scopes' => ['account_email', 'profile_nickname', 'profile_image'],
            'verify_email' => false,
            'with' => [],
        ],
    ],
];
