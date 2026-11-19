"""Exercise the actual rsync protection rules against synthetic sibling data."""
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


class PositiveTreeScope(unittest.TestCase):
    @unittest.skipUnless(shutil.which('rsync'), 'rsync required')
    def test_sync_keeps_nested_site_and_private_state(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp); source = root/'source'; target = root/'target'
            source.mkdir(); target.mkdir()
            protected = {
                'my.aicountly.co.in/index.html': 'independent My site',
                'my.aicountly.co.in/api/data.sqlite': 'independent My data',
                '.env': 'synthetic local config',
                'api/.env': 'synthetic API config',
                'api/data/production.sqlite': 'synthetic persisted data',
            }
            for name, value in protected.items():
                path = target/name; path.parent.mkdir(parents=True, exist_ok=True); path.write_text(value)
            (target/'obsolete.js').write_text('obsolete public asset')
            (source/'index.html').write_text('new PositiveTree site')
            (source/'.env').write_text('must not replace the destination')
            subprocess.run(['rsync', '-avz', '--delete', '--exclude=.env', '--exclude=api/.env',
                            '--exclude=api/data/', '--exclude=/my.aicountly.co.in/',
                            str(source)+'/', str(target)+'/'], check=True, capture_output=True)
            self.assertEqual((target/'index.html').read_text(), 'new PositiveTree site')
            self.assertFalse((target/'obsolete.js').exists())
            for name, value in protected.items():
                self.assertEqual((target/name).read_text(), value)


if __name__ == '__main__': unittest.main()
