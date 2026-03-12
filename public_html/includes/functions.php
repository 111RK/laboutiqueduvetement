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

function get_packlink_key() {
    $db = getDB();
    $row = $db->query("SELECT value FROM settings WHERE key = 'packlink_api'")->fetch();
    return $row ? $row['value'] : '';
}

function get_packlink_base() {
    $db = getDB();
    $row = $db->query("SELECT value FROM settings WHERE key = 'packlink_mode'")->fetch();
    $mode = $row ? $row['value'] : 'live';
    return $mode === 'test'
        ? 'https://apisandbox.packlink.com'
        : 'https://api.packlink.com';
}

function packlink_request($method, $endpoint, $data = null) {
    $key = get_packlink_key();
    if (!$key) return ['error' => 'Clé Packlink non configurée'];

    $url = get_packlink_base() . $endpoint;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $key,
            'Content-Type: application/json',
        ],
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $http_code, 'body' => json_decode($response, true)];
}

function get_payplug_keys() {
    $db = getDB();
    $rows = $db->query("SELECT key, value FROM settings WHERE key IN ('payplug_mode','payplug_test_secret','payplug_live_secret')")->fetchAll();
    $s = [];
    foreach ($rows as $r) $s[$r['key']] = $r['value'];
    $mode = $s['payplug_mode'] ?? 'test';
    return [
        'secret' => $s["payplug_{$mode}_secret"] ?? '',
        'mode' => $mode,
    ];
}
