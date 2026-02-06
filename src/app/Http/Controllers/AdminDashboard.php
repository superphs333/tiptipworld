<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\User;
use App\Models\Status;
use App\Models\Role;

class AdminDashboard extends Controller
{
    public function index(Request $request, ?string $tab = null)
    {
        $tabs = config('admin.tabs', []);
        $defaultTab = array_key_first($tabs) ?? 'users';
        $tab = $this->resolveTab($request, $tab, $tabs, $defaultTab);
        $datas = $this->getDatas($tab, $request);

        if ($tab === 'categories') {
            session(['categories.query' => $request->query()]);
        }

        if ($tab === 'users') {
            session(['users.query' => $request->query()]);
        }

        if($tab === 'tags'){
            session(['tags.query' => $request->query()]);
        }


        $viewArray = [
            'tab' => $tab,
            'headerTitle' => $tabs[$tab] ?? 'Admin',
            'tabView' => 'admin.partials.' . $tab,
            'datas' => $datas,
        ];

        if($tab == 'users'){
            $viewArray['statuses'] = Status::getStatuses();
            $viewArray['roles'] = Role::getAllRoles();
        }

        return view('admin.dashboard', $viewArray);
    }

    private function resolveTab(Request $request, ?string $routeTab, array $tabs, string $defaultTab): string
    {
        $tab = $routeTab ?? $request->query('tab', $defaultTab);
        if (!array_key_exists($tab, $tabs)) {
            return $defaultTab;
        }
        return $tab;
    }

    private function getDatas(string $tab, Request $request): mixed
    {
        return match ($tab) {
            'categories' => Category::query()
            ->filter($request->query('is_active'), $request->query('name'))
            ->orderBy('sort_order','asc')
            ->orderBy('id')
            ->get(),
            'users' => User::getUsers(
                $request->only(['provider', 'query', 'status', 'role']),
                $this->resolvePerPage($request),
            ),
            default => null,
        };
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        if ($perPage < 1) {
            return 20;
        }

        return min($perPage, 100);
    }
  

}
