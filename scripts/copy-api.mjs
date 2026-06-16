import { cpSync, existsSync, mkdirSync, rmSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const apiSrc = join(root, 'api');
const apiDest = join(root, 'public_html', 'api');

if (existsSync(apiDest)) {
  rmSync(apiDest, { recursive: true, force: true });
}

cpSync(apiSrc, apiDest, {
  recursive: true,
  filter: (src) => {
    const rel = src.replace(/\\/g, '/');
    if (rel.includes('/vendor/')) return false;
    if (rel.endsWith('/.env')) return false;
    if (rel.includes('.sqlite')) return false;
    return true;
  },
});

mkdirSync(join(apiDest, 'data'), { recursive: true });

console.log('Running composer install in public_html/api...');
const composerCmd = existsSync(join(root, 'composer.phar'))
  ? 'php ../composer.phar install --no-dev --optimize-autoloader'
  : 'composer install --no-dev --optimize-autoloader';

try {
  execSync(composerCmd, {
    cwd: apiDest,
    stdio: 'inherit',
  });
} catch {
  console.warn('Composer install failed. Run `composer install --no-dev` in public_html/api on the server.');
}

console.log('API copied to public_html/api');
