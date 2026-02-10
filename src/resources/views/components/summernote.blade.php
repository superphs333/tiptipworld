<textarea
    id="{{ $id }}"
    name="{{ $name }}"
    data-summernote
    data-summernote-placeholder="{{ $placeholder }}"
    data-summernote-height="{{ $height }}"
    data-summernote-upload-url="{{ route('summernote.uploadImage', absolute: false) }}"
    {{ $attributes }}
>{{ $fieldValue() }}</textarea>

@once
    @vite('resources/js/components/summernote.js')

    <style>
        .note-modal-backdrop {
            z-index: 2000;
        }

        .note-modal {
            z-index: 2010;
        }
    </style>
@endonce
