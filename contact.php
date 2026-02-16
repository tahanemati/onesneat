<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'فقط درخواست POST مجاز است.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize_text(string $value): string
{
    $clean = trim($value);
    $clean = strip_tags($clean);

    return preg_replace('/\s+/u', ' ', $clean) ?? '';
}

$name = sanitize_text($_POST['name'] ?? '');
$email = sanitize_text($_POST['email'] ?? '');
$subject = sanitize_text($_POST['subject'] ?? '');
$budget = sanitize_text($_POST['budget'] ?? '');
$message = sanitize_text($_POST['message'] ?? '');

$errors = [];

if ($name === '') {
    $errors[] = 'نام را وارد کنید.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'ایمیل معتبر وارد کنید.';
}

if ($subject === '') {
    $errors[] = 'عنوان یا شغل را وارد کنید.';
}

if ($message === '') {
    $errors[] = 'متن پیام نمی‌تواند خالی باشد.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$submission = [
    'id' => bin2hex(random_bytes(8)),
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ایجاد پوشه داده.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$line = json_encode($submission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($line === false || file_put_contents($dataFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ذخیره درخواست.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'درخواست شما با موفقیت ثبت شد. خیلی زود با شما تماس می‌گیرم.',
], JSON_UNESCAPED_UNICODE);
