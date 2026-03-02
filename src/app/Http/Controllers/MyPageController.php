<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;



class MyPageController extends Controller
{
    public function index(Request $request, ?string $tab = null) : View{
        $tabs = config('mypage.tabs',[]);
        $defaultTab = array_key_first($tabs) ?? 'profile';
        $tab = $tab ?? $request->route('tab') ?? $defaultTab;
        if (! array_key_exists($tab, $tabs)) {
            $tab = $defaultTab;
        }
        $viewData = [
            'tab' => $tab,
            'headerTitle' => $tabs[$tab] ?? 'My Page',
            'tabView' => 'mypage.partials.' . $tab,
            'user' => $request->user(),
        ];

        // switch($tab){
        //     case 'mytips' : 
        //         $viewData = null;
        //         break;
        // }

        return view('mypage.dashboard', $viewData);
    }
}
