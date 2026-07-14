'use strict';

(function () {
    const THEME_STORAGE_KEY = 'tsg-theme';

    function initTheme() {
        const root = document.documentElement;
        const toggleButton = document.getElementById('theme-toggle');
        const stored = window.localStorage.getItem(THEME_STORAGE_KEY);

        if (stored === 'light' || stored === 'dark') {
            root.setAttribute('data-theme', stored);
        }

        toggleButton.addEventListener('click', function () {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const current = root.getAttribute('data-theme') || (prefersDark ? 'dark' : 'light');
            const next = current === 'dark' ? 'light' : 'dark';

            root.setAttribute('data-theme', next);
            window.localStorage.setItem(THEME_STORAGE_KEY, next);
        });
    }

    function formatBytes(bytes) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = Math.max(0, bytes);
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex += 1;
        }

        return (unitIndex === 0 ? size.toFixed(0) : size.toFixed(2)) + ' ' + units[unitIndex];
    }

    function createResultCard(template, resultsContainer, filename) {
        const fragment = template.content.cloneNode(true);
        const card = fragment.querySelector('.result-card');

        card.querySelector('.result-card__filename').textContent = filename;
        setStatus(card, 'uploading', 'Uploading…');

        resultsContainer.prepend(card);

        return card;
    }

    function setStatus(card, kind, label) {
        const status = card.querySelector('.result-card__status');
        status.textContent = label;
        status.classList.remove('is-uploading', 'is-success', 'is-error');
        status.classList.add('is-' + kind);
    }

    function setProgress(card, percent) {
        card.querySelector('.result-card__progress-bar').style.width = percent + '%';
    }

    function showError(card, message) {
        setStatus(card, 'error', 'Failed');
        setProgress(card, 100);
        card.querySelector('.result-card__error').textContent = message;
    }

    function addMetaRow(list, term, value) {
        const dt = document.createElement('dt');
        dt.textContent = term;
        const dd = document.createElement('dd');
        dd.textContent = value;
        list.append(dt, dd);
    }

    function addCopyButton(container, label, value) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;

        button.addEventListener('click', function () {
            navigator.clipboard.writeText(value).then(function () {
                const original = button.textContent;
                button.textContent = 'Copied!';
                window.setTimeout(function () {
                    button.textContent = original;
                }, 1500);
            });
        });

        container.append(button);
    }

    function showSuccess(card, data) {
        setStatus(card, 'success', 'Uploaded');
        setProgress(card, 100);

        const meta = card.querySelector('.result-card__meta');
        addMetaRow(meta, 'Upload method', data.upload_method);
        addMetaRow(meta, 'File size', formatBytes(data.file_size));
        addMetaRow(meta, 'MIME type', data.original_mime_type);
        addMetaRow(meta, 'File ID', data.file_id);
        addMetaRow(meta, 'Unique file ID', data.file_unique_id);
        addMetaRow(meta, 'Message ID', String(data.message_id));
        addMetaRow(meta, 'Chat ID', data.chat_id);

        const actions = card.querySelector('.result-card__actions');
        addCopyButton(actions, 'Copy file ID', data.file_id);

        const json = card.querySelector('.result-card__json');
        json.textContent = JSON.stringify(data, null, 2);
        addCopyButton(actions, 'Copy JSON', json.textContent);

        const toggleJsonButton = document.createElement('button');
        toggleJsonButton.type = 'button';
        toggleJsonButton.textContent = 'View JSON';
        toggleJsonButton.addEventListener('click', function () {
            json.classList.toggle('is-visible');
            toggleJsonButton.textContent = json.classList.contains('is-visible') ? 'Hide JSON' : 'View JSON';
        });
        actions.append(toggleJsonButton);
    }

    function uploadFile(file, card) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/index.php', true);

        xhr.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable) {
                setProgress(card, Math.round((event.loaded / event.total) * 100));
            }
        });

        xhr.addEventListener('load', function () {
            let payload;
            try {
                payload = JSON.parse(xhr.responseText);
            } catch (error) {
                showError(card, 'The server returned an unexpected response.');
                return;
            }

            if (payload.success) {
                showSuccess(card, payload.data);
            } else {
                showError(card, (payload.error && payload.error.message) || 'Upload failed.');
            }
        });

        xhr.addEventListener('error', function () {
            showError(card, 'A network error occurred while uploading.');
        });

        const formData = new FormData();
        formData.append('file', file);
        xhr.send(formData);
    }

    function handleFiles(files, dropzone, resultsContainer, template) {
        const maxFileSize = parseInt(dropzone.dataset.maxFileSize, 10) || 0;

        Array.from(files).forEach(function (file) {
            const card = createResultCard(template, resultsContainer, file.name);

            if (maxFileSize > 0 && file.size > maxFileSize) {
                showError(card, 'This file exceeds the maximum allowed size of ' + formatBytes(maxFileSize) + '.');
                return;
            }

            uploadFile(file, card);
        });
    }

    function initUploader() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-input');
        const resultsContainer = document.getElementById('results');
        const template = document.getElementById('result-card-template');

        dropzone.addEventListener('click', function () {
            fileInput.click();
        });

        dropzone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', function (event) {
            if (event.dataTransfer && event.dataTransfer.files.length > 0) {
                handleFiles(event.dataTransfer.files, dropzone, resultsContainer, template);
            }
        });

        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                handleFiles(fileInput.files, dropzone, resultsContainer, template);
                fileInput.value = '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTheme();
        initUploader();
    });
})();
