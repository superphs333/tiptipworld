<?php

namespace App\Http\Controllers;

use App\Services\HomeViewService;

class HomeController extends Controller
{
    public function index()
    {
        $popular_tips = HomeViewService::getpopularList();

        return view('home.home', [
            'popular_tips' => $popular_tips,
        ]);
    }
}
