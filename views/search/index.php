<?php
// Simple search results view
?>
<div class="page-header">
    <h1>Tìm kiếm: <?= htmlspecialchars($q) ?></h1>
</div>

<div class="card">
    <?php if (empty($results)): ?>
        <div class="card-body">
            <p>Không tìm thấy kết quả.</p>
        </div>
    <?php else: ?>
        <div class="subjects-list">
            <h2>Kết quả tìm kiếm</h2>
            <div class="subjects-grid">
                <?php foreach ($results as $subject): ?>
                    <div class="subject-card">
                        <?php if (!empty($subject['VideoURL'])): ?>
                            <div class="subject-thumbnail">
                                <img src="<?= htmlspecialchars(str_replace('.mp4', '.png', $subject['VideoURL'])) ?>" alt="<?= htmlspecialchars($subject['Title']) ?>">
                                <?php if (!empty($subject['Duration'])): ?>
                                    <span class="duration"><?= floor($subject['Duration'] / 60) ?>:<?= str_pad($subject['Duration'] % 60, 2, '0', STR_PAD_LEFT) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="subject-info">
                            <h3><a href="<?= BASE_URL ?>/subject/<?= (int)$subject['ID'] ?>"><?= htmlspecialchars($subject['Title']) ?></a></h3>
                            <p><?= htmlspecialchars(mb_substr($subject['Description'] ?? '', 0, 180)) ?><?php if (mb_strlen($subject['Description'] ?? '') > 180) echo '...'; ?></p>
                            <a href="<?= BASE_URL ?>/subject/<?= (int)$subject['ID'] ?>" class="btn-primary">Xem chi tiết</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
