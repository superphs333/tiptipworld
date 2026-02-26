import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

$(() => {
    let userFeedSortXhr = null;

    // 유저피드 정렬 (리다이렉트 대신 부분 갱신)
    $(document).on('change', '#tip-userfeed-sort', function () {
        const $select = $(this);
        const $form = $select.closest('form');
        const $root = $select.closest('[data-tip-userfeed]');
        const $targetFeed = $root.find('.tip-userfeed__feed').first();
        const sortKey = String($select.val() || 'latest');

        if ($targetFeed.length === 0) {
            return;
        }

        const requestUrl = new URL($form.attr('action') || window.location.href, window.location.origin);
        requestUrl.searchParams.set('sort', sortKey);

        if (userFeedSortXhr) {
            userFeedSortXhr.abort();
            userFeedSortXhr = null;
        }

        $select.prop('disabled', true);
        $root.attr('aria-busy', 'true');

        userFeedSortXhr = $.ajax({
            url: requestUrl.toString(),
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .done((html) => {
                const $doc = $('<div>').append($.parseHTML(html, document, true));
                const $nextFeed = $doc.find('[data-tip-userfeed] .tip-userfeed__feed').first();

                if ($nextFeed.length === 0) {
                    window.location.assign(requestUrl.toString());
                    return;
                }

                $targetFeed.replaceWith($nextFeed);
                window.history.replaceState({}, '', requestUrl.toString());
            })
            .fail(() => {
                window.location.assign(requestUrl.toString());
            })
            .always(() => {
                $select.prop('disabled', false);
                $root.removeAttr('aria-busy');
                userFeedSortXhr = null;
            });
    });
});
