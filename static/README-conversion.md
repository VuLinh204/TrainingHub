This folder contains a simple static HTML scaffold generated from the PHP-based Training project.

What I created
- `_header.html` and `_footer.html`: static header/footer templates that include the CSS and JS asset references.
- `index.html`, `login.html`, `dashboard.html`, `subject-list.html`, `exam-take.html`: sample pages that use the static header/footer (comment markers) and contain placeholders for dynamic data.
- `api.js` and `api-example.js` (placed under `assets/js`) provide a vanilla JS API helper and examples for calling backend endpoints with fetch.

Assumptions
- The original application used `BASE_URL` and session-based PHP for user/auth. Static pages cannot provide server-side login or session management.
- The static header includes a `<meta name="csrf-token">` placeholder. If you have a CSRF token, inject it into that meta tag via server or build step.
- Links in the static pages are absolute (start with `/`) and expect assets to be reachable at `/assets/...`.

How to use
1. Serve the project (e.g., with Apache as before). Open `/static/index.html` in the browser to see the static homepage.
2. To wire dynamic behaviour, use `assets/js/api.js` to call backend endpoints. Example calls are in `assets/js/api-example.js`.
3. If you want to fully remove PHP, you'll need to rewrite server-side logic (auth, DB, routes) or replace it with a client-side app talking to an API.

Next steps (recommended)
- Replace PHP-rendered pages by converting each `views/*.php` file into a static HTML page or SPA component. For pages that require data, use the API helper to fetch data and render on the client.
- Add client-side routing if you want a single-page experience.
- Wire authentication: either keep a small server-side auth (recommended) or implement token-based auth (JWT) and an authentication API.
- Update `sw.js` and `manifest.json` to include the static pages if you want PWA offline caching for them.

If you want, I can:
- Convert more specific PHP view files into static HTML files (do one-by-one).
- Replace server-side route redirects with client-side links.
- Inject content from specific PHP views into static templates.

Notes
- The static pages are intentionally minimal and include placeholders. They are a starting point for migrating from PHP views to static HTML.
