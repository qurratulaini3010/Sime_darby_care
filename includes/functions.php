<?php
declare(strict_types=1);
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . BASE_URL . '/' . ltrim($path, '/')); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid or expired form token. Refresh the page and try again.');
    }
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_role(array $roles): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('You do not have permission to access this page.'); }
    return $u;
}
function category_rows(PDO $pdo): array { return $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll(); }
function category_class(string $slug): string { return 'cat-' . preg_replace('/[^a-z-]/', '', $slug); }
function status_class(int $status): string { return 'status-' . max(0, min(4, $status)); }
function status_names(): array { return ['Request Received','Assigned to Responder','On the Way','In Progress','Resolved']; }
function status_timestamp(?string $dt): string { return $dt ? date('d M Y, g:i A', strtotime($dt)) : 'Pending'; }
function add_notification(PDO $pdo, int $userId, int $requestId, string $title, string $body): void {
    $st = $pdo->prepare('INSERT INTO notifications (user_id, request_id, title, body) VALUES (?,?,?,?)');
    $st->execute([$userId, $requestId, $title, $body]);
}
function log_status(PDO $pdo, int $requestId, int $status, ?int $changedBy, ?string $note = null): void {
    $st = $pdo->prepare('INSERT INTO request_status_history (request_id,status,changed_by,note) VALUES (?,?,?,?)');
    $st->execute([$requestId, $status, $changedBy, $note]);
    $pdo->prepare('UPDATE requests SET status=?, updated_at=NOW() WHERE id=?')->execute([$status, $requestId]);
}
function request_by_id(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare('SELECT r.*, c.name category_name,c.slug category_slug, u.full_name owner_name,u.phone owner_phone,u.email owner_email,p.address premise_address,p.township,p.property_type,sp.user_id provider_user_id,pu.full_name provider_name,sp.service_type provider_type FROM requests r JOIN categories c ON c.id=r.category_id JOIN users u ON u.id=r.owner_id LEFT JOIN premises p ON p.id=r.premise_id LEFT JOIN service_providers sp ON sp.id=r.provider_id LEFT JOIN users pu ON pu.id=sp.user_id WHERE r.id=?');
    $st->execute([$id]); $r=$st->fetch(); return $r ?: null;
}
function status_history(PDO $pdo, int $requestId): array {
    $st=$pdo->prepare('SELECT h.*,u.full_name changed_by_name FROM request_status_history h LEFT JOIN users u ON u.id=h.changed_by WHERE h.request_id=? ORDER BY h.created_at ASC,h.id ASC');
    $st->execute([$requestId]); return $st->fetchAll();
}
function upload_attachment(array $file): ?array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Upload failed or file exceeds 5 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Only valid JPG, PNG or WEBP images are allowed.');
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) throw new RuntimeException('Upload folder is not available.');
    $name = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Could not save uploaded photo.');
    return ['name'=>$name,'mime'=>$mime,'size'=>(int)$file['size']];
}
function upload_attachment_data(string $dataUrl): ?array {
    // Photo arrives as a base64 data URL inside a normal urlencoded POST field.
    // (Hosting currently blocks multipart/form-data requests, so $_FILES is not used.)
    $dataUrl = trim($dataUrl);
    if ($dataUrl === '') return null;
    if (!preg_match('~^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=\s]+)$~', $dataUrl, $m)) throw new RuntimeException('Only valid JPG, PNG or WEBP images are allowed.');
    $bin = base64_decode(preg_replace('~\s+~', '', $m[2]), true);
    if ($bin === false || $bin === '') throw new RuntimeException('Upload failed. Please choose the photo again.');
    if (strlen($bin) > MAX_UPLOAD_BYTES) throw new RuntimeException('Upload failed or file exceeds 5 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->buffer($bin);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($allowed[$mime]) || @getimagesizefromstring($bin) === false) throw new RuntimeException('Only valid JPG, PNG or WEBP images are allowed.');
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) throw new RuntimeException('Upload folder is not available.');
    $name = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
    if (file_put_contents(UPLOAD_DIR . '/' . $name, $bin) === false) throw new RuntimeException('Could not save uploaded photo.');
    return ['name'=>$name,'mime'=>$mime,'size'=>strlen($bin)];
}
function category_emoji(string $slug): string {
    return ['fire-safety'=>'🔥','plumbing'=>'💧','electrical'=>'⚡','security'=>'🛡️','maintenance'=>'🔧','other'=>'💬'][$slug] ?? '📋';
}
