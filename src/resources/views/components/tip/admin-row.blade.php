@props(['item' => []])

<tr>
    <td>{{ data_get($item, 'id') }}</td>
    <td>
        <div class="tip-panel__thumb">
            @if (data_get($item, 'thumbnail_url'))
                <img src="{{ data_get($item, 'thumbnail_url') }}" alt="" />
            @else
                <span class="tip-panel__thumb-placeholder">img</span>
            @endif
        </div>
    </td>
    <td>
        <div class="tip-panel__meta-line">
            <span class="tip-panel__category">{{ data_get($item, 'category.name', '미분류') }}</span>
        </div>
        <div class="tip-panel__title-line">
            “{{ data_get($item, 'title') }}”
        </div>
        <div class="tip-panel__meta-line">
            @if (!empty(data_get($item, 'tags', [])))
                <span class="tip-panel__tags">
                    @foreach (data_get($item, 'tags', []) as $tag)
                        <span class="tip-panel__tag">{{ data_get($tag, 'label') }}</span>
                    @endforeach
                </span>
            @endif
        </div>
        <div class="tip-panel__actions">
            <a class="tip-panel__action" href="{{ data_get($item, 'edit_url') }}">편집</a>
            <form class="tip-panel__action-form" action="{{ data_get($item, 'delete_url') }}" method="POST" onsubmit="return confirm('정말 삭제할까요?')">
                @csrf
                @method('DELETE')
                <button class="tip-panel__action tip-panel__action--delete" type="submit">
                    <svg class="tip-panel__action-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7h16" stroke="currentColor" stroke-linecap="round"/>
                        <path d="M9 7V5h6v2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 7l1 12h8l1-12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 11v5M14 11v5" stroke="currentColor" stroke-linecap="round"/>
                    </svg>
                    <span>삭제</span>
                </button>
            </form>
        </div>
    </td>
    <td>{{ data_get($item, 'author.name', '작성자 미상') }}</td>
    <td>
        <span class="tip-panel__status tip-panel__status--visibility-{{ data_get($item, 'visibility.key', 'public') }}">
            {{ data_get($item, 'visibility.label', '공개') }}
        </span>
    </td>
    <td>
        <span class="tip-panel__status tip-panel__status--state-{{ data_get($item, 'status.key', 'unknown') }}">
            {{ data_get($item, 'status.label', '-') }}
        </span>
    </td>
    <td class="tip-panel__date">{{ data_get($item, 'date_text', '-') }}</td>
</tr>
