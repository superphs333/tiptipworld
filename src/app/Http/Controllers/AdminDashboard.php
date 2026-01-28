<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use PDO;

class AdminDashboard extends Controller
{
    public function index(Request $request, ?string $tab = null)
    {
        $tabs = config('admin.tabs', []);
        $defaultTab = array_key_first($tabs) ?? 'users';
        $tab = $this->resolveTab($request, $tab, $tabs, $defaultTab);
        $datas = $this->getDatas($tab, $request);

        return view('admin.dashboard', [
            'tab' => $tab,
            'headerTitle' => $tabs[$tab] ?? 'Admin',
            'tabView' => 'admin.partials.' . $tab,
            'datas' => $datas,
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

    private function getDatas($tab,$request) : mixed{

        $returnData = null;

        switch($tab){
            case 'categories' :
                $categoryModel = new Category();
                $returnData = $categoryModel->getCategories(
                    $request->query('is_active',null),
                    $request->query('name',null)
                );
            default : 
                $returnData = null;
        }

        return $returnData;
    }

}

