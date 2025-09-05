#!/usr/bin/env node
/*
 Cross-platform asset minifier for Plugin assets (Windows-friendly)

 - Minifies JS with terser
 - Minifies CSS with clean-css-cli
 - Skips files already minified (*.min.js, *.min.css) and any files under a /min/ folder
*/

const { spawnSync } = require('child_process');
const { readdirSync, statSync } = require('fs');
const { join, extname, basename, dirname } = require('path');

const root = join(__dirname, '..');
const jsRoot = join(root, 'Plugin', 'assets', 'js');
const cssRoot = join(root, 'Plugin', 'assets', 'css');

let jsCount = 0;
let cssCount = 0;

function isMinified(file) {
  return file.endsWith('.min.js') || file.endsWith('.min.css');
}

function isInMinDir(filePath) {
  return filePath.split(/[\\/]/).includes('min');
}

function walk(dir, cb) {
  let entries;
  try { entries = readdirSync(dir); } catch (e) { return; }
  for (const entry of entries) {
    const full = join(dir, entry);
    let st;
    try { st = statSync(full); } catch { continue; }
    if (st.isDirectory()) {
      walk(full, cb);
    } else if (st.isFile()) {
      cb(full);
    }
  }
}

function run(cmd, args) {
  const res = spawnSync(cmd, args, { stdio: 'inherit', shell: process.platform === 'win32' });
  if (res.error) throw res.error;
  if (res.status !== 0) throw new Error(`${cmd} ${args.join(' ')} failed with code ${res.status}`);
}

console.log('Minifying JavaScript (terser)...');
walk(jsRoot, (file) => {
  if (extname(file) !== '.js') return;
  if (isMinified(file)) return;
  if (isInMinDir(file)) return;
  const out = join(dirname(file), basename(file, '.js') + '.min.js');
  console.log(`  ${file} -> ${out}`);
  run('npx', ['terser', file, '-c', '-m', '-o', out]);
  jsCount++;
});

console.log('\nMinifying CSS (clean-css-cli)...');
walk(cssRoot, (file) => {
  if (extname(file) !== '.css') return;
  if (isMinified(file)) return;
  if (isInMinDir(file)) return;
  const out = join(dirname(file), basename(file, '.css') + '.min.css');
  console.log(`  ${file} -> ${out}`);
  run('npx', ['clean-css-cli', '-o', out, file]);
  cssCount++;
});

console.log('\nSummary:');
console.log(`  Minified JS files: ${jsCount}`);
console.log(`  Minified CSS files: ${cssCount}`);

