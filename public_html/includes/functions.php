<?php

function slugify($text) {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function upload_product_image($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return ['error' => 'Format non autorisé. Utilisez JPG, PNG ou WebP.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['error' => 'Type de fichier non valide.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Image trop lourde (max 5 Mo).'];
    }

    $filename = uniqid('prod_', true) . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    list($w, $h) = getimagesize($file['tmp_name']);
    $max_w = 800;

    if ($w > $max_w) {
        $ratio = $max_w / $w;
        $new_w = $max_w;
        $new_h = (int)($h * $ratio);

        switch ($mime) {
            case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
            case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); break;
            case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
        }

        $dst = imagecreatetruecolor($new_w, $new_h);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $w, $h);

        switch ($mime) {
            case 'image/jpeg': imagejpeg($dst, $dest, 85); break;
            case 'image/png':  imagepng($dst, $dest, 8); break;
            case 'image/webp': imagewebp($dst, $dest, 85); break;
        }

        imagedestroy($src);
        imagedestroy($dst);
    } else {
        move_uploaded_file($file['tmp_name'], $dest);
    }

    return ['filename' => $filename];
}

function delete_product_image($filename) {
    if ($filename && file_exists(UPLOAD_DIR . $filename)) {
        unlink(UPLOAD_DIR . $filename);
    }
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function format_price($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}
