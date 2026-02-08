@include('tips.partials.tipform', [
    'formAction' => '',
    'backUrl' => route('admin', ['tab' => 'tips']),
    'submitLabel' => '게시하기',
])
