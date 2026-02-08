<textarea id="{{ $id }}" name="{{ $name }}" {{ $attributes }}>{{ $fieldValue() }}</textarea>

@once
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-ko-KR.min.js"></script>
    <style>
        .note-modal-backdrop {
            z-index: 2000;
        }

        .note-modal {
            z-index: 2010;
        }
    </style>
@endonce

<script>
    (() => {
        const $editor = window.jQuery ? window.jQuery('#{{ $id }}') : null;

        if (!$editor || !$editor.length || typeof $editor.summernote !== 'function') {
            return;
        }

        // Prevent duplicate init when the same element is rendered again.
        if ($editor.next('.note-editor').length) {
            return;
        }

        $editor.summernote({
            placeholder: '{{ $placeholder }}',
            tabsize: 2,
            height: {{ $height }},
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
                onDialogHidden: function() {
                    window.jQuery('.note-modal-backdrop').each(function() {
                        const $backdrop = window.jQuery(this);
                        if ($backdrop.css('display') === 'none') {
                            $backdrop.remove();
                        }
                    });
                },
                onImageUpload: function(files) {
                    const fileArr = Array.from(files);
                    uploadImagesSequentially(fileArr, this);
                }
            },
        });
    })();


    /**
     * 여러 장을 순차로 업로드 (실패는 건너뛰고 계속 진행)
     * @param {File} file
     * @param {HTMLElement} editorEl - summernote가 붙은 element (this)
     */
    async function uploadImagesSequentially(files, editorEl) {
        const failed = []; // 실패 목록

        for (const file of files) {
            try {
                const res = awai uploadSingleImage(file);
                $(editorEl).summernote('insertImage', res.url, function($img) {
                    $img.attr('alt', res.alt ?? '');
                })
            } catch (err) {
                console.warn(err);

                failed.push({
                    name: file?.name ?? 'unknown',
                    size: file?.size ?? 0,
                    type: file?.type ?? '',
                    message: err?.message ?? '업로드 실패',
                });
            }
        }

        // 한번에 알림
        if (failed.length > 0) {
            const lines = failed.map((f, idx) => {
                const sizeKb = f.size ? Math.round(f.size / 1024) : 0;
                return `${idx + 1}. ${f.name} (${sizeKb}KB) - ${f.message}`;
            });

            alert(
                `일부 이미지 업로드에 실패했어요.\n\n` +
                lines.join('\n')
            );
        }
    }


    /** 단일 파일 업로드 (Promise)
     * @param {File} file
     * 
     */
    function uploadSingleImage(file) {
        return new Promise((resolve, reject) => {

            // 이미지의 확장자/타입 검사
            if (!file.type || !file.type.startsWith("image/")) {
                reject(new Error('이미지 파일만 업로드할 수 있어요.'));
                return;
            }

            const formData = new FormData();
            formData.append('image', file);

            $.ajax({
                url: '/summernote/uploades/image',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    if (!res || !res.url) {
                        reject(new Error('서버 응답에 url이 없어요.'));
                        return;
                    }
                    resolve(res);
                },
                error: function(xhr) {
                    const msg =
                        xhr?.responseJSON?.message ??
                        (xhr?.status ? `업로드 실패 (HTTP ${xhr.status})` : '업로드 실패');
                    reject(new Error(msg));
                }
            });
        });
    }
</script>
