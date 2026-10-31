<?php
/* Creates a community post (text and/or photo). Multipart endpoint – validates and re-encodes uploaded images. */
require_once __DIR__ . '/config.php';
function bad(string $m, int $c = 422): void { json_out(['ok' => false, 'error' => $m], $c); }
if (empty($_SESSION['uid'])) bad('Session expired. Please sign in again.', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) bad('Invalid request token. Refresh the page.', 403);
$u = current_user(); if (!$u) bad('Session expired.', 401);

$text = trim((string)($_POST['content'] ?? ''));
$type = in_array($_POST['post_type'] ?? '', ['Workout', 'MentalHealth', 'Nutrition', 'Yoga', 'Progress'], true) ? $_POST['post_type'] : 'Workout';
if (mb_strlen($text) > 500) bad('Captions can be up to 500 characters.');

$img = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['image'];
    if ($f['error'] !== UPLOAD_ERR_OK) bad('The photo could not be uploaded. Try a smaller image.');
    if ($f['size'] > 4 * 1024 * 1024) bad('Photos must be under 4 MB.');
    $info = @getimagesize($f['tmp_name']);
    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!$info || !in_array($info[2], $allowed, true)) bad('Please upload a JPG, PNG or WebP photo.');
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) bad('Upload folder is not writable.', 500);
    $name = bin2hex(random_bytes(10));
    if (function_exists('imagecreatetruecolor')) {
        $src = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($f['tmp_name']) : ($info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($f['tmp_name']) : (function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false));
        if (!$src) bad('That image could not be processed.');
        $scale = min(1, 1080 / max($info[0], $info[1]));
        $nw = max(1, (int)round($info[0] * $scale)); $nh = max(1, (int)round($info[1] * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $info[0], $info[1]);
        if (!imagejpeg($dst, "$dir/$name.jpg", 85)) bad('Could not save the photo.', 500);
        imagedestroy($src); imagedestroy($dst);
        $img = "uploads/$name.jpg";
    } else {
        $ext = $info[2] === IMAGETYPE_JPEG ? 'jpg' : ($info[2] === IMAGETYPE_PNG ? 'png' : 'webp');
        if (!move_uploaded_file($f['tmp_name'], "$dir/$name.$ext")) bad('Could not save the photo.', 500);
        $img = "uploads/$name.$ext";
    }
}
if (mb_strlen($text) < 3 && !$img) bad('Write at least a few words or add a photo.');
if ($text === '') $text = '📸';

$pdo = db();
$pdo->prepare('INSERT INTO posts (user_id, content, post_type, image_path, created_at) VALUES (?,?,?,?,?)')->execute([$u['id'], $text, $type, $img, date('Y-m-d H:i:s')]);
json_out(['ok' => true, 'post' => ['id' => (int)$pdo->lastInsertId(), 'user_id' => (int)$u['id'], 'name' => $u['name'], 'content' => $text, 'post_type' => $type, 'image' => $img,
    'likes_count' => 0, 'liked' => 0, 'saved' => 0, 'comments_count' => 0, 'ago' => 'Just now', 'mine' => true]]);
