<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function respond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond([
        'success' => false,
        'type' => 'danger',
        'message' => 'فقط درخواست POST مجاز است.',
    ], 405);
}

function sanitize_text(string $value): string
{
    $clean = trim($value);
    $clean = strip_tags($clean);

    return preg_replace('/\s+/u', ' ', $clean) ?? '';
}

function too_long(string $value, int $max): bool
{
    return mb_strlen($value, 'UTF-8') > $max;
}

$name = sanitize_text($_POST['name'] ?? '');
$email = sanitize_text($_POST['email'] ?? '');
$subject = sanitize_text($_POST['subject'] ?? '');
$budget = sanitize_text($_POST['budget'] ?? '');
$message = sanitize_text($_POST['message'] ?? '');

$errors = [];

if ($name === '') {
    $errors[] = 'نام را وارد کنید.';
} elseif (too_long($name, 120)) {
    $errors[] = 'نام بیش از حد طولانی است.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'ایمیل معتبر وارد کنید.';
} elseif (too_long($email, 190)) {
    $errors[] = 'ایمیل بیش از حد طولانی است.';
}

if ($subject === '') {
    $errors[] = 'عنوان یا شغل را وارد کنید.';
} elseif (too_long($subject, 150)) {
    $errors[] = 'عنوان بیش از حد طولانی است.';
}

if ($budget !== '' && too_long($budget, 80)) {
    $errors[] = 'مقدار بودجه بیش از حد طولانی است.';
}

if ($message === '') {
    $errors[] = 'متن پیام نمی‌تواند خالی باشد.';
} elseif (too_long($message, 3000)) {
    $errors[] = 'متن پیام بیش از حد طولانی است.';
}

if (!empty($errors)) {
    respond([
        'success' => false,
        'type' => 'danger',
        'message' => implode(' ', $errors),
    ], 422);
}

try {
    $id = bin2hex(random_bytes(8));
} catch (Throwable $exception) {
    respond([
        'success' => false,
        'type' => 'danger',
        'message' => 'خطای داخلی در پردازش درخواست.',
    ], 500);
}

$submission = [
    'id' => $id,
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'budget' => $budget,
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'created_at' => gmdate('c'),
];

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/contact-submissions.jsonl';

if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
    respond([
        'success' => false,
        'type' => 'danger',
        'message' => 'خطا در ایجاد پوشه داده.',
    ], 500);
}

$line = json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($line === false || file_put_contents($dataFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
    respond([
        'success' => false,
        'type' => 'danger',
        'message' => 'خطا در ذخیره درخواست.',
    ], 500);
}

respond([
    'success' => true,
    'type' => 'success',
    'message' => 'درخواست شما با موفقیت ثبت شد. خیلی زود با شما تماس می‌گیرم.',
]);
