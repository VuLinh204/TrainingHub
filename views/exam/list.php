<div class="container mt-4">
    <h2 class="mb-3">Danh sách bài kiểm tra</h2>

    <?php if (empty($exams)): ?>
        <div class="alert alert-info">Chưa có bài kiểm tra nào khả dụng.</div>
    <?php else: ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên khóa học</th>
                    <th>Điểm cao nhất</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exams as $exam): ?>
                <tr>
                    <td><?= htmlspecialchars($exam['ExamID']) ?></td>
                    <td><?= htmlspecialchars($exam['SubjectName']) ?></td>
                    <td><?= htmlspecialchars($exam['BestScore'] ?? '-') ?></td>
                    <td>
                        <?= $exam['Passed'] ? '<span class="badge bg-success">Đạt</span>' : '<span class="badge bg-secondary">Chưa đạt</span>' ?>
                    </td>
                    <td>
                        <a href="/Training/exam/<?= $exam['SubjectID'] ?>" class="btn btn-primary btn-sm">
                            Làm bài
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
