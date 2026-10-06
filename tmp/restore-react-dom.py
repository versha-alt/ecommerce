from pathlib import Path
import tarfile
p=Path('node_modules/react-dom');p.rename(p.with_name('react-dom-unreadable-20261006'))
with tarfile.open('tmp/react-dom-19.3.0.tgz') as archive:
 for item in archive.getmembers():
  if item.isfile():
   target=p/Path(item.name).relative_to('package');target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(archive.extractfile(item).read())
