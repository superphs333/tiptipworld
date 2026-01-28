<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminDashboard extends Controller
{
    public function index(Request $request)
    {
        $tabs = [
            'users' => 'User',
            'categories' => 'Categories',
            'tags' => 'Tags',
            'tips' => 'Tips',
        ];
        $tab = $request->query('tab', 'users');
        if (!array_key_exists($tab, $tabs)) {
            $tab = 'users';
        }

        return view('admin.dashboard', [
            'tab' => $tab,
            'headerTitle' => $tabs[$tab],
            'tabView' => 'admin.partials.' . $tab,
        ]);
    }
}
