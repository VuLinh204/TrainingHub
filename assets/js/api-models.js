/*
 * api-models.js
 * Client-side model wrappers that call backend REST API endpoints using window.API
 * Each model exposes basic CRUD methods: list, get, create, update, remove
 * and can be extended with domain-specific methods.
 *
 * This file is intentionally framework-free and works with the existing `assets/js/api.js` helper.
 */
(function () {
    'use strict';

    if (!window.API) {
        console.warn('window.API not found - include assets/js/api.js before api-models.js');
    }

    function buildUrl(base, id) {
        if (!id) return base;
        return base + '/' + encodeURIComponent(id);
    }

    function makeModel(basePath) {
        return {
            list: function (params) {
                // params -> query string
                let url = basePath;
                if (params && typeof params === 'object') {
                    const qs = Object.keys(params)
                        .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
                        .join('&');
                    if (qs) url += '?' + qs;
                }
                return window.API.get(url);
            },
            get: function (id) {
                return window.API.get(buildUrl(basePath, id));
            },
            create: function (data) {
                return window.API.postJSON(basePath, data);
            },
            update: function (id, data) {
                return window.API.postJSON(buildUrl(basePath, id), data);
            },
            remove: function (id) {
                return window.API.request(buildUrl(basePath, id), { method: 'DELETE' });
            },
        };
    }

    // Define model clients
    const Models = {
        Employee: makeModel('/api/employees'),
        Subject: makeModel('/api/subjects'),
        Exam: makeModel('/api/exams'),
        Question: makeModel('/api/questions'),
        Answer: makeModel('/api/answers'),
        Notification: makeModel('/api/notifications'),
        Certificate: makeModel('/api/certificates'),
        Completion: makeModel('/api/completions'),
        Assign: makeModel('/api/assigns'),
        WatchLog: makeModel('/api/watchlogs'),
    };

    // Domain-specific helpers (examples)
    Models.Subject.startExam = function (subjectId) {
        return window.API.postJSON(`/api/subjects/${encodeURIComponent(subjectId)}/start`, {});
    };

    Models.WatchLog.track = function (payload) {
        return window.API.postJSON('/api/watchlogs/track', payload);
    };

    Models.Exam.submit = function (examId, answers) {
        return window.API.postJSON(`/api/exams/${encodeURIComponent(examId)}/submit`, { answers });
    };

    // Attach to global
    window.Models = Models;

    // Small usage examples in console
    // window.Models.Employee.list().then(console.log).catch(console.error);
    // window.Models.Subject.get(1).then(console.log);

})();
