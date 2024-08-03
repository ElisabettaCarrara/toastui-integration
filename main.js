jQuery(document).ready(function($) {
    // Register FilePond plugins
    FilePond.registerPlugin(FilePondPluginImagePreview);

    // Initialize ToastUI Editor
    const editor = new toastui.Editor({
        el: document.querySelector('#toastui-editor'),
        height: '400px',
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical'
    });

    // Load existing content if any
    const existingContent = $('#content').val();
    if (existingContent) {
        editor.setMarkdown(existingContent);
    }

    // Initialize FilePond
    const pond = FilePond.create(document.querySelector('#filepond-container'), {
        server: {
            process: {
                url: toastuiData.ajaxurl,
                method: 'POST',
                withCredentials: false,
                headers: {},
                timeout: 7000,
                onload: (response) => {
                    return JSON.parse(response).data;
                },
                onerror: (response) => {
                    return response.data;
                },
                ondata: (formData) => {
                    formData.append('action', 'handle_file_upload');
                    formData.append('nonce', toastuiData.nonce);
                    return formData;
                }
            }
        },
        allowMultiple: true,
        maxFiles: 5
    });

    // Handle media button click
    $('#toastui-media-button').click(function() {
        $('#filepond-container').toggle();
    });

    // Handle successful file upload
    pond.on('processfile', (error, file) => {
        if (!error) {
            const fileUrl = file.serverId;
            editor.insertText(`![${file.filename}](${fileUrl})`);
        }
    });

    // Update hidden input when form is submitted
    $('form#post').submit(function() {
        $('#toastui-content').val(editor.getMarkdown());
        $('#content').val(editor.getMarkdown());
    });
});