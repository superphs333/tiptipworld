import $ from 'jquery';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

$(() => {
    // 댓글 섹션이 없는 페이지에서는 동작하지 않는다.
    const $section = $('#tip-comments');
    if (!$section.length) {
        return;
    }

    // 현재 상세 글의 tip_id
    const tipId = String($section.data('tipId') ?? '');
    if (!tipId) {
        return;
    }

    // 자주 쓰는 DOM 캐시
    const $list = $section.find('.tip-wireframe__comment-list');
    const $textarea = $section.find('#tip-wireframe-comment');
    const $submitBtn = $section.find('[data-tip-action="comment_add"]');
    const $cancelBtn = $section.find('[data-tip-action="comment_cancel"]');

    const DEFAULT_PLACEHOLDER = '댓글을 입력해주세요.';
    const DEFAULT_SUBMIT_LABEL = String($submitBtn.text() ?? '').trim() || '댓글 등록';
    const EDIT_PLACEHOLDER = '수정할 댓글을 입력해주세요.';
    const REPLY_SUBMIT_LABEL = '답글 등록';
    const EDIT_SUBMIT_LABEL = '댓글 수정';
    const commentMap = new Map();
    const pendingLikeByCommentId = Object.create(null);

    // CSRF 공통 헤더
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    });

    /**
     * API helpers
     */
    function getCommentList() {
        return $.ajax({
            url: `/tip/comment_list/${encodeURIComponent(tipId)}`,
            method: 'GET',
        });
    }

    function addComment(body, parentId = null, replyToId = null) {
        return $.ajax({
            url: `/tip/comment/${encodeURIComponent(tipId)}`,
            method: 'POST',
            data: {
                comment: body,
                parent_id: parentId,
                reply_to_id: replyToId,
            },
        });
    }

    function deleteComment(commentId) {
        return $.ajax({
            url: `/tip/comment/${encodeURIComponent(commentId)}`,
            method: 'DELETE',
        });
    }

    function likeComment(commentId) {
        return $.ajax({
            url: `/tip/comment/like/${encodeURIComponent(commentId)}`,
            method: 'POST',
        });
    }

    function editComment(commentId, body) {
        return $.ajax({
            url: `/tip/comment/${encodeURIComponent(commentId)}`,
            method: 'PATCH',
            data: {
                comment: body,
            },
        });
    }

    /**
     * UI helpers
     */
    function escapeHtml(value = '') {
        return String(value).replace(/[&<>"']/g, (m) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[m]));
    }

    function nl2br(value = '') {
        return escapeHtml(value).replace(/\n/g, '<br>');
    }

    function compactText(value = '') {
        return String(value).replace(/\s+/g, ' ').trim();
    }

    function toReplyPreview(value = '', maxLength = 18) {
        const compact = compactText(value);
        if (!compact) {
            return '';
        }

        return compact.length > maxLength
            ? `${compact.slice(0, maxLength)}...`
            : compact;
    }

    function parseNullableInt(rawValue) {
        if (rawValue === undefined || rawValue === null || rawValue === '') {
            return null;
        }

        const n = Number(rawValue);
        return Number.isInteger(n) && n > 0 ? n : null;
    }

    function getCommentLikeButtons(commentId) {
        return $(`#tip-comments [data-comment-action="like"][data-comment-id="${commentId}"]`);
    }

    function formatCommentTime(iso) {
        if (!iso) {
            return '';
        }

        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) {
            return '';
        }

        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}.${pad(d.getMonth() + 1)}.${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function handleUnauthorized(xhr) {
        if (xhr?.status !== 401) {
            return false;
        }

        const redirect = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `/login?redirect=${redirect}`;
        return true;
    }

    function resetComposer() {
        $textarea.val('').attr('placeholder', DEFAULT_PLACEHOLDER);
        $submitBtn.removeData('parentId');
        $submitBtn.removeData('replyToId');
        $submitBtn.removeData('editCommentId');
        $submitBtn.text(DEFAULT_SUBMIT_LABEL);
        $submitBtn.prop('disabled', false);
        $cancelBtn.prop('hidden', true);
    }

    function setReplyMode(parentId, replyToId, parentAuthor, parentPreview = '') {
        $submitBtn.data('parentId', parentId);
        $submitBtn.data('replyToId', replyToId);
        $submitBtn.removeData('editCommentId');
        $submitBtn.text(REPLY_SUBMIT_LABEL);
        $cancelBtn.prop('hidden', false);

        const previewLabel = parentPreview ? ` ${parentPreview}` : '';
        $textarea.val('').attr('placeholder', `${parentAuthor}${previewLabel}에게 답글 입력...`).focus();
    }

    function setEditMode(commentId, commentBody) {
        $submitBtn.removeData('parentId');
        $submitBtn.removeData('replyToId');
        $submitBtn.data('editCommentId', commentId);
        $submitBtn.text(EDIT_SUBMIT_LABEL);
        $cancelBtn.prop('hidden', false);

        $textarea.val(String(commentBody ?? '')).attr('placeholder', EDIT_PLACEHOLDER).focus();
    }

    /**
     * Render helpers
     */
    function renderDeleteButton(comment) {
        if (!comment.can_delete) {
            return '';
        }

        return `
            <button
                type="button"
                class="tip-wireframe__comment-action-btn is-danger"
                data-comment-action="delete"
                data-comment-id="${comment.id}"
            >삭제</button>
        `.trim();
    }

    function renderEditButton(comment) {
        if (!comment.can_edit) {
            return '';
        }

        return `
            <button
                type="button"
                class="tip-wireframe__comment-action-btn"
                data-comment-action="edit"
                data-comment-id="${comment.id}"
            >수정</button>
        `.trim();
    }

    function renderLikeButton(comment) {
        if (!comment.can_like) {
            return '';
        }

        const liked = Boolean(comment.is_liked);
        const likeCount = Number(comment.like_count ?? 0);
        const displayCount = Number.isFinite(likeCount) ? likeCount : 0;

        return `
            <button
                type="button"
                class="tip-wireframe__comment-like-btn ${liked ? 'is-liked' : ''}"
                data-comment-action="like"
                data-comment-id="${comment.id}"
                aria-pressed="${liked ? 'true' : 'false'}"
                aria-label="댓글 좋아요"
            >
                <span class="tip-wireframe__comment-like-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" focusable="false">
                        <path d="M12 19.2c-4.3-2.83-7.2-5.53-7.2-8.69 0-2.24 1.84-4.01 4.13-4.01 1.43 0 2.72.68 3.47 1.82.75-1.14 2.04-1.82 3.47-1.82 2.29 0 4.13 1.77 4.13 4.01 0 3.16-2.9 5.86-7.2 8.69Z" stroke-width="1.6" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span data-comment-like-count>${displayCount.toLocaleString()}</span>
            </button>
        `.trim();
    }

    function renderReplyButton(comment) {
        if (!comment.can_reply) {
            return '';
        }

        const threadParentId = parseNullableInt(comment.parent_id) ?? parseNullableInt(comment.id);
        const replyToId = parseNullableInt(comment.id);
        if (!threadParentId || !replyToId) {
            return '';
        }

        const safeAuthor = escapeHtml(comment.user_name || '작성자');
        const safePreview = escapeHtml(toReplyPreview(comment.body || ''));

        return `
            <button
                type="button"
                class="tip-wireframe__comment-reply-btn"
                data-comment-action="reply"
                data-comment-parent-id="${threadParentId}"
                data-comment-reply-to-id="${replyToId}"
                data-comment-author="${safeAuthor}"
                data-comment-preview="${safePreview}"
                aria-label="${safeAuthor} 댓글에 답글 달기"
            >답글 달기</button>
        `.trim();
    }

    function renderReplyItem(reply) {
        const author = escapeHtml(reply.user_name || '익명');
        const avatar = escapeHtml(reply.user_profile_image_url || '/images/avatar-default.svg');
        const body = nl2br(reply.body || '');
        const time = escapeHtml(formatCommentTime(reply.created_at));
        const replyToId = parseNullableInt(reply.reply_to_id);
        const parentId = parseNullableInt(reply.parent_id);
        const shouldShowTarget = replyToId !== null && parentId !== null && replyToId !== parentId;

        const targetName = escapeHtml(reply.reply_to_user_name || '작성자');
        const targetPreview = escapeHtml(reply.reply_to_body_preview || '');
        const target = targetPreview
            ? `${targetName} ${targetPreview}에게 답글`
            : `${targetName}에게 답글`;
        const targetHtml = shouldShowTarget
            ? `<p class="tip-wireframe__comment-target">${target}</p>`
            : '';

        return `
            <li class="tip-wireframe__reply-item" data-comment-id="${reply.id}" data-parent-id="${reply.parent_id ?? ''}">
                <article class="tip-wireframe__comment-card tip-wireframe__comment-card--reply ${reply.is_deleted ? 'is-deleted' : ''}">
                    <header class="tip-wireframe__comment-head">
                        <div class="tip-wireframe__comment-user">
                            <span class="tip-wireframe__comment-avatar">
                                <img src="${avatar}" alt="${author} 프로필" loading="lazy">
                            </span>
                            <strong class="tip-wireframe__comment-author">${author}</strong>
                        </div>
                        <time class="tip-wireframe__comment-time">${time}</time>
                    </header>

                    ${targetHtml}
                    <p class="tip-wireframe__comment-body">${body}</p>

                    <div class="tip-wireframe__comment-bottom">
                        <div class="tip-wireframe__comment-meta">${renderLikeButton(reply)}${renderReplyButton(reply)}</div>
                        <div class="tip-wireframe__comment-actions">${renderEditButton(reply)}${renderDeleteButton(reply)}</div>
                    </div>
                </article>
            </li>
        `.trim();
    }

    function renderRootItem(comment) {
        const author = escapeHtml(comment.user_name || '익명');
        const avatar = escapeHtml(comment.user_profile_image_url || '/images/avatar-default.svg');
        const body = nl2br(comment.body || '');
        const time = escapeHtml(formatCommentTime(comment.created_at));

        const children = Array.isArray(comment.children) ? comment.children : [];

        const repliesHtml = children.length > 0
            ? `<ul class="tip-wireframe__reply-list" aria-label="대댓글 목록">${children.map((reply) => renderReplyItem(reply)).join('')}</ul>`
            : '';

        return `
            <li class="tip-wireframe__comment-item" data-comment-id="${comment.id}">
                <article class="tip-wireframe__comment-card ${comment.is_deleted ? 'is-deleted' : ''}">
                    <header class="tip-wireframe__comment-head">
                        <div class="tip-wireframe__comment-user">
                            <span class="tip-wireframe__comment-avatar">
                                <img src="${avatar}" alt="${author} 프로필" loading="lazy">
                            </span>
                            <strong class="tip-wireframe__comment-author">${author}</strong>
                        </div>
                        <time class="tip-wireframe__comment-time">${time}</time>
                    </header>

                    <p class="tip-wireframe__comment-body">${body}</p>

                    <div class="tip-wireframe__comment-bottom">
                        <div class="tip-wireframe__comment-meta">${renderLikeButton(comment)}${renderReplyButton(comment)}</div>
                        <div class="tip-wireframe__comment-actions">${renderEditButton(comment)}${renderDeleteButton(comment)}</div>
                    </div>
                </article>

                ${repliesHtml}
            </li>
        `.trim();
    }

    function indexComments(comments) {
        commentMap.clear();

        comments.forEach((root) => {
            const rootId = parseNullableInt(root?.id);
            if (rootId !== null) {
                commentMap.set(rootId, root);
            }

            const children = Array.isArray(root?.children) ? root.children : [];
            children.forEach((child) => {
                const childId = parseNullableInt(child?.id);
                if (childId !== null) {
                    commentMap.set(childId, child);
                }
            });
        });
    }

    function loadComments() {
        if (!$list.length) {
            return;
        }

        getCommentList()
            .done((response) => {
                const comments = Array.isArray(response?.comments) ? response.comments : [];
                indexComments(comments);
                $list.html(comments.map(renderRootItem).join(''));
            })
            .fail((xhr) => {
                if (handleUnauthorized(xhr)) {
                    return;
                }

                const message = xhr?.responseJSON?.message ?? '댓글 목록 조회 실패';
                alert(message);
            });
    }

    // 최초 목록 조회
    loadComments();

    // 답글 모드 진입
    $(document).on('click', '#tip-comments [data-comment-action="reply"]', function () {
        const $btn = $(this);
        const parentId = parseNullableInt($btn.data('commentParentId'));
        const replyToId = parseNullableInt($btn.data('commentReplyToId'));
        const parentAuthor = String($btn.data('commentAuthor') ?? '작성자');
        const parentPreview = String($btn.data('commentPreview') ?? '');

        if (!parentId || !replyToId) {
            return;
        }

        setReplyMode(parentId, replyToId, parentAuthor, parentPreview);
    });

    // 댓글 좋아요
    $(document).on('click', '#tip-comments [data-comment-action="like"]', function (event) {
        event.preventDefault();

        const $btn = $(this);
        const commentId = parseNullableInt($btn.data('commentId'));
        if (!commentId) {
            return;
        }

        if (pendingLikeByCommentId[commentId]) {
            return;
        }

        pendingLikeByCommentId[commentId] = true;

        const $buttons = getCommentLikeButtons(commentId);
        $buttons.prop('disabled', true);

        likeComment(commentId)
            .done((response) => {
                const liked = Boolean(response?.liked);
                const likeCount = Number(response?.like_count ?? 0);

                $buttons
                    .attr('aria-pressed', liked ? 'true' : 'false')
                    .toggleClass('is-liked', liked);

                if (Number.isFinite(likeCount)) {
                    $buttons.find('[data-comment-like-count]').text(likeCount.toLocaleString());
                }

                const cached = commentMap.get(commentId);
                if (cached) {
                    cached.is_liked = liked;
                    if (Number.isFinite(likeCount)) {
                        cached.like_count = likeCount;
                    }
                }
            })
            .fail((xhr) => {
                if (handleUnauthorized(xhr)) {
                    return;
                }

                const message = xhr?.responseJSON?.message ?? '댓글 좋아요 처리 실패';
                alert(message);
            })
            .always(() => {
                pendingLikeByCommentId[commentId] = false;
                $buttons.prop('disabled', false);
            });
    });

    // 수정 모드 진입
    $(document).on('click', '#tip-comments [data-comment-action="edit"]', function () {
        const $btn = $(this);
        const commentId = parseNullableInt($btn.data('commentId'));
        if (!commentId) {
            return;
        }

        const comment = commentMap.get(commentId);
        if (!comment || !comment.can_edit) {
            return;
        }

        setEditMode(commentId, comment.body);
    });

    // 댓글 등록
    $(document).on('click', '#tip-comments [data-tip-action="comment_add"]', function (event) {
        event.preventDefault();

        const body = String($textarea.val() ?? '').trim();
        if (!body) {
            alert('댓글을 입력해주세요.');
            return;
        }

        const editCommentId = parseNullableInt($submitBtn.data('editCommentId'));
        const parentId = parseNullableInt($submitBtn.data('parentId'));
        const replyToId = parseNullableInt($submitBtn.data('replyToId'));

        $submitBtn.prop('disabled', true);

        const request = editCommentId
            ? editComment(editCommentId, body)
            : addComment(body, parentId, replyToId);

        request
            .done(() => {
                resetComposer();
                loadComments();
            })
            .fail((xhr) => {
                if (handleUnauthorized(xhr)) {
                    return;
                }

                const defaultErrorMessage = editCommentId ? '댓글 수정 실패' : '댓글 등록 실패';
                const message = xhr?.responseJSON?.message ?? defaultErrorMessage;
                alert(message);
                $submitBtn.prop('disabled', false);
            });
    });

    // 입력 취소
    $(document).on('click', '#tip-comments [data-tip-action="comment_cancel"]', function (event) {
        event.preventDefault();
        resetComposer();
    });

    // 댓글 삭제 (status 변경)
    $(document).on('click', '#tip-comments [data-comment-action="delete"]', function (event) {
        event.preventDefault();

        const $btn = $(this);
        const commentId = Number($btn.data('commentId') ?? 0);

        if (!commentId) {
            return;
        }

        if (!window.confirm('댓글을 삭제하시겠습니까?')) {
            return;
        }

        $btn.prop('disabled', true);

        deleteComment(commentId)
            .done(() => {
                resetComposer();
                loadComments();
            })
            .fail((xhr) => {
                if (handleUnauthorized(xhr)) {
                    return;
                }

                const message = xhr?.responseJSON?.message ?? '댓글 삭제 실패';
                alert(message);
                $btn.prop('disabled', false);
            });
    });
});
