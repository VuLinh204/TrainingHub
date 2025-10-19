/*
 * api.js - Lightweight global API helper (vanilla JS)
 * Exposes window.API with methods: request, get, postJSON, postForm
 * Usage examples in api-example.js
 */
(function () {
    'use strict';

    // Determine base path. If server sets window.BASE_URL use it.
    function getBase() {
        if (window.BASE_URL) return window.BASE_URL.replace(/\/$/, '');
        // e.g. /Training/... -> return /Training
        const pathParts = window.location.pathname.split('/');
        return pathParts.length > 1 && pathParts[1] ? '/' + pathParts[1] : '';
    }

    const API_BASE = getBase();

    // Try to read CSRF token from meta tag (optional)
    function getCsrfToken() {
        try {
            const m = document.querySelector('meta[name="csrf-token"]');
            if (m) return m.getAttribute('content');
        } catch (e) {
            // ignore
        }
        return null;
    }

    // Parse response robustly and return JSON or throw with useful message
    async function parseJsonResponse(response) {
        const text = await response.text();
        const trimmed = text.trim();
        if (!trimmed) return null;

        // Some backends may accidentally concatenate JSON, try to recover last JSON object
        let jsonText = trimmed;
        try {
            if (!jsonText.startsWith('{') && jsonText.indexOf('{') !== -1) {
                jsonText = jsonText.slice(jsonText.indexOf('{'));
            }
            return JSON.parse(jsonText);
        } catch (err) {
            // If JSON parse fails, include raw text in error
            throw new Error(`Invalid JSON response: ${err.message}\nResponse: ${text.slice(0, 200)}`);
        }
    }

    async function request(path, options = {}) {
        const url = (path.startsWith('http') ? path : API_BASE + path);

        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest'
        };

        const csrf = getCsrfToken();
        if (csrf) defaultHeaders['X-CSRF-Token'] = csrf;

        const fetchOptions = {
            method: options.method || 'GET',
            headers: Object.assign({}, defaultHeaders, options.headers || {}),
        };

        if (options.body) {
            // if body is plain object and content-type not provided, assume JSON
            const contentType = (fetchOptions.headers['Content-Type'] || fetchOptions.headers['content-type'] || '').toLowerCase();
            if (typeof options.body === 'object' && !(options.body instanceof FormData) && !contentType) {
                fetchOptions.headers['Content-Type'] = 'application/json';
                fetchOptions.body = JSON.stringify(options.body);
            } else {
                fetchOptions.body = options.body;
            }
        }

        // Optional timeout support
        const timeout = options.timeout || 0; // ms, 0 = no timeout
        let controller;
        let timeoutId;
        if (timeout > 0 && typeof AbortController !== 'undefined') {
            controller = new AbortController();
            fetchOptions.signal = controller.signal;
            timeoutId = setTimeout(() => controller.abort(), timeout);
        }

        try {
            const res = await fetch(url, fetchOptions);
            if (timeoutId) clearTimeout(timeoutId);

            if (!res.ok) {
                // Try to parse body for server error message
                let bodyText = '';
                try {
                    bodyText = await res.text();
                } catch (e) {
                    bodyText = res.statusText;
                }
                const err = new Error(`HTTP ${res.status} ${res.statusText}: ${bodyText}`);
                err.status = res.status;
                throw err;
            }

            // Try to parse JSON, if empty return null
            const parsed = await parseJsonResponse(res);
            return parsed;
        } catch (err) {
            // If fetch was aborted by timeout
            if (err.name === 'AbortError') {
                throw new Error('Request timed out');
            }
            throw err;
        }
    }

    // Convenience helpers
    function get(path, options = {}) {
        return request(path, Object.assign({}, options, { method: 'GET' }));
    }

    function postJSON(path, data, options = {}) {
        return request(path, Object.assign({}, options, {
            method: 'POST',
            headers: Object.assign({}, options.headers || {}, { 'Content-Type': 'application/json' }),
            body: typeof data === 'string' ? data : JSON.stringify(data),
        }));
    }

    function postForm(path, formData, options = {}) {
        // formData should be FormData instance
        return request(path, Object.assign({}, options, {
            method: 'POST',
            body: formData,
        }));
    }

    // Expose global API
    window.API = {
        base: API_BASE,
        request: request,
        get: get,
        postJSON: postJSON,
        postForm: postForm,
        getCsrfToken: getCsrfToken,
    };

})();
