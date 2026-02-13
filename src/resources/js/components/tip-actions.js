import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

$(() => {
    /**
     * 좋아요 기능
     */
    // Laravel 레이아웃의 meta 태그에서 CSRF 토큰 1회 조회
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    // 같은 tip_id에 대한 중복 요청 방지용 잠금 객체
    const pendingByTipId = Object.create(null);

    // 모든 AJAX 요청에 공통 헤더 적용
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    });

    // 같은 tip_id를 가진 좋아요 버튼들을 모두 찾는다(피드/상세 동시 갱신용)
    function getButtonsByTipId(tipId) {
        return $(`[data-tip-action="like"][data-tip-id="${tipId}"]`);
    }

    // 좋아요 토글 API 호출
    function likeTip(tipId) {
        return $.ajax({
            url: `/tip/like/${encodeURIComponent(tipId)}`,
            method: 'POST',
        });
    }

    window.likeTip = likeTip;

    // 이벤트 위임: 동적으로 렌더링되는 버튼도 동일하게 처리
    $(document).on('click', '[data-tip-action="like"]', function (event) {
        event.preventDefault();

        const $clicked = $(this);
        const tipId = String($clicked.data('tipId') ?? '');

        if (!tipId) {
            console.warn('좋아요 버튼에 data-tip-id가 없습니다.');
            return;
        }

        if (pendingByTipId[tipId]) {
            return;
        }

        pendingByTipId[tipId] = true;

        const $buttons = getButtonsByTipId(tipId);
        $buttons.prop('disabled', true);

        likeTip(tipId)
            .done((response) => {
                const liked = Boolean(response?.liked);
                const likeCount = Number(response?.like_count ?? 0);

                // 같은 tip_id 버튼의 상태를 모두 동기화
                $buttons
                    .attr('aria-pressed', liked ? 'true' : 'false')
                    .toggleClass('is-liked', liked);

                if (Number.isFinite(likeCount)) {
                    $buttons.find('[data-like-count]').text(likeCount.toLocaleString());
                }
            })
            .fail((xhr) => {
                if (xhr?.status === 401) {
                    const redirect = encodeURIComponent(window.location.pathname + window.location.search);
                    window.location.href = `/login?redirect=${redirect}`;
                    return;
                }

                const message =
                    xhr?.responseJSON?.message ??
                    (xhr?.status ? `좋아요 처리 실패 (HTTP ${xhr.status})` : '좋아요 처리 실패');
                alert(message);
            })
            .always(() => {
                pendingByTipId[tipId] = false;
                $buttons.prop('disabled', false);
            });
    });


    /**
     * 공유 기능
     */
    $(document).on('click', '[data-tip-action="share"]', async function (event) {
        event.preventDefault();
        
        const $btn = $(this);
        const shareData = {
            title : $btn.data('title'),
            text : $btn.data('text'),
            url : $btn.data('url')
        };

        try{
            if(navigator.share && (!navigator.canShare || navigator.canShare(shareData))){
                await navigator.share(shareData);
                console.log('ok')
                return ;
            }
            await navigator.clipboard.writeText(shareData.url); // 공유 미지원
            alert('공유가 완료되었습니다.');
        }catch(err){
            if (err.name !== 'AbortError') console.error(err);
        }
    });

});
