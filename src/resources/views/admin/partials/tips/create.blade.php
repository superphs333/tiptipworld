@include('tips.partials.tipform', [
    'formAction' => $formAction ?? '',
    'backUrl' => route('admin', ['tab' => 'tips']),
    'submitLabel' => '게시하기',
    'categories' => $categories ?? collect(),
])
