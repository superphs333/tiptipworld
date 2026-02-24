<?php

namespace App\Http\Controllers;

use App\Services\HomeViewService;

class HomeController extends Controller
{
    public function index()
    {
        $popular_tips = HomeViewService::getpopularList();
        $popular_tags = HomeViewService::getPopularTags();
        $all_categories = HomeViewService::getAllCategories();


        return view('home.home', [
            'popular_tips' => $popular_tips,
            'popular_tags' => $popular_tags,
            'categories' => $all_categories
        ]);
    }
}
