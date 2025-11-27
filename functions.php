<?php
function asset($path) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
    if (file_exists($fullPath)) {
        $version = (isset($_SERVER['DEVELOPMENT_MODE']) ? time() : filemtime($fullPath));
        return $path . '?v=' . $version;
    }
    return $path;
}