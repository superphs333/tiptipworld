import $ from 'jquery';
import 'summernote/dist/summernote-lite.css';
import 'summernote/dist/summernote-lite';
import 'summernote/dist/lang/summernote-ko-KR';

window.$ = window.$ || $;
window.jQuery = window.jQuery || $;

function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : null;
}

function removeHiddenBackdrops() {
    $('.note-modal-backdrop').each(function () {
        const $backdrop = $(this);
        if ($backdrop.css('display') === 'none') {
            $backdrop.remove();
        }
    });
}

function uploadSingleImage(file, uploadUrl) {
    return new Promise((resolve, reject) => {
        if (!file.type || !file.type.startsWith('image/')) {
            reject(new Error('이미지 파일만 업로드할 수 있어요.'));
            return;
        }

        const maxImageSizeBytes = 10 * 1024 * 1024;
        if ((file.size ?? 0) > maxImageSizeBytes) {
            reject(new Error('이미지 최대 용량은 10MB입니다.'));
            return;
        }

        const formData = new FormData();
        formData.append('image', file);

        const token = getCsrfToken();
        const headers = token ? { 'X-CSRF-TOKEN': token } : {};

        $.ajax({
            url: uploadUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers,
            success: (response) => {
                if (!response || !response.url) {
                    reject(new Error('서버 응답에 url이 없어요.'));
                    return;
                }

                resolve(response);
            },
            error: (xhr) => {
                const message =
                    xhr?.responseJSON?.message ??
                    (xhr?.status ? `업로드 실패 (HTTP ${xhr.status})` : '업로드 실패');
                reject(new Error(message));
            },
        });
    });
}

async function uploadImagesSequentially(files, editorElement, uploadUrl) {
    const failed = [];

    for (const file of files) {
        try {
            const response = await uploadSingleImage(file, uploadUrl);
            $(editorElement).summernote('insertImage', response.url, ($image) => {
                $image.attr('alt', response.alt ?? '');
            });
        } catch (error) {
            failed.push({
                name: file?.name ?? 'unknown',
                size: file?.size ?? 0,
                message: error?.message ?? '업로드 실패',
            });
        }
    }

    if (failed.length === 0) {
        return;
    }

    const lines = failed.map((item, index) => {
        const sizeKb = item.size ? Math.round(item.size / 1024) : 0;
        return `${index + 1}. ${item.name} (${sizeKb}KB) - ${item.message}`;
    });

    alert(`일부 이미지 업로드에 실패했어요.\n\n${lines.join('\n')}`);
}

function mountSummernote(element) {
    if (element.dataset.summernoteReady === '1') {
        return;
    }

    if (typeof $.fn?.summernote !== 'function') {
        return;
    }

    const $editor = $(element);
    if (!$editor.length) {
        return;
    }

    if ($editor.next('.note-editor').length) {
        element.dataset.summernoteReady = '1';
        return;
    }

    const placeholder = element.dataset.summernotePlaceholder || '내용을 입력하세요.';
    const height = Number.parseInt(element.dataset.summernoteHeight || '500', 10);
    const uploadUrl = element.dataset.summernoteUploadUrl || '';

    $editor.summernote({
        placeholder,
        tabsize: 2,
        height: Number.isFinite(height) ? height : 500,
        lang: 'ko-KR',
        dialogsInBody: true,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
        ],
        callbacks: {
            onDialogHidden: () => {
                removeHiddenBackdrops();
            },
            onImageUpload: function (files) {
                if (!uploadUrl) {
                    return;
                }

                uploadImagesSequentially(Array.from(files), this, uploadUrl);
            },
        },
    });

    element.dataset.summernoteReady = '1';
}

export function mountSummernotes(container = document) {
    container.querySelectorAll('textarea[data-summernote]').forEach((element) => {
        mountSummernote(element);
    });
}

export function unmountSummernotes(container = document) {
    if (typeof $.fn?.summernote !== 'function') {
        return;
    }

    container.querySelectorAll('textarea[data-summernote]').forEach((element) => {
        const $editor = $(element);
        if ($editor.next('.note-editor').length) {
            $editor.summernote('destroy');
        }
        element.dataset.summernoteReady = '0';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => mountSummernotes());
} else {
    mountSummernotes();
}

window.mountSummernotes = mountSummernotes;
window.unmountSummernotes = unmountSummernotes;
