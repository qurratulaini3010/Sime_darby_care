<?php $pageTitle = $pageTitle ?? APP_NAME; $u = current_user(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($pageTitle) ?> · Sime Darby Care</title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/theme.css"></head><body>
<div class="app-shell<?= $u ? '' : ' no-sidebar' ?>">
<?php if ($u): ?>
<aside class="sidebar"><div class="brand"><div class="brand-mark">SD</div><div><div class="brand-name">Sime Darby Care</div><span class="brand-sub">Township assistance · Elmina</span></div></div>
<nav class="side-nav">
<?php if ($u['role']==='owner'): ?>
<a class="<?= str_contains($_SERVER['PHP_SELF'],'owner/index')?'active':'' ?>" href="<?= BASE_URL ?>/owner/index.php">⌂ &nbsp; Home</a>
<a class="<?= str_contains($_SERVER['PHP_SELF'],'report')?'active':'' ?>" href="<?= BASE_URL ?>/owner/report.php">＋ &nbsp; Report an Issue</a>
<a class="<?= str_contains($_SERVER['PHP_SELF'],'requests') || str_contains($_SERVER['PHP_SELF'],'status')?'active':'' ?>" href="<?= BASE_URL ?>/owner/requests.php">◷ &nbsp; My Requests</a>
<a class="<?= str_contains($_SERVER['PHP_SELF'],'notifications')?'active':'' ?>" href="<?= BASE_URL ?>/owner/notifications.php">♧ &nbsp; Notifications</a>
<a class="<?= str_contains($_SERVER['PHP_SELF'],'profile')?'active':'' ?>" href="<?= BASE_URL ?>/owner/profile.php">♙ &nbsp; Profile</a>
<?php elseif ($u['role']==='operator'): ?>
<a href="<?= BASE_URL ?>/operator/index.php">▦ &nbsp; Operations Overview</a><a href="<?= BASE_URL ?>/operator/requests.php">▤ &nbsp; Incoming Requests</a><a href="<?= BASE_URL ?>/operator/analytics.php">▥ &nbsp; Analytics</a>
<?php else: ?><a href="<?= BASE_URL ?>/provider/index.php">▦ &nbsp; Assigned Jobs</a><?php endif; ?>
<a href="<?= BASE_URL ?>/logout.php">↪ &nbsp; Sign out</a></nav>
<div class="side-footer"><strong>SAFETY FIRST</strong>For fire, serious injury or immediate danger, call 999 first. Sime Darby Care coordinates supplementary support.</div></aside>
<?php endif; ?>
<main class="main"><header class="topbar"><div><div class="eyebrow">Sime Darby Property · Elmina Township</div><h1><?= e($pageTitle) ?></h1></div><?php if ($u): ?>
    <div class="topbar-account">
        <div class="user-pill">
            <?= e($u['full_name']) ?> · <?= e(ucfirst($u['role'])) ?>
        </div>
        <a class="btn btn-secondary btn-sm mobile-signout"
           href="<?= BASE_URL ?>/logout.php">
            ↪ Sign out
        </a>
    </div>
<?php endif; ?></header>
