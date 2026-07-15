const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const catalogPath = path.join(root, 'json', 'wallpapers.json');
const catalog = JSON.parse(fs.readFileSync(catalogPath, 'utf8'));
const errors = [];

for (const wallpaper of catalog.wallpapers || []) {
  const file = String(wallpaper.file || '');
  if (!/\.jpe?g$/i.test(file)) {
    errors.push(`Wallpaper ${wallpaper.id || '(unknown)'} is not JPG: ${file}`);
    continue;
  }

  const assetPath = path.join(root, 'skin', 'img', file);
  if (!fs.existsSync(assetPath)) {
    errors.push(`Wallpaper ${wallpaper.id || '(unknown)'} is missing: ${file}`);
    continue;
  }

  const signature = fs.readFileSync(assetPath).subarray(0, 3);
  if (!(signature[0] === 0xff && signature[1] === 0xd8 && signature[2] === 0xff)) {
    errors.push(`Wallpaper ${wallpaper.id || '(unknown)'} has a non-JPEG file signature: ${file}`);
  }
}

if (errors.length) {
  console.error(errors.join('\n'));
  process.exit(1);
}

console.log(`Validated ${catalog.wallpapers.length} catalog images: all are JPG files.`);
