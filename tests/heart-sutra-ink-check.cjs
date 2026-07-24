const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const catalog = JSON.parse(fs.readFileSync(path.join(root, 'json', 'wallpapers.json'), 'utf8'));
const items = catalog.wallpapers.filter((item) => item.topics?.includes('heart-sutra'));

function jpegSize(buffer) {
  if (buffer[0] !== 0xff || buffer[1] !== 0xd8) {
    throw new Error('不是有效的 JPG');
  }

  let offset = 2;
  while (offset + 8 < buffer.length) {
    if (buffer[offset] !== 0xff) {
      offset += 1;
      continue;
    }

    const marker = buffer[offset + 1];
    offset += 2;
    if (marker === 0xd8 || marker === 0xd9) continue;
    const length = buffer.readUInt16BE(offset);
    const isStartOfFrame = marker >= 0xc0 && marker <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(marker);
    if (isStartOfFrame) {
      return {
        height: buffer.readUInt16BE(offset + 3),
        width: buffer.readUInt16BE(offset + 5),
      };
    }
    offset += length;
  }

  throw new Error('找不到 JPG 尺寸資料');
}

const expectedTitles = [
  '墨境觀心', '笑看塵機', '照見五蘊', '心無罣礙', '掃心見性',
  '靜念合掌', '放下自在', '不生不滅', '明心見性', '即心即佛',
  '墨掃千山', '返照心源', '智慧雲開', '雲海觀心', '行深萬山',
  '指月觀心', '一念破雲', '雙峰開悟', '靜念圓融', '無聲雲海',
  '無畏登峰', '普門照見', '明心自在', '迴流見性', '無生雲巔',
];

if (items.length !== 25) {
  throw new Error(`心經水墨桌布應有 25 張，目前為 ${items.length} 張`);
}

items.forEach((item, index) => {
  const number = String(index + 1).padStart(2, '0');
  const expectedFile = `assets/desktop/heart-sutra-ink-${number}.jpg`;
  const expectedId = String(65 + index);

  if (item.id !== expectedId) throw new Error(`${expectedFile} 的 id 應為 ${expectedId}`);
  if (item.title !== expectedTitles[index]) throw new Error(`${expectedFile} 的標題不正確`);
  if (item.file !== expectedFile) throw new Error(`第 ${number} 張檔案路徑不正確`);
  if (item.content_type !== 'real' || !item.labels.includes('真人')) {
    throw new Error(`${expectedFile} 未正確歸入真人類別`);
  }
  if (JSON.stringify(item.colors) !== JSON.stringify(['black', 'white'])) {
    throw new Error(`${expectedFile} 色系必須恰為 black、white`);
  }
  if (item.device !== 'pc' || item.orientation !== 'landscape' || item.width !== 1920 || item.height !== 1080) {
    throw new Error(`${expectedFile} 桌布資料必須為 PC 橫向 1920x1080`);
  }

  const diskPath = path.join(root, 'skin', 'img', item.file);
  if (!fs.existsSync(diskPath)) throw new Error(`缺少圖檔：${diskPath}`);
  const size = jpegSize(fs.readFileSync(diskPath));
  if (size.width !== 1920 || size.height !== 1080) {
    throw new Error(`${expectedFile} 實際尺寸為 ${size.width}x${size.height}`);
  }
});

console.log('Validated 25 Heart Sutra ink wallpapers: 真人, black + white, 1920x1080 JPG.');
