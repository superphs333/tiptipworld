<?php

/**
 * 어떤 리스트를 어떤 규칙으로 가져올지 적어둔 설정 파일
 */

use App\Enums\TipSort;

return [
    'sort_options' => [
        TipSort::Latest->value => TipSort::Latest->label(),
        TipSort::Popular->value => TipSort::Popular->label(),
        TipSort::Likes->value => TipSort::Likes->label(),
        TipSort::Bookmarks->value => TipSort::Bookmarks->label(),
    ],

    'contexts' => [
        'public_search' => [
            'query_method' => 'publicSearchQuery',
            'presenter' => 'presentListItem',
            'result_mode' => 'paginate',
            'meta_profile' => 'search',
            'with_categories' => true,
        ],
        'category' => [
            'query_method' => 'categoryBaseQuery',
            'presenter' => 'presentListItem',
            'result_mode' => 'paginate',
            'meta_profile' => 'public_list',
            'sort_mode' => 'category',
            'apply_sort' => true,
        ],
        'tag' => [
            'query_method' => 'tagBaseQuery',
            'presenter' => 'presentListItem',
            'result_mode' => 'paginate',
            'meta_profile' => 'public_list',
            'sort_mode' => 'tag',
            'apply_sort' => true,
        ],
        'user_feed' => [
            'query_method' => 'userFeedBaseQuery',
            'presenter' => 'presentCard',
            'result_mode' => 'collection',
            'meta_profile' => 'user_feed',
        ],
        'my_tips' => [
            'query_method' => 'myTipsBaseQuery',
            'presenter' => 'presentOwnerRow',
            'result_mode' => 'paginate',
            'meta_profile' => 'owner',
        ],
        'admin_tips' => [
            'query_method' => 'adminTipsBaseQuery',
            'presenter' => 'presentAdminRow',
            'result_mode' => 'paginate',
            'meta_profile' => 'admin',
            'with_categories' => true,
        ],
        'home_popular' => [
            'query_method' => 'homePopularQuery',
            'presenter' => 'presentCard',
            'result_mode' => 'collection',
            'meta_profile' => 'home_popular',
            'default_limit' => 10,
        ],
    ],
];
