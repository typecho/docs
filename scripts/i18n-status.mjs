#!/usr/bin/env node
// Translation status report (OUTLINE.md §5.5).
//
// For every page under docs/**, checks the mirrored English file under
// i18n/en/docusaurus-plugin-content-docs/current/ and prints a Markdown table:
//
//   missing  - no English file
//   machine  - English file has front matter `translation: machine`
//   reviewed - English file exists without that flag
//   stale    - English file's `source_commit` is older than the last change to
//              the Chinese source (`git log <source_commit>..HEAD -- docs/<path>`
//              is non-empty), or a reviewed page has no `source_commit` at all
//
// Usage: node scripts/i18n-status.mjs   (or: pnpm i18n:status)

import {execFileSync} from 'node:child_process';
import {existsSync, readdirSync, readFileSync} from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const siteDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const docsDir = path.join(siteDir, 'docs');
const enDir = path.join(
  siteDir,
  'i18n/en/docusaurus-plugin-content-docs/current',
);

function listPages(dir, base = '') {
  const pages = [];
  for (const entry of readdirSync(dir, {withFileTypes: true})) {
    const rel = path.posix.join(base, entry.name);
    if (entry.name.startsWith('_')) {
      continue; // Docusaurus excludes _-prefixed files and dirs by default
    }
    if (entry.isDirectory()) {
      pages.push(...listPages(path.join(dir, entry.name), rel));
    } else if (/\.mdx?$/.test(entry.name)) {
      pages.push(rel);
    }
  }
  return pages.sort();
}

// Reads top-level `key: value` pairs from a YAML front matter block. Enough for
// the flat keys this script needs (translation, source_commit).
function readFrontMatter(file) {
  const match = readFileSync(file, 'utf8').match(
    /^---\r?\n([\s\S]*?)\r?\n---(?:\r?\n|$)/,
  );
  const data = {};
  if (!match) {
    return data;
  }
  for (const line of match[1].split(/\r?\n/)) {
    const kv = line.match(/^([A-Za-z_][\w-]*):\s*(.*?)\s*$/);
    if (kv) {
      data[kv[1]] = kv[2].replace(/^(['"])(.*)\1$/, '$2');
    }
  }
  return data;
}

function git(...args) {
  return execFileSync('git', args, {
    cwd: siteDir,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'ignore'],
  }).trim();
}

function staleStatus(page, sourceCommit, reviewed) {
  if (!sourceCommit) {
    return reviewed ? 'yes (no source_commit)' : '';
  }
  try {
    git('rev-parse', '--verify', '--quiet', `${sourceCommit}^{commit}`);
  } catch {
    return `unknown (bad source_commit ${sourceCommit})`;
  }
  const log = git('log', '--oneline', `${sourceCommit}..HEAD`, '--', `docs/${page}`);
  const n = log ? log.split('\n').length : 0;
  return n ? `yes (${n} commit${n === 1 ? '' : 's'})` : '';
}

const rows = [];
const counts = {missing: 0, machine: 0, reviewed: 0, stale: 0};

for (const page of listPages(docsDir)) {
  const enFile = path.join(enDir, page);
  if (!existsSync(enFile)) {
    counts.missing++;
    rows.push([page, 'x', '', '', '']);
    continue;
  }
  const fm = readFrontMatter(enFile);
  const machine = fm.translation === 'machine';
  const stale = staleStatus(page, fm.source_commit, !machine);
  counts[machine ? 'machine' : 'reviewed']++;
  if (stale) {
    counts.stale++;
  }
  rows.push([page, '', machine ? 'x' : '', machine ? '' : 'x', stale]);
}

const total = rows.length;
const coverage = total ? ((counts.reviewed / total) * 100).toFixed(1) : '0.0';

console.log('| path | missing | machine | reviewed | stale |');
console.log('|---|:-:|:-:|:-:|---|');
for (const row of rows) {
  console.log(`| ${row.join(' | ')} |`);
}
console.log();
console.log(
  `**${total} pages**: ${counts.reviewed} reviewed, ${counts.machine} machine, ` +
    `${counts.missing} missing, ${counts.stale} stale. ` +
    `Reviewed coverage: **${coverage}%**.`,
);
