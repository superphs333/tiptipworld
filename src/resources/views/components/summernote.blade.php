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
                onDialogHidden: function () {
                    window.jQuery('.note-modal-backdrop').each(function () {
                        const $backdrop = window.jQuery(this);
                        if ($backdrop.css('display') === 'none') {
                            $backdrop.remove();
                        }
                    });
                },
            },
        });
    })();
</script>
