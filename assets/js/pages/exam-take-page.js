// exam-take-page.js - initialize exam interactions and submission using Models.Exam
(function () {
    'use strict';

    function init() {
        if (!window.Models || !window.Models.Exam) return;
        const examForm = document.querySelector('.exam-form');
        if (!examForm) return;

        examForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const examId = examForm.dataset.examId;
            const answers = collectAnswers(examForm);
            try {
                const res = await window.Models.Exam.submit(examId, answers);
                if (res && res.success) {
                    window.location.href = '/exam/results.html';
                } else {
                    alert(res.error || 'Nộp bài thất bại');
                }
            } catch (err) {
                console.error('Submit failed', err);
                alert('Lỗi khi nộp bài (API chưa sẵn sàng)');
            }
        });
    }

    function collectAnswers(form) {
        const data = [];
        form.querySelectorAll('.answer-option.selected').forEach(el => {
            data.push({ question_id: el.dataset.questionId, answer_id: el.dataset.answerId });
        });
        return data;
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
