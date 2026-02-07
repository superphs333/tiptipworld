<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TipController extends Controller
{
    public function createForm()
    {
        $tabs = config('admin.tabs', []);
        $tab = 'tips';

        return view('admin.dashboard', [
            'tab' => $tab,
            'headerTitle' => $tabs[$tab] ?? 'Tips',
            'tabView' => 'admin.partials.tips.create',
            'datas' => null,
        ]);
    }
}
