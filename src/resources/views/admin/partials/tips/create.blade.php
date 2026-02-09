@include('tips.partials.tipform', [
    'formAction' => $formAction ?? '',
    'data' => $data ?? null,
    'backUrl' => route('admin', ['tab' => 'tips']),
    'submitLabel' => '게시하기',
    'categories' => $categories ?? collect(),
])
