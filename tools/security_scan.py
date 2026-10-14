#!/usr/bin/env python3
"""Narrow source tripwire, not antivirus or proof of absence of vulnerabilities."""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
files = [root / 'cardraabta-payment-gateway-for-jazzcash.php', root / 'uninstall.php']
files += sorted((root / 'includes').glob('*.php'))
files += sorted((root / 'assets').glob('*.js'))
patterns = {
    'execution or unsafe deserialization': r'\b(?:eval|assert|system|exec|shell_exec|passthru|popen|proc_open|unserialize)\s*\(',
    'upload or filesystem write': r'\b(?:move_uploaded_file|file_put_contents|fwrite|wp_handle_upload|wp_upload_bits)\s*\(',
    'account or plugin installation': r'\b(?:wp_create_user|wp_insert_user|wp_set_password|activate_plugin|download_url)\s*\(',
    'remote PHP client': r'\b(?:curl_exec|wp_remote_get|wp_remote_post|wp_remote_request)\s*\(',
    'executable browser HTML': r'\b(?:innerHTML|outerHTML|dangerouslySetInnerHTML|document\.write)\b',
}
findings=[]
for file in files:
    source=file.read_text()
    for category, pattern in patterns.items():
        if re.search(pattern, source, re.I): findings.append(f'{file.relative_to(root)}: {category}')
if findings:
    raise SystemExit('\n'.join(findings))
print(f'PASS: {len(files)} runtime PHP/JavaScript files checked; no listed dangerous sinks found.')
print('Scope: regex tripwire plus separate manual review. Not an antivirus signature scan or penetration-test certification.')
