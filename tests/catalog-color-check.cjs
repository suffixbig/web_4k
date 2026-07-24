const fs = require("node:fs");
const path = require("node:path");

const root = path.resolve(__dirname, "..");
const catalog = JSON.parse(fs.readFileSync(path.join(root, "json", "wallpapers.json"), "utf8"));
const allowedColors = new Set(["red", "orange", "yellow", "green", "blue", "purple", "black", "white", "brown"]);
const counts = Object.fromEntries([...allowedColors].map((color) => [color, 0]));
const errors = [];

for (const wallpaper of catalog.wallpapers || []) {
  const colors = wallpaper.colors;
  if (!Array.isArray(colors) || colors.length < 1 || colors.length > 2) {
    errors.push(`Wallpaper ${wallpaper.id || "(unknown)"} must have one or two colors.`);
    continue;
  }
  if (new Set(colors).size !== colors.length) {
    errors.push(`Wallpaper ${wallpaper.id} has duplicate colors: ${colors.join(", ")}`);
  }
  for (const color of colors) {
    if (!allowedColors.has(color)) errors.push(`Wallpaper ${wallpaper.id} has unsupported color: ${color}`);
    else counts[color] += 1;
  }
}

for (const [color, count] of Object.entries(counts)) {
  if (count === 0) errors.push(`Supported color ${color} is not assigned to any wallpaper.`);
}

if (errors.length) {
  console.error(errors.join("\n"));
  process.exit(1);
}

console.log(`Validated ${catalog.wallpapers.length} wallpaper color records: one or two supported colors each.`);
console.log(JSON.stringify(counts));
