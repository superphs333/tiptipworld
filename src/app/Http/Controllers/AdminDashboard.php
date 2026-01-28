<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminDashboard extends Controller
{
    public function index(Request $request, ?string $tab = null)
    {
        $tabs = config('admin.tabs', []);
        $defaultTab = array_key_first($tabs) ?? 'users';
        $tab = $this->resolveTab($request, $tab, $tabs, $defaultTab);

        return view('admin.dashboard', [
            'tab' => $tab,
            'headerTitle' => $tabs[$tab] ?? 'Admin',
            'tabView' => 'admin.partials.' . $tab,
        ]);
    }

    private function resolveTab(Request $request, ?string $routeTab, array $tabs, string $defaultTab): string
    {
        $tab = $routeTab ?? $request->query('tab', $defaultTab);
        if (!array_key_exists($tab, $tabs)) {
            return $defaultTab;
        }
        return $tab;
    }
}
