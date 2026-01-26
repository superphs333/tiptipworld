<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipRequest;
use App\Http\Requests\UpdateTipRequest;
use App\Http\Resources\TipResource;
use App\Models\Tip;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;


class TipController extends Controller
{
    // Default page size for the public list.
    private const PER_PAGE = 12;

    /**
     * 팁 목록 표시
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Tip::class);

        $tips = Tip::query()
            ->with('user')
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Tips/Index', [
            'tips' => TipResource::collection($tips),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tips/Create');
    }

    public function show(Tip $tip): Response
    {
        $this->authorize('view', $tip);

        $tip->load('user');
        
        return Inertia::render('Tips/Show', [
            'tip' => TipResource::make($tip),
        ]);
    }

    public function edit(Tip $tip): Response
    {
        $this->authorize('update', $tip);

        $tip->load('user');

        return Inertia::render('Tips/Edit', [
            'tip' => TipResource::make($tip),
        ]);
    }

    /**
     * 팁 저장
     */
    public function store(StoreTipRequest $request): RedirectResponse
    {
        $request->user()->tips()->create($request->validated());

        return redirect()->route('tips.index')->with('success', '팁이 성공적으로 작성되었습니다.');
    }

    public function update(UpdateTipRequest $request, Tip $tip): RedirectResponse
    {
        $tip->update($request->validated());

        return redirect()
            ->route('tips.show', $tip)
            ->with('success', '팁이 성공적으로 수정되었습니다.');
    }

    public function destroy(Tip $tip): RedirectResponse
    {
        $this->authorize('delete', $tip);

        $tip->delete();

        return redirect()
            ->route('tips.index')
            ->with('success', '팁이 성공적으로 삭제되었습니다.');
    }
}
