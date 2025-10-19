// profile-page.js - fetch and render employee profile using Models.Employee and Models.Certificate
(function () {
    'use strict';

    async function init() {
        if (!window.Models || !window.Models.Employee) return;

        const profileContainer = document.querySelector('.profile-container');
        if (!profileContainer) return;

        try {
            const res = await window.Models.Employee.get('me');
            renderProfile(res);
            const certs = await window.Models.Certificate.list({ employee_id: res.id });
            renderCertificates(certs);
        } catch (err) {
            console.error('Profile load failed', err);
            profileContainer.innerHTML = '<div class="error">Không thể tải hồ sơ (API chưa sẵn sàng).</div>';
        }
    }

    function renderProfile(data) {
        const el = document.querySelector('.profile-container');
        if (!el) return;
        el.innerHTML = `
            <h2>${escapeHtml(data.FirstName || '')} ${escapeHtml(data.LastName || '')}</h2>
            <p>Email: ${escapeHtml(data.Email || '')}</p>
            <p>Department: ${escapeHtml(data.DepartmentName || '')}</p>
        `;
    }

    function renderCertificates(certs) {
        const listEl = document.querySelector('.certificates-list');
        if (!listEl) return;
        if (!certs || certs.length === 0) {
            listEl.innerHTML = '<p>Chưa có chứng chỉ</p>';
            return;
        }
        listEl.innerHTML = certs.map(c => `
            <div class="certificate-item">
                <h4>${escapeHtml(c.SubjectName)}</h4>
                <p>Code: ${escapeHtml(c.CertificateCode || '')}</p>
                <a href="/certificates/${encodeURIComponent(c.ID)}.html">Xem</a>
            </div>
        `).join('');
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

})();
