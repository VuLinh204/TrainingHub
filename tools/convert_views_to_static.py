#!/usr/bin/env python3
"""
convert_views_to_static.py

Scans the workspace `views/` folder and creates static HTML copies under `static/views/`.
- Replaces `<?= expr ?>` with a placeholder `{{ expr }}` (trimmed).
- Removes `<?php ... ?>` blocks.
- Prepends a comment to include `static/_header.html` and appends comment for `_footer.html`.

This is a best-effort conversion for migration; review results manually.
"""
import os
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
VIEWS_DIR = ROOT / 'views'
OUT_DIR = ROOT / 'static' / 'views'

PHP_SHORT_ECHO = re.compile(r"<\?=\s*(.*?)\s*\?>", re.S)
PHP_BLOCK = re.compile(r"<\?php[\s\S]*?\?>", re.S)

if not VIEWS_DIR.exists():
    print(f"Views directory not found: {VIEWS_DIR}")
    raise SystemExit(1)

created = []
for dirpath, dirnames, filenames in os.walk(VIEWS_DIR):
    rel_dir = Path(dirpath).relative_to(VIEWS_DIR)
    out_subdir = OUT_DIR / rel_dir
    out_subdir.mkdir(parents=True, exist_ok=True)

    for fname in filenames:
        if not fname.endswith('.php'):
            continue
        src_path = Path(dirpath) / fname
        with src_path.open('r', encoding='utf-8', errors='ignore') as f:
            content = f.read()

        # Replace short echo tags
        def short_echo_repl(m):
            inner = m.group(1).strip()
            # Simplify common patterns like htmlspecialchars($_GET['q'] ?? '') -> {{ q }}
            # But we'll keep the inner expression as a placeholder
            safe_inner = inner.replace('\n', ' ').strip()
            return '{{ ' + safe_inner + ' }}'

        content = PHP_SHORT_ECHO.sub(short_echo_repl, content)
        # Remove remaining php blocks
        content = PHP_BLOCK.sub('', content)

        # Remove any remaining leading/trailing php tags
        content = content.replace('<?', '').replace('?>', '')

        # Wrap with header/footer include comments
        out_html = []
        out_html.append('<!-- Converted from: ' + str(src_path.relative_to(ROOT)) + ' -->')
        out_html.append('<!-- Include: ../_header.html -->')
        out_html.append(content)
        out_html.append('<!-- Include: ../_footer.html -->')

        out_path = out_subdir / (Path(fname).stem + '.html')
        with out_path.open('w', encoding='utf-8') as outf:
            outf.write('\n'.join(out_html))

        created.append(out_path)

print(f"Created {len(created)} static HTML files under {OUT_DIR}")
for p in created:
    print('-', p.relative_to(ROOT))
