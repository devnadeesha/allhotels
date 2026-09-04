<?php
/**
 * AllHotels.lk - Shared helper functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_role($role) {
    if (!is_logged_in() || $_SESSION['user']['role'] !== $role) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Logs a notification and (in production) would dispatch a real
 * Email + WhatsApp message. Here we just persist an audit row so the
 * "Automated Owner Notification Matrix" from the spec is honoured.
 */
function notify(PDO $pdo, $user_id, $type, $message, $channel = 'both') {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, message, channel) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $type, $message, $channel]);

    // Hook point for real integrations, e.g.:
    // send_email($to, $subject, $body);
    // send_whatsapp($phone, $body);
}

function average_rating(PDO $pdo, $hotel_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE hotel_id = ?");
    $stmt->execute([$hotel_id]);
    return $stmt->fetch();
}

function star_html($rating) {
    $rating = round($rating);
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating ? '★' : '☆';
    }
    return $out;
}

function redirect($path) {
    header("Location: $path");
    exit;
}


