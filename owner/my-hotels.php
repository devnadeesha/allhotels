<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('owner');

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$hotels = $stmt->fetchAll();

$page_title = 'My Hotels';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>My Hotels</h2><p>Manage the properties you've listed on AllHotels.lk.</p></div>
        <a href="/owner/add-hotel.php" class="btn btn-terracotta">+ Add New Hotel</a>
    </div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <?php if (empty($hotels)): ?>
                <p class="footer-note">You haven't listed any hotels yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Hotel</th><th>District</th><th>Price</th><th>Plan</th><th>Status</th><th>Listed</th></tr></thead>
                <tbody>
                <?php foreach ($hotels as $h2): ?>
                    <tr>
                        <td><a href="/allhotels/hotel-details/hotel-details.php?id=<?= (int)$h2['id'] ?>"><?= h($h2['name']) ?></a></td>
                        <td><?= h($h2['district']) ?></td>
                        <td>Rs. <?= number_format($h2['starting_price']) ?></td>
                        <td><?= $h2['is_premium'] ? '★ Premium' : 'Free' ?></td>
                        <td><span class="status-pill status-<?= h($h2['status']) ?>"><?= h($h2['status']) ?></span></td>
                        <td><?= date('d M Y', strtotime($h2['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
      
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
