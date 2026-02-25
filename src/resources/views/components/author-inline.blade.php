@props([
    'name' => '작성자 미상',
    'avatar' => null,
    'authorId' => null,
    'profileUrl' => null,
    'variant' => 'list',
    'showFollow' => true,
    'followLabel' => '팔로우',
])

@php
    $allowedVariants = ['detail', 'list', 'card'];
    $resolvedVariant = in_array($variant, $allowedVariants, true) ? $variant : 'list';
    $avatarUrl = filled($avatar) ? $avatar : asset('images/avatar-default.svg');
    $resolvedAuthorId = is_numeric($authorId) ? (int) $authorId : null;
    if ($resolvedAuthorId !== null && $resolvedAuthorId <= 0) {
        $resolvedAuthorId = null;
    }
@endphp

<span
    @if ($resolvedAuthorId !== null)
        data-author-id="{{ $resolvedAuthorId }}"
    @endif
    {{ $attributes->class(['author-inline', "author-inline--{$resolvedVariant}"]) }}
>
    @if (filled($profileUrl))
        <a class="author-inline__profile" href="{{ $profileUrl }}" aria-label="{{ $name }} 프로필">
            <img class="author-inline__avatar" src="{{ $avatarUrl }}" alt="{{ $name }} 프로필" loading="lazy">
            <span class="author-inline__name">{{ $name }}</span>
        </a>
    @else
        <span class="author-inline__profile author-inline__profile--static">
            <img class="author-inline__avatar" src="{{ $avatarUrl }}" alt="{{ $name }} 프로필" loading="lazy">
            <span class="author-inline__name">{{ $name }}</span>
        </span>
    @endif

    @if ($showFollow)
        <button type="button" class="author-inline__follow" aria-label="{{ $name }} 팔로우">{{ $followLabel }}</button>
    @endif
</span>
