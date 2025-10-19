// dashboard-page.js - fetch and render dashboard stats using Models
(function () {
    'use strict';

    async function init() {
        if (!window.Models || !window.Models.Completion) return;
        const statsEl = document.querySelector('.dashboard-stats');
        if (!statsEl) return;

        try {
            const stats = await window.Models.Completion.get('summary');
            renderStats(stats);
        } catch (err) {
            console.error('Dashboard load failed', err);
            statsEl.innerHTML = '<div class="error">Không thể tải số liệu (API chưa sẵn sàng).</div>';
        }
    }

    function renderStats(s) {
        const el = document.querySelector('.dashboard-stats');
        if (!el) return;
        el.innerHTML = `
            <div class="stat-card">Khóa học: <strong>${s.total_assigned || 0}</strong></div>
            <div class="stat-card">Chứng chỉ: <strong>${s.certificates || 0}</strong></div>
            <div class="stat-card">Tỉ lệ hoàn thành: <strong>${s.percentage || 0}%</strong></div>
        `;
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
