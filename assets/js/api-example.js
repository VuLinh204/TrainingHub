/* api-example.js
 * Examples of using the global API helper (assets/js/api.js)
 * Include this file in pages or copy patterns into your existing scripts.
 */

// Example: GET request
async function loadNotifications() {
    try {
        const data = await window.API.get('/notifications', { timeout: 8000 });
        console.log('Notifications', data);
    } catch (err) {
        console.error('Failed to load notifications', err);
        window.showToast && window.showToast('Không tải được thông báo', 'error');
    }
}

// Example: POST JSON
async function startExam(subjectId) {
    try {
        const payload = { subject_id: subjectId };
        const result = await window.API.postJSON(`/exam/${subjectId}/start`, payload, { timeout: 10000 });
        console.log('Exam started', result);
        if (result && result.success) {
            window.location.href = `/exam/${subjectId}/take`;
        } else {
            window.showToast && window.showToast(result.error || 'Không thể bắt đầu bài thi', 'error');
        }
    } catch (err) {
        console.error('Start exam failed', err);
        window.showToast && window.showToast(err.message || 'Lỗi mạng', 'error');
    }
}

// Example: POST FormData (file upload)
async function uploadProfilePicture(fileInput) {
    const file = fileInput.files && fileInput.files[0];
    if (!file) return;

    const form = new FormData();
    form.append('avatar', file);

    try {
        const res = await window.API.postForm('/employee/avatar', form, { timeout: 20000 });
        console.log('Upload result', res);
        if (res && res.success) {
            window.showToast('Tải ảnh lên thành công', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            window.showToast(res.error || 'Không thể tải ảnh lên', 'error');
        }
    } catch (err) {
        console.error('Upload failed', err);
        window.showToast && window.showToast(err.message || 'Lỗi mạng', 'error');
    }
}

// Expose examples to global scope for manual testing from console
window.APIExamples = {
    loadNotifications,
    startExam,
    uploadProfilePicture,
};
