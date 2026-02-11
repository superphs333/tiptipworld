import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

function likeTip(tipId) {
    console.log('tip_id', tipId);
}

window.likeTip = likeTip;

$(() => {
    $(document).on('click', '.tip-wireframe__action-btn[data-tip-action="like"]', function (event) {
        event.preventDefault();

        const tipId = $(this).closest('[data-tip-wireframe]').data('tipId');

        if (!tipId) {
            console.warn('tipId not found for like action');
            return;
        }

        likeTip(String(tipId));
    });
});
