@include('tips.partials.tipform', [
    'tone' => 'front',
    'formAction' => $formAction ?? route('tip.store'),
    'data' => $data ?? null,
    'backUrl' => route('home'),
    'submitLabel' => $submitLabel ?? '게시하기',
    'categories' => $categories ?? collect(),
])
