import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

$(() => {
    // 피드로 이동 
    $(document).on('click', '.author-inline__name', function (e) {
        e.preventDefault();
        const authorId = $(this).closest('.author-inline').data('authorId'); 
        if(!Number.isInteger(authorId) || authorId<0) return;
        window.location.assign(`/tips/user/${authorId}`)
    });

    // 팔로우 
    $(document).on('click','.author-inline__follow',function(e){
        e.preventDefault();
        const $btn = $(this);
        const authorId = $(this).closest('.author-inline').data('authorId'); 
        if(!Number.isInteger(authorId) || authorId<0) return;

        // 팔로우 기능 
        $.ajax({
            url : '/user/follow/'+authorId,
            method : 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
            success : function(res){
                if(res.success){
                    const isFollowing = !!res.following;
                    const count = Number(res.followers_count ?? res.following_count ?? 0);

                    $btn.toggleClass('is-following', isFollowing)
                        .text(isFollowing ? '팔로잉' : '팔로우');

                    $('[data-followers-count]')
                        .attr('data-count', String(count))
                        .data('count', count)
                        .text(count.toLocaleString('ko-KR'));
                }
            }, 
            error : function(xhr){

            }
        });
    });


});
