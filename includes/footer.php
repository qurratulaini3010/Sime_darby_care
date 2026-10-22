<div class="footer-note">Sime Darby Care · University Hackathon 2026 · Concept prototype, not a live emergency dispatch service.</div>
</main></div>

<?php
$mobileUser = current_user();
if ($mobileUser):
    $role = $mobileUser['role'];
    $currentPath = $_SERVER['PHP_SELF'] ?? '';
?>
<nav class="bottom-nav" aria-label="Mobile navigation">
    <?php if ($role === 'owner'): ?>
        <a href="<?= BASE_URL ?>/owner/index.php" class="<?= str_contains($currentPath,'owner/index') ? 'active' : '' ?>">
            <span>⌂</span><small>Home</small>
        </a>
        <a href="<?= BASE_URL ?>/owner/report.php" class="<?= str_contains($currentPath,'report') ? 'active' : '' ?>">
            <span>＋</span><small>Report</small>
        </a>
        <a href="<?= BASE_URL ?>/owner/requests.php" class="<?= str_contains($currentPath,'requests') || str_contains($currentPath,'status') ? 'active' : '' ?>">
            <span>◷</span><small>My Requests</small>
        </a>
        <a href="<?= BASE_URL ?>/owner/notifications.php" class="<?= str_contains($currentPath,'notifications') ? 'active' : '' ?>">
            <span>♧</span><small>Alerts</small>
        </a>
        <a href="<?= BASE_URL ?>/owner/profile.php" class="<?= str_contains($currentPath,'profile') ? 'active' : '' ?>">
            <span>♙</span><small>Profile</small>
        </a>
    <?php elseif ($role === 'operator'): ?>
        <a href="<?= BASE_URL ?>/operator/index.php" class="<?= str_contains($currentPath,'operator/index') ? 'active' : '' ?>">
            <span>▦</span><small>Overview</small>
        </a>
        <a href="<?= BASE_URL ?>/operator/requests.php" class="<?= str_contains($currentPath,'operator/requests') || str_contains($currentPath,'operator/request.php') ? 'active' : '' ?>">
            <span>▤</span><small>Requests</small>
        </a>
        <a href="<?= BASE_URL ?>/operator/analytics.php" class="<?= str_contains($currentPath,'analytics') ? 'active' : '' ?>">
            <span>▥</span><small>Analytics</small>
        </a>
        <a href="<?= BASE_URL ?>/logout.php">
            <span>↪</span><small>Sign out</small>
        </a>
    <?php elseif ($role === 'provider'): ?>
        <a href="<?= BASE_URL ?>/provider/index.php" class="<?= str_contains($currentPath,'provider/index') ? 'active' : '' ?>">
            <span>🛠</span><small>Assigned Jobs</small>
        </a>
        <a href="<?= BASE_URL ?>/logout.php">
            <span>↪</span><small>Sign out</small>
        </a>
    <?php endif; ?>
</nav>
<?php endif; ?>
</body></html>
