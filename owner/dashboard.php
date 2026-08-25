<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('owner');

$user = current_user();
$userId = $user['id'];

$totalHotels = $pdo->prepare("SELECT COUNT(*) FROM hotels WHERE user_id = ?");
$totalHotels->execute([$userId]);
$totalHotels = $totalHotels->fetchColumn();

$pending = $pdo->prepare("SELECT COUNT(*) FROM hotels WHERE user_id = ? AND status = 'pending'");
$pending->execute([$userId]);
$pending = $pending->fetchColumn();

$totalReviews = $pdo->prepare("SELECT COUNT(*) FROM reviews r JOIN hotels h ON h.id = r.hotel_id WHERE h.user_id = ?");
$totalReviews->execute([$userId]);
$totalReviews = $totalReviews->fetchColumn();

$totalBookings = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN hotels h ON h.id = b.hotel_id WHERE h.user_id = ?");
$totalBookings->execute([$userId]);
$totalBookings = $totalBookings->fetchColumn();

$page_title = 'Owner Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head">
        <div>
            <h2>Welcome back, <?= h($user['full_name']) ?></h2>
            <p>Here's a quick snapshot of your listings on AllHotels.lk.</p>
        </div>
        <a href="/allhotels/owner/add-hotel.php" class="btn btn-terracotta">+ Add New Hotel</a>
    </div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div>
            <div class="stat-grid">
                <div class="stat-card"><div class="num"><?= $totalHotels ?></div><div class="label">Listed Hotels</div></div>
                <div class="stat-card"><div class="num"><?= $pending ?></div><div class="label">Pending Approval</div></div>
                <div class="stat-card"><div class="num"><?= $totalReviews ?></div><div class="label">Total Reviews</div></div>
                <div class="stat-card"><div class="num"><?= $totalBookings ?></div><div class="label">Total Bookings</div></div>
            </div>

            <div class="panel">
                <h3>My Profile</h3>
                <div class="info-row"><div class="label">Full Name</div><div><?= h($user['full_name']) ?></div></div>
                <div class="info-row"><div class="label">Email</div><div><?= h($user['email']) ?></div></div>
                <div class="info-row"><div class="label">Role</div><div>Hotel Owner</div></div>
                <a href="../owner/account-settings.php" class="btn btn-outline btn-sm" style="margin-top:14px;">Edit Profile</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
