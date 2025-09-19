@props([
    'editorId' => 'editor',
    'height' => '100px',
    'maxHeight' => '500px',
    'type' => 'full', // Options: 'full', 'basic', 'minimal'
    'customToolbar' => null, // For custom toolbar configuration
])

@once
<link rel="stylesheet" href="{{ asset('vendor/quill/quill.min.css') }}" />
<style>
    .ql-editor {
        /* Heights are applied via JS to avoid Blade syntax inside CSS */
        overflow-y: auto;
    }
    .ql-toolbar.ql-snow {
        border-radius: 10px 10px 0px 0px;
        margin-bottom: 0px;
    }
    /* Create a container for Quill to target */
    .quill-container {
        border: 1px solid #ccc;
        border-radius: 0 0 10px 10px;
        background: transparent;
    }
    .dark .quill-container {
        border-color: #4b5563;
        color: #e5e7eb;
    }
    .dark .ql-snow {
        border-color: #4b5563;
    }
    .dark .ql-toolbar.ql-snow .ql-picker-label,
    .dark .ql-toolbar.ql-snow .ql-picker-options,
    .dark .ql-toolbar.ql-snow button,
    .dark .ql-toolbar.ql-snow span {
        color: #e5e7eb;
    }
    .dark .ql-snow .ql-stroke {
        stroke: #e5e7eb;
    }
    .dark .ql-snow .ql-fill {
        fill: #e5e7eb;
    }
    .dark .ql-editor.ql-blank::before {
        color: rgba(255, 255, 255, 0.6);
    }

    /* Alternative using iconify icon */
    .ql-toolbar .ql-media-modal {
        width: 28px;
        height: 28px;
    }
    
    .ql-toolbar .ql-media-modal:after {
        content: '';
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>');
        background-size: 18px;
        background-repeat: no-repeat;
        background-position: center;
        width: 100%;
        height: 100%;
        display: block;
    }
</style>

<script src="{{ asset('vendor/quill/quill.min.js') }}"></script>
@endonce

<!-- Include the media modal component for Quill editor -->
<x-media-modal 
    :id="'quillMediaModal_' . $editorId" 
    title="Select Media for Editor"
    :multiple="false"
    allowedTypes="all"
    buttonText="Select Media"
    buttonClass="hidden"
/>
