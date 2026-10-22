<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';

$u = require_role(['provider']);
verify_csrf();

$st = $pdo->prepare('SELECT * FROM service_providers WHERE user_id = ? LIMIT 1');
$st->execute([$u['id']]);
$provider = $st->fetch();

if (!$provider) {
    http_response_code(403);
    exit('Provider profile not configured.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['request_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $r = request_by_id($pdo, $id);

    if (!$r || (int)$r['provider_id'] !== (int)$provider['id']) {
        $error = 'This job is not assigned to your account.';
    } else {
        try {
            $pdo->beginTransaction();

            // Refresh the request inside the transaction so stale browser pages
            // cannot skip a status or overwrite a newer status.
            $lock = $pdo->prepare('SELECT status, provider_id FROM requests WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $current = $lock->fetch();

            if (!$current || (int)$current['provider_id'] !== (int)$provider['id']) {
                throw new RuntimeException('This job is no longer assigned to your account.');
            }

            $currentStatus = (int)$current['status'];
            $targets = [
                'accept' => 1,
                'onway' => 2,
                'progress' => 3,
                'resolve' => 4,
            ];

            if (!array_key_exists($action, $targets)) {
                throw new RuntimeException('Choose a valid job action.');
            }

            $target = $targets[$action];

            if ($action === 'accept') {
                if ($currentStatus !== 0) {
                    throw new RuntimeException('This job has already been accepted or progressed.');
                }

                log_status($pdo, $id, 1, (int)$u['id'], 'Accepted by assigned service provider.');
                $pdo->prepare("UPDATE service_providers SET availability = 'busy' WHERE id = ?")
                    ->execute([$provider['id']]);
                add_notification($pdo, (int)$r['owner_id'], $id, 'Provider accepted job',
                    'The assigned provider has accepted request ' . $r['request_no'] . '.');
                add_notification($pdo, (int)$r['owner_id'], $id, 'Request status updated',
                    status_names()[1] . ' for ' . $r['request_no'] . '.');
                $success = 'Job accepted. The request status is now Assigned to Responder.';
            } else {
                if ($currentStatus !== $target - 1) {
                    throw new RuntimeException('Please complete the previous step before moving this job forward.');
                }

                $notes = [
                    'onway' => 'Provider marked as on the way.',
                    'progress' => 'Provider started work on the issue.',
                    'resolve' => 'Provider marked the job as resolved.',
                ];
                log_status($pdo, $id, $target, (int)$u['id'], $notes[$action]);
                add_notification($pdo, (int)$r['owner_id'], $id, 'Request status updated',
                    status_names()[$target] . ' for ' . $r['request_no'] . '.');

                // Notify the operator account(s), if configured.
                $operators = $pdo->query("SELECT id FROM users WHERE role = 'operator'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($operators as $operatorId) {
                    add_notification($pdo, (int)$operatorId, $id, 'Job status updated',
                        $r['request_no'] . ' is now ' . status_names()[$target] . '.');
                }

                if ($target === 4) {
                    $pdo->prepare("UPDATE service_providers SET availability = 'available' WHERE id = ?")
                        ->execute([$provider['id']]);
                }
                $success = 'Job status updated to ' . status_names()[$target] . '.';
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Could not update this job. Please try again.';
        }
    }
}

$st = $pdo->prepare(
    'SELECT r.*, c.name AS category_name, c.slug AS category_slug
     FROM requests r
     JOIN categories c ON c.id = r.category_id
     WHERE r.provider_id = ?
     ORDER BY r.created_at DESC'
);
$st->execute([$provider['id']]);
$jobs = $st->fetchAll();

$newJobs = array_values(array_filter($jobs, static fn($job) => (int)$job['status'] === 0));
$activeJobs = array_values(array_filter($jobs, static fn($job) => (int)$job['status'] >= 1 && (int)$job['status'] < 4));
$completedJobs = array_values(array_filter($jobs, static fn($job) => (int)$job['status'] === 4));

$pageTitle = 'Assigned Jobs';
require __DIR__ . '/../includes/header.php';

function render_provider_jobs(array $jobs, string $emptyMessage, array $provider, array $u): void {
    if (!$jobs) {
        echo '<div class="card empty">' . e($emptyMessage) . '</div>';
        return;
    }

    foreach ($jobs as $r) {
        $status = (int)$r['status'];
        $nextAction = null;
        $nextLabel = '';
        if ($status === 0) {
            $nextAction = 'accept'; $nextLabel = 'Accept job';
        } elseif ($status === 1) {
            $nextAction = 'onway'; $nextLabel = 'Mark On the Way';
        } elseif ($status === 2) {
            $nextAction = 'progress'; $nextLabel = 'Start work';
        } elseif ($status === 3) {
            $nextAction = 'resolve'; $nextLabel = 'Mark Resolved';
        }
        ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
                <div>
                    <div class="eyebrow"><?= e($r['request_no']) ?></div>
                    <h3 style="margin-top:7px"><?= e($r['title']) ?></h3>
                    <p class="muted" style="font-size:12px"><?= e($r['category_name']) ?> · <?= e($r['township']) ?></p>
                </div>
                <span class="badge <?= status_class($status) ?>"><?= e(status_names()[$status]) ?></span>
            </div>
            <p><?= nl2br(e($r['description'])) ?></p>
            <p class="muted" style="font-size:12px">📍 <?= e($r['location']) ?></p>
            <div class="grid grid-2" style="margin-top:12px">
                <div>
                    <small class="muted">Current stage</small>
                    <strong style="display:block"><?= e(status_names()[$status]) ?></strong>
                </div>
                <div>
                    <small class="muted">Next step</small>
                    <strong style="display:block"><?= e($nextLabel ?: 'No further action') ?></strong>
                </div>
            </div>
            <?php if ($nextAction !== null): ?>
                <form method="post" style="margin-top:14px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="<?= e($nextAction) ?>">
                    <button class="btn <?= $nextAction === 'resolve' ? 'btn-primary' : 'btn-secondary' ?> btn-block">
                        <?= e($nextLabel) ?>
                    </button>
                </form>
            <?php else: ?>
                <div class="flash" style="margin-top:14px">This job is marked as resolved.</div>
            <?php endif; ?>
            <p class="muted" style="font-size:11px;margin-bottom:0;margin-top:10px">
                Status changes are saved to the request history and shared with the resident.
            </p>
        </div>
        <?php
    }
}
?>
<div class="hero">
    <div>
        <div class="eyebrow">AUTHORISED SERVICE PROVIDER</div>
        <h2>Your assigned <span style="color:var(--sd-red)">jobs</span></h2>
        <p>Review each assignment and follow the next available step. The resident can follow saved status updates.</p>
    </div>
    <div class="hero-art">🛠️</div>
</div>

<?php if ($success): ?><div class="flash" style="margin-top:15px"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error" style="margin-top:15px"><?= e($error) ?></div><?php endif; ?>

<div class="grid grid-3" style="margin-top:18px">
    <div class="card"><div class="eyebrow">NEW ASSIGNMENTS</div><h2><?= count($newJobs) ?></h2><p class="muted">Awaiting provider acceptance</p></div>
    <div class="card"><div class="eyebrow">ACTIVE JOBS</div><h2><?= count($activeJobs) ?></h2><p class="muted">Accepted or in progress</p></div>
    <div class="card"><div class="eyebrow">COMPLETED</div><h2><?= count($completedJobs) ?></h2><p class="muted">Marked as resolved</p></div>
</div>

<div class="section-head"><h3>New assignments</h3><span class="muted"><?= count($newJobs) ?> job(s)</span></div>
<div class="grid"><?php render_provider_jobs($newJobs, 'No new assignments. New jobs assigned by the operator will appear here.', $provider, $u); ?></div>

<div class="section-head"><h3>Active jobs</h3><span class="muted"><?= count($activeJobs) ?> job(s)</span></div>
<div class="grid"><?php render_provider_jobs($activeJobs, 'No active jobs at the moment.', $provider, $u); ?></div>

<div class="section-head"><h3>Completed jobs</h3><span class="muted"><?= count($completedJobs) ?> job(s)</span></div>
<div class="grid"><?php render_provider_jobs($completedJobs, 'No completed jobs yet.', $provider, $u); ?></div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
