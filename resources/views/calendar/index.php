<?php use App\Core\Url; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= htmlspecialchars($monthName) ?> <?= $year ?></h2>
    <div class="d-flex gap-2">
        <a href="<?= Url::to('/calendar') ?>?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-outline-secondary">&laquo; Onceki Ay</a>
        <a href="<?= Url::to('/calendar') ?>?year=<?= date('Y') ?>&month=<?= date('n') ?>" class="btn btn-outline-primary">Bugun</a>
        <a href="<?= Url::to('/calendar') ?>?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-outline-secondary">Sonraki Ay &raquo;</a>
    </div>
</div>

<?php
$statusBadgeClass = [
    'pending_schedule' => 'bg-warning text-dark',
    'scheduled' => 'bg-info text-dark',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger',
];

$dayNames = ['Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt', 'Paz'];

// Build a flat list of cells: leading blanks + day numbers + trailing blanks
$totalCells = $startWeekday - 1 + $daysInMonth;
$totalCells = (int) (ceil($totalCells / 7) * 7);
?>

<div class="card shadow-sm p-3">
    <div class="calendar-grid" style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 4px;">
        <?php foreach ($dayNames as $dayName): ?>
            <div class="text-center fw-bold text-muted small py-1"><?= $dayName ?></div>
        <?php endforeach; ?>

        <?php for ($cell = 0; $cell < $totalCells; $cell++): ?>
            <?php $day = $cell - ($startWeekday - 1) + 1; ?>
            <?php if ($day < 1 || $day > $daysInMonth): ?>
                <div class="border rounded p-2" style="min-height: 110px; background:#f8f9fa;"></div>
            <?php else: ?>
                <div class="border rounded p-2 <?= $day === $todayDay ? 'border-primary border-2' : '' ?>" style="min-height: 110px;">
                    <div class="small fw-bold mb-1 <?= $day === $todayDay ? 'text-primary' : '' ?>"><?= $day ?></div>
                    <?php if (!empty($jobsByDay[$day])): ?>
                        <?php foreach ($jobsByDay[$day] as $job): ?>
                            <a href="<?= Url::to('/jobs/show') ?>?id=<?= $job['id'] ?>"
                               class="d-block text-decoration-none mb-1 p-1 rounded small <?= $statusBadgeClass[$job['status']] ?? 'bg-secondary' ?>"
                               style="color:#fff; line-height:1.2;">
                                <?php if (!empty($job['start_time'])): ?>
                                    <strong><?= htmlspecialchars(substr($job['start_time'], 0, 5)) ?></strong>
                                <?php endif; ?>
                                <?= htmlspecialchars($job['customer_name'] ?? '') ?>
                                <?php if (!empty($job['duration_hours'])): ?>
                                    (<?= htmlspecialchars($job['duration_hours']) ?>sa)
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
</div>

<div class="mt-3 small text-muted">
    Renkler: <span class="badge bg-warning text-dark">Baslangic Bekleniyor</span>
    <span class="badge bg-info text-dark">Planlandi</span>
    <span class="badge bg-primary">Devam Ediyor</span>
    <span class="badge bg-success">Tamamlandi</span>
    <span class="badge bg-danger">Iptal</span>
</div>
