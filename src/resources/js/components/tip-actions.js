import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;



$(() => {

    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    // 같은 tip_id 요청이 동시에 중복으로 나가지 않게 잠금 관리
    const pendingByTipId = Object.create(null);

    // 모든 AJAX 요청에 공통 헤더 설정
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',          
        },
    });

    function getButtonsByTipId(tipId) {
        return $(`[data-tip-action="like"][data-tip-id="${tipId}"]`);
    }



    $(document).on('click', '[data-tip-action="like"]', function (event) {
        event.preventDefault();

        const $clicked = $(this);
        const tipId = String($clicked.data('tipId') ?? '');

        if (!tipId) {
            console.warn('data-tip-id가 없습니다.');
            return;
        }

        if(pendingByTipId[tipId]){
            return;
        }

        $ajax({
            url : `/tip/like/${encodeURIComponent(tipId)}`,
            method : 'POST',
        })
            .done((reponse)=>{

            })
            .fail((xhr)=>{

            })
            .always(()=>{
                
            });
    });
});
