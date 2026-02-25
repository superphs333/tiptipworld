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

});