@props(['item' => []])

<tr
    @class(['mytips-row-link' => filled(data_get($item, 'detail_url'))])
    @if (filled(data_get($item, 'detail_url')))
        data-mytips-row-link
        data-href="{{ data_get($item, 'detail_url') }}"
        tabindex="0"
        role="link"
        aria-label="팁 상세 보기: {{ data_get($item, 'title') }}"
    @endif
>
    <td class="mytips-id">{{ data_get($item, 'id') }}</td>
    <td>
        <div class="mytips-thumb">
            @if (data_get($item, 'thumbnail_url'))
                <img src="{{ data_get($item, 'thumbnail_url') }}" alt="" />
            @else
                <span>TT</span>
            @endif
        </div>
    </td>
    <td>
        <div class="mytips-title-stack">
            <div class="mytips-chip">{{ data_get($item, 'category.name', '미분류') }}</div>
            <div class="mytips-title">"{{ data_get($item, 'title') }}"</div>

            @if (!empty(data_get($item, 'tags', [])))
                <div class="mytips-tag-row">
                    @foreach (data_get($item, 'tags', []) as $tag)
                        <span class="mytips-chip {{ data_get($tag, 'is_alert') ? 'mytips-chip--alert' : '' }}">
                            {{ data_get($tag, 'label') }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </td>
    <td class="mytips-metric">{{ data_get($item, 'metrics.views_text', '0') }}</td>
    <td class="mytips-metric">{{ data_get($item, 'metrics.likes_text', '0') }}</td>
    <td class="mytips-metric">{{ data_get($item, 'metrics.bookmarks_text', '0') }}</td>
    <td>
        <span class="mytips-badge mytips-badge--{{ data_get($item, 'visibility.tone', 'mint') }}">
            {{ data_get($item, 'visibility.label', '공개') }}
        </span>
    </td>
    <td>
        <span class="mytips-badge mytips-badge--{{ data_get($item, 'status.tone', 'gray') }}">
            {{ data_get($item, 'status.label', '-') }}
        </span>
    </td>
    <td class="mytips-date">{{ data_get($item, 'date_text', '-') }}</td>
</tr>
