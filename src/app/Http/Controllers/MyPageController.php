<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\TipService;
use App\Services\UserNotificationService;


class MyPageController extends Controller
{
    public function __construct(
        private TipService $tip_service,
        private UserNotificationService $userNotificationService,
    )
    {
    }

    public function index(Request $request, ?string $tab = null) : View{
        $user = Auth()->user();
        $user_id = Auth()->id();
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

        switch($tab){
            case 'mytips' : 
                $viewData['tips'] = $this->tip_service->getMyTips($request);
                $viewData['myTipcategories'] = $this->tip_service->userTipsCategory($user_id);
                $viewData['myTipTags'] = $this->tip_service->userTipTags($user_id);
                break;
            case 'myarchive' :
                $viewData = array_merge(
                    $viewData,
                    $this->tip_service->getMyArchiveViewData()
                );
                break;
            case 'notifications' :
                if ($user !== null) {
                    $status = (string) $request->query('status', 'all');
                    $type = (string) $request->query('type', 'all');

                    $viewData = array_merge(
                        $viewData,
                        $this->userNotificationService->getBoardData($user, $status, $type)
                    );
                }
                break;
        }

        return view('mypage.dashboard', $viewData);
    }
}
        
