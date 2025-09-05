#!/usr/bin/env node
/*
 Cross-platform asset minifier for Plugin assets (Windows-friendly)

 - Minifies JS with terser
 - Minifies CSS with clean-css-cli
 - Skips files already minified (*.min.js, *.min.css) and any files under a /min/ folder
*/

const { spawnSync } = require('child_process');
const { readdirSync, statSync, existsSync, mkdirSync, readFileSync, writeFileSync } = require('fs');
const { join, extname, basename, dirname } = require('path');

const root = join(__dirname, '..');
const jsRoot = join(root, 'Plugin', 'assets', 'js');
const cssRoot = join(root, 'Plugin', 'assets', 'css');
const bundleRoot = join(root, 'Plugin', 'assets', 'bundles');

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

// ------------------------------------------------------------
// Bundling phase: concatenate key assets into a few bundles
// ------------------------------------------------------------

function ensureDir(dir) {
  if (!existsSync(dir)) {
    mkdirSync(dir, { recursive: true });
  }
}

function concatFiles(outFile, files) {
  const contents = [];
  for (const f of files) {
    if (!existsSync(f)) {
      console.warn(`[bundle] Skip missing: ${f}`);
      continue;
    }
    contents.push(readFileSync(f, 'utf8'));
  }
  writeFileSync(outFile, contents.join('\n;\n'));
  console.log(`  bundle -> ${outFile}`);
}

console.log('\nCreating bundles...');
ensureDir(bundleRoot);

// Admin JS bundle (5–10 files → 1)
const adminJsBundle = join(bundleRoot, 'mt-admin.bundle.min.js');
const adminJsFiles = [
  join(jsRoot, 'mt-admin.min.js'),
  join(jsRoot, 'mt-evaluations-admin.min.js'),
  join(jsRoot, 'mt-assignments.min.js'),
  join(jsRoot, 'mt-settings-admin.min.js'),
  join(jsRoot, 'mt-debug-center.min.js'),
  join(jsRoot, 'mt-modal-debug.min.js'),
].filter(Boolean);
concatFiles(adminJsBundle, adminJsFiles);

// Admin CSS bundle
const adminCssBundle = join(bundleRoot, 'mt-admin.bundle.min.css');
const adminCssFiles = [
  join(cssRoot, 'admin.min.css'),
  join(cssRoot, 'mt-evaluations-admin.min.css'),
  join(cssRoot, 'mt-modal-fix.min.css'),
].filter(Boolean);
concatFiles(adminCssBundle, adminCssFiles);

// Frontend v4 CSS bundle
const v4Dir = join(cssRoot, 'v4');
const v4Bundle = join(v4Dir, 'mt-v4.bundle.min.css');
const v4CssFiles = [
  join(v4Dir, 'mt-tokens.min.css'),
  join(v4Dir, 'mt-reset.min.css'),
  join(v4Dir, 'mt-base.min.css'),
  join(v4Dir, 'mt-components.min.css'),
  join(v4Dir, 'mt-pages.min.css'),
].filter(Boolean);
concatFiles(v4Bundle, v4CssFiles);

console.log('\nBundles created successfully.');
