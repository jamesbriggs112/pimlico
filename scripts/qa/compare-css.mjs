// Compares two compile-css.mjs runs by computed value: every var() is
// resolved against that side's :root (src/sass/styles.css), and numbers,
// colours and whitespace are normalised. Swapping a literal for a token that
// holds the same value is therefore no difference; anything left is a real
// change to explain.
//
//   node scripts/qa/compare-css.mjs /tmp/css-before /tmp/css-after
//
// Run from the project root.
import { createRequire } from 'node:module';
import { readFileSync, globSync, existsSync } from 'node:fs';
import { join, resolve as resolvePath } from 'node:path';

// postcss comes with Vite; the theme doesn't depend on it directly.
const themeRequire = createRequire(resolvePath('web/themes/custom/pimlico/package.json'));
const postcss = createRequire(themeRequire.resolve('vite'))('postcss');

const [before, after] = process.argv.slice(2);

const splitTop = (s, sep = ',') => {
  const out = []; let depth = 0; let cur = '';
  for (const ch of s) {
    if (ch === '(' || ch === '[') depth++;
    if (ch === ')' || ch === ']') depth--;
    if (ch === sep && depth === 0) { out.push(cur); cur = ''; } else cur += ch;
  }
  out.push(cur);
  return out.map((x) => x.trim());
};

const rootVars = (dir) => {
  const vars = {};
  const css = postcss.parse(readFileSync(join(dir, 'src/sass/styles.css'), 'utf8'));
  css.walkRules((r) => {
    if (r.parent.type !== 'root' || r.selector.trim() !== ':root') return;
    r.walkDecls((d) => { if (d.prop.startsWith('--') && d.parent === r) vars[d.prop] = d.value; });
  });
  return vars;
};

// Custom properties set on exactly one value anywhere in a file (component locals).
const localVars = (root) => {
  const seen = {};
  root.walkDecls((d) => {
    if (!d.prop.startsWith('--')) return;
    (seen[d.prop] ??= new Set()).add(d.value.trim());
  });
  return Object.fromEntries(Object.entries(seen).filter(([, v]) => v.size === 1).map(([k, v]) => [k, [...v][0]]));
};

const resolve = (value, vars, locals, depth = 0) => {
  if (depth > 20) return value;
  let out = ''; let i = 0;
  while (i < value.length) {
    const j = value.indexOf('var(', i);
    if (j < 0) { out += value.slice(i); break; }
    out += value.slice(i, j);
    let d = 0; let k = j + 3;
    for (; k < value.length; k++) { if (value[k] === '(') d++; if (value[k] === ')') { d--; if (d === 0) break; } }
    const inner = value.slice(j + 4, k);
    const [name, ...fb] = splitTop(inner);
    const fallback = fb.length ? fb.join(',') : null;
    const v = vars[name] ?? locals[name];
    if (v !== undefined) out += resolve(v, vars, locals, depth + 1);
    else if (fallback !== null) out += resolve(fallback, vars, locals, depth + 1);
    else out += `var(${name})`;
    i = k + 1;
  }
  return out;
};

const num = (n) => String(Math.round(parseFloat(n) * 10000) / 10000);
const hexRgb = (h) => {
  h = h.slice(1);
  if (h.length === 3) h = [...h].map((c) => c + c).join('');
  return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16));
};
const colour = (c) => {
  c = c.trim();
  if (/^#[0-9a-f]{3}([0-9a-f]{3})?$/.test(c)) return [...hexRgb(c), 1];
  let m = c.match(/^rgba?\(([\d.]+)[ ,]+([\d.]+)[ ,]+([\d.]+)(?:\s*[/,]\s*([\d.]+)(%?))?\)$/);
  if (m) return [+m[1], +m[2], +m[3], m[4] === undefined ? 1 : m[5] ? m[4] / 100 : +m[4]];
  if (c === 'white') return [255, 255, 255, 1];
  if (c === 'black') return [0, 0, 0, 1];
  return null;
};
const fmt = ([r, g, b, a]) => `rgba(${num(r)},${num(g)},${num(b)},${num(a)})`;

const normalise = (v) => {
  v = v.replace(/\s+/g, ' ').replace(/\/\*.*?\*\//g, '').trim().toLowerCase();
  // color-mix(in srgb, X p%, transparent)
  v = v.replace(/color-mix\(\s*in srgb\s*,\s*([^,]+?)\s+([\d.]+)%\s*,\s*transparent\s*\)/g, (all, c, p) => {
    const rgb = colour(c);
    return rgb ? fmt([rgb[0], rgb[1], rgb[2], rgb[3] * p / 100]) : all;
  });
  v = v.replace(/#[0-9a-f]{3,6}\b|rgba?\([^)]*\)/g, (c) => { const rgb = colour(c); return rgb ? fmt(rgb) : c; });
  v = v.replace(/(^|[^\w.-])(-?\d*\.?\d+)(?=[a-z%]*)/g, (all, pre, n) => pre + num(n));
  v = v.replace(/\b0(px|rem|em|ms)\b/g, '0').replace(/(\d)ms\b/g, (a, d) => a);
  v = v.replace(/\s*([,()/])\s*/g, '$1');
  return v;
};

const collect = (file, dir) => {
  const map = new Map();
  if (!existsSync(file)) return map;
  const root = postcss.parse(readFileSync(file, 'utf8'));
  const vars = rootVars(dir);
  const locals = localVars(root);
  root.walkRules((r) => {
    const ctx = [];
    for (let p = r.parent; p && p.type !== 'root'; p = p.parent) if (p.type === 'atrule') ctx.unshift(`@${p.name} ${p.params.replace(/\s+/g, ' ')}`);
    for (const sel of splitTop(r.selector)) {
      const key = [...ctx, sel.replace(/\s+/g, ' ')].join(' | ');
      const decls = map.get(key) ?? {};
      r.each((d) => {
        if (d.type !== 'decl') return;
        decls[d.prop] = { raw: d.value, val: normalise(resolve(d.value, vars, locals)) + (d.important ? ' !important' : '') };
      });
      map.set(key, decls);
    }
  });
  return map;
};

const files = new Set([...globSync('**/*.css', { cwd: before }), ...globSync('**/*.css', { cwd: after })]);
let count = 0; const customs = { removed: [], added: [], changed: [] };
for (const f of [...files].sort()) {
  const b = collect(join(before, f), before);
  const a = collect(join(after, f), after);
  if (!existsSync(join(after, f))) { console.log(`\n## ${f}: file removed`); count++; continue; }
  if (!existsSync(join(before, f))) { console.log(`\n## ${f}: file added`); count++; continue; }
  const out = [];
  for (const key of new Set([...b.keys(), ...a.keys()])) {
    const db = b.get(key) ?? {}; const da = a.get(key) ?? {};
    for (const prop of new Set([...Object.keys(db), ...Object.keys(da)])) {
      const x = db[prop]?.val; const y = da[prop]?.val;
      if (x === y) continue;
      if (prop.startsWith('--') && key === ':root') {
        if (x === undefined) customs.added.push(prop); else if (y === undefined) customs.removed.push(prop); else customs.changed.push(`${prop}: ${x} -> ${y}`);
        continue;
      }
      out.push(`  ${key}\n    ${prop}: ${x ?? '(none)'}  ->  ${y ?? '(none)'}`);
    }
  }
  if (out.length) { console.log(`\n## ${f}`); console.log(out.join('\n')); count += out.length; }
}
console.log(`\n## :root custom properties\n  removed (${customs.removed.length}): ${customs.removed.join(' ')}\n  added (${customs.added.length}): ${customs.added.join(' ')}\n  changed:\n    ${customs.changed.join('\n    ')}`);
console.log(`\n${count} declaration differences (excluding :root custom properties)`);
