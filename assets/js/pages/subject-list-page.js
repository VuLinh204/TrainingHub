// subject-list-page.js - fetch and render subject list using Models.Subject
(function () {
    'use strict';

    async function init() {
        if (!window.Models || !window.Models.Subject) return;
        const listEl = document.querySelector('.subject-items');
        if (!listEl) return;

        try {
            const subjects = await window.Models.Subject.list();
            renderList(subjects || []);
        } catch (err) {
            console.error('Subjects load failed', err);
            listEl.innerHTML = '<li>Không thể tải danh sách (API chưa sẵn sàng).</li>';
        }
    }

    function renderList(items) {
        const el = document.querySelector('.subject-items');
        if (!el) return;
        if (items.length === 0) {
            el.innerHTML = '<li>Không có khóa học</li>';
            return;
        }
        el.innerHTML = items.map(s => `<li><a href="/subject/${encodeURIComponent(s.ID)}.html">${escapeHtml(s.Title)}</a></li>`).join('');
    }

    function escapeHtml(str) {
        const d = document.createElement('div'); d.textContent = str; return d.innerHTML;
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
