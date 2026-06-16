import { mkdirSync, writeFileSync, existsSync, copyFileSync, readdirSync, statSync } from 'fs';
import { join, dirname, relative } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '../public_html/images');

const OLDBCK_UPLOADS =
  'C:/Users/rahul/Downloads/positivetree-ngo/oldbck/wp-content/uploads';

const REMOTE_IMAGES = [
  '2023/07/logo-tree.png',
  '2024/01/logo-line.png',
  '2024/01/favicon.png',
  '2023/07/banner1-scaled.jpg',
  '2023/07/old-age-programmes.jpg',
  '2023/07/about.png',
  '2023/07/education.webp',
  '2023/07/oldage-home.jpg',
  '2023/07/eyecare.jpg',
  '2024/01/healthcare.jpg',
  '2024/01/empowerment.jpg',
  '2024/01/food-donation.jpg',
  '2024/01/temple-funding.jpg',
  '2024/01/mission.png',
  '2024/01/vision.png',
  '2024/01/ngo.jpg',
];

function copyTree(src, dest) {
  let count = 0;
  for (const entry of readdirSync(src)) {
    const srcPath = join(src, entry);
    const destPath = join(dest, entry);
    if (statSync(srcPath).isDirectory()) {
      mkdirSync(destPath, { recursive: true });
      count += copyTree(srcPath, destPath);
    } else if (/\.(jpg|jpeg|png|webp)$/i.test(entry)) {
      copyFileSync(srcPath, destPath);
      count++;
    }
  }
  return count;
}

function copyFromOldbck(relativePath) {
  const src = join(OLDBCK_UPLOADS, relativePath);
  const dest = join(outDir, relativePath);
  if (!existsSync(src)) return false;
  mkdirSync(dirname(dest), { recursive: true });
  copyFileSync(src, dest);
  console.log(`local ${relativePath}`);
  return true;
}

async function download(relativePath) {
  const dest = join(outDir, relativePath);
  if (existsSync(dest)) {
    console.log(`skip ${relativePath}`);
    return true;
  }
  if (copyFromOldbck(relativePath)) return true;

  const url = `https://positivetree.ngo/wp-content/uploads/${relativePath}`;
  mkdirSync(dirname(dest), { recursive: true });
  try {
    const res = await fetch(url);
    if (!res.ok) {
      console.warn(`fail ${relativePath}: ${res.status}`);
      return false;
    }
    writeFileSync(dest, Buffer.from(await res.arrayBuffer()));
    console.log(`remote ${relativePath}`);
    return true;
  } catch (err) {
    console.warn(`err  ${relativePath}: ${err.message}`);
    return false;
  }
}

mkdirSync(outDir, { recursive: true });

if (existsSync(OLDBCK_UPLOADS)) {
  const copied = copyTree(OLDBCK_UPLOADS, outDir);
  console.log(`Copied ${copied} images from WordPress backup (oldbck/)`);
} else {
  console.warn('oldbck uploads not found — trying remote download');
  const results = await Promise.all(REMOTE_IMAGES.map(download));
  console.log(`Downloaded ${results.filter(Boolean).length}/${REMOTE_IMAGES.length}`);
}
