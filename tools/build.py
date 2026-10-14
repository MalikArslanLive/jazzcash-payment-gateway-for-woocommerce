#!/usr/bin/env python3
"""Build only the reviewed plugin runtime; never include tests, keys or tooling."""
from pathlib import Path
import re, zipfile, hashlib
root=Path(__file__).resolve().parents[1]
slug='cardraabta-payment-gateway-for-jazzcash'
version=re.search(r'Version:\s*([^\s]+)',(root/(slug+'.php')).read_text()).group(1)
files=[root/(slug+'.php'),root/'uninstall.php',root/'readme.txt',root/'README.md',root/'LICENSE']
for name in ['includes','assets','languages']:
 if (root/name).exists(): files.extend(p for p in (root/name).rglob('*') if p.is_file())
out=root/'dist';out.mkdir(exist_ok=True)
archive=out/(slug+'-'+version+'.zip')
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as z:
 for p in sorted(files):
  info=zipfile.ZipInfo(slug+'/'+p.relative_to(root).as_posix(),date_time=(2026,1,1,0,0,0))
  info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED
  z.writestr(info,p.read_bytes())
with zipfile.ZipFile(archive) as z: assert z.testzip() is None
print(archive)
print('SHA256 '+hashlib.sha256(archive.read_bytes()).hexdigest())
