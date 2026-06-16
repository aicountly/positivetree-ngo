import { cpSync, existsSync, mkdirSync, readdirSync, statSync } from 'fs';
import { join, dirname, relative } from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const apiSrc = join(root, 'api');
const apiDest = join(root, 'public_html', 'api');

function shouldSkipSource(relPath) {
  const rel = relPath.replace(/\\/g, '/');
  if (rel.includes('/vendor/')) return true;
  if (rel.endsWith('/.env')) return true;
  if (rel.includes('.sqlite')) return true;
  return false;
}

function copyTree(src, dest) {
  mkdirSync(dest, { recursive: true });

  for (const entry of readdirSync(src)) {
    const srcPath = join(src, entry);
    const destPath = join(dest, entry);
    const rel = relative(apiSrc, srcPath);

    if (shouldSkipSource(rel)) {
      continue;
    }

    if (statSync(srcPath).isDirectory()) {
      copyTree(srcPath, destPath);
    } else {
      cpSync(srcPath, destPath);
    }
  }
}

mkdirSync(join(apiDest, 'data'), { recursive: true });
copyTree(apiSrc, apiDest);

console.log('Running composer install in public_html/api...');
const composerPhar = join(root, 'composer.phar');
const composerCmd = existsSync(composerPhar)
  ? `php "${composerPhar}" install --no-dev --optimize-autoloader`
  : 'composer install --no-dev --optimize-autoloader';

try {
  execSync(composerCmd, {
    cwd: apiDest,
    stdio: 'inherit',
  });
} catch {
  console.warn('Composer install failed. Run `composer install --no-dev` in public_html/api on the server.');
}

console.log('API copied to public_html/api (preserved existing .env and database)');
