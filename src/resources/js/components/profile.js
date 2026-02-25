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

            }, 
            error : function(xhr){

            }
        });
    });


});