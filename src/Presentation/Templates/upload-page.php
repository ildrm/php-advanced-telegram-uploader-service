<?php
/**
 * @var array{maxFileSize:int,maxFileSizeHuman:string,allowedExtensions:list<string>,appMode:string} $viewData
 */
declare(strict_types=1);

$acceptAttribute = $viewData['allowedExtensions'] !== []
    ? implode(',', array_map(static fn (string $ext): string => '.' . $ext, $viewData['allowedExtensions']))
    : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Telegram Storage Gateway</title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="page">
    <header class="page__header">
        <h1 class="page__title">Telegram Storage Gateway</h1>
        <p class="page__subtitle">Upload files directly to Telegram. Nothing is stored on this server.</p>
        <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode">
            <span aria-hidden="true">&#9788;</span>
        </button>
    </header>

    <main>
        <section
            id="dropzone"
            class="dropzone"
            data-max-file-size="<?= (int) $viewData['maxFileSize'] ?>"
            tabindex="0"
            role="button"
            aria-label="Upload files"
        >
            <p class="dropzone__text">Drag &amp; drop files here, or click to choose</p>
            <p class="dropzone__hint">Maximum size per file: <?= htmlspecialchars($viewData['maxFileSizeHuman'], ENT_QUOTES) ?></p>
            <input
                type="file"
                id="file-input"
                class="visually-hidden"
                multiple
                <?= $acceptAttribute !== '' ? 'accept="' . htmlspecialchars($acceptAttribute, ENT_QUOTES) . '"' : '' ?>
            >
        </section>

        <section id="results" class="results" aria-live="polite"></section>
    </main>

    <footer class="page__footer">
        <p>Telegram Storage Gateway &mdash; a stateless upload gateway, not a storage provider.</p>
    </footer>
</div>

<template id="result-card-template">
    <article class="result-card">
        <header class="result-card__header">
            <span class="result-card__filename"></span>
            <span class="result-card__status"></span>
        </header>
        <div class="result-card__progress">
            <div class="result-card__progress-bar"></div>
        </div>
        <dl class="result-card__meta"></dl>
        <div class="result-card__error"></div>
        <div class="result-card__actions"></div>
        <pre class="result-card__json"></pre>
    </article>
</template>

<script src="assets/js/app.js"></script>
</body>
</html>
