/*
 * <pimlico-map> — bird's-eye vector map of Pimlico Road.
 *
 * One dependency-free web component for both uses:
 *   Overview:   <pimlico-map src="pimlico-map.json" brands="brands.json"></pimlico-map>
 *   Brand page: <pimlico-map src="…" brands="…" mode="brand" brand="jamb"></pimlico-map>
 *
 * Attributes
 *   src        base geometry (from scripts/export_map.js). Static; cache it long.
 *   brands     tenancy layer URL (Drupal endpoint). Or put the JSON inline in a
 *              child <script type="application/json"> and omit this attribute.
 *   mode       "overview" (default: whole map) | "brand" (zoomed to one brand)
 *   brand      brand id/slug to focus in brand mode
 *   category   show only this category's tenancies in yellow ("" = all)
 *   radius     brand mode: metres of context around the brand (default 110)
 *   margin     overview: metres of context around the tenancies (default 70)
 *   locate     show a "you are here" button (needs HTTPS)
 *   no-card    don't open the built-in card; listen for `pimlico-map:select` instead
 *   static     no pan/zoom (e.g. a small brand-page map)
 *   no-labels  hide the small shop names on tenancy buildings (shown by default;
 *              names that would overlap drop out, more appear as you zoom in)
 *   pick       editor mode: every building is selectable and `pimlico-map:select`
 *              reports its id (for a Drupal field widget that sets a unit's building)
 *
 * Events (bubble, composed)
 *   pimlico-map:select  detail: { brands: [...], buildingId }
 *   pimlico-map:ready   detail: { categories: [...] }
 *
 * Property
 *   categories  active brand categories (same list as pimlico-map:ready)
 *
 *   no-fullscreen  hide the full-screen button
 *   no-search  hide the brand search box (shown on the overview only; never on a
 *              brand page, in pick mode or with static)
 *
 * Full screen: the corner button fills the screen (the browser's Fullscreen API,
 * or a page-filling overlay on iPhone, which only allows video full screen).
 * While full screen, one finger pans and plain scrolling zooms.
 *
 * Keyboard: Tab reaches tenancy buildings (left to right), Enter opens them;
 * arrow keys pan, +/− zoom, Home or 0 resets the view, Escape closes the card
 * (then leaves full screen).
 *
 * Theme with CSS custom properties on the element, e.g.
 *   pimlico-map { --pm-ground: #E0DCCE; --pm-active: #F9E577; height: 600px; }
 * and parts: ::part(svg), ::part(controls), ::part(search), ::part(card), ::part(pin-label), ::part(attribution).
 */
(() => {
  'use strict';

  const SVG = 'http://www.w3.org/2000/svg';
  const MAX_ZOOM = 8;            // relative to the fitted view
  const LABEL_PX = 11;           // street label size on screen
  const LABEL_CHAR_EM = 0.78;    // street labels: bold capitals with 0.08em spacing
  const SHOP_LABEL_PX = 10;      // shop name size on screen
  const SHOP_CHAR_EM = 0.58;     // average glyph width, for overlap checks without layout
  const cache = new Map();       // url → promise of JSON (shared by every map on the page)
  let sheet = null;              // the component's constructed stylesheet, shared

  const load = (url) => {
    if (!cache.has(url)) {
      cache.set(url, fetch(url, { credentials: 'same-origin' }).then((r) => {
        if (!r.ok) throw new Error(`${url}: ${r.status}`);
        return r.json();
      }).catch((err) => { cache.delete(url); throw err; })); // don't keep a failure
    }
    return cache.get(url);
  };
  const el = (tag, attrs = {}, parent) => {
    const n = document.createElementNS(SVG, tag);
    for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
    if (parent) parent.appendChild(n);
    return n;
  };
  // Generic train symbol for station markers (not the TfL roundel, which is a trademark),
  // in px around the station point: body with rounded corners, two lights, two legs.
  const TRAIN = 'M-3.5 -5h7a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1 -1.5 1.5h-7a1.5 1.5 0 0 1 -1.5 -1.5v-6a1.5 1.5 0 0 1 1.5 -1.5z'
    + 'M-2.5 4.2l-1.6 2.3M2.5 4.2l1.6 2.3';
  // Rectangle centred on (cx, cy), half-sizes hw × hh, rotated by `a` radians → corners.
  const rotRect = (cx, cy, hw, hh, a) => {
    const c = Math.cos(a), s = Math.sin(a);
    return [[-hw, -hh], [hw, -hh], [hw, hh], [-hw, hh]].map(([x, y]) => [cx + x * c - y * s, cy + x * s + y * c]);
  };
  // Do two convex quads overlap? (Separating-axis test.)
  const overlaps = (P, Q) => ![P, Q].some((R) => R.some((p, i) => {
    const q = R[(i + 1) % R.length], ax = p[1] - q[1], ay = q[0] - p[0];
    const a = P.map(([x, y]) => x * ax + y * ay), b = Q.map(([x, y]) => x * ax + y * ay);
    return Math.max(...a) < Math.min(...b) || Math.max(...b) < Math.min(...a);
  }));
  const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);

  const CSS = `
    :host {
      /* Palette from the Figma designs (Pimlico Visuals – V01, 03 Brand with Map). */
      --pm-ground: #E0DCCE;
      --pm-road: #ECE9DF;          /* tint/linen-60 */
      --pm-road-stroke: #BABABA;   /* 1px line just inside the road edge */
      --pm-green: #CDD6BE;
      --pm-tree: #A7B993;
      --pm-building: #FFFFFF;      /* brand/white */
      --pm-building-stroke: #CDC7B7;
      --pm-shadow: rgba(57, 73, 89, 0.10);
      --pm-active: #F9E577;        /* brand/yellow */
      --pm-active-stroke: #DCC24C;
      --pm-nearby: #FFF8D2;        /* brand page: the other tenancies, so the brand stands out */
      --pm-ink: #394959;           /* brand/dark-grey */
      --pm-label: #394959;
      --pm-shop-label: #394959;
      --pm-shop-halo: rgba(255, 255, 255, 0.85);
      --pm-card-bg: #FFFFFF;
      display: block; position: relative; overflow: hidden;
      height: 520px; background: var(--pm-ground); color: var(--pm-ink);
      font: inherit; contain: content;
    }
    :host([hidden]) { display: none; }
    svg.map { position: absolute; inset: 0; width: 100%; height: 100%; display: block; touch-action: pan-y; user-select: none; -webkit-user-select: none; }
    :host([static]) svg.map { touch-action: auto; }
    .ground { fill: var(--pm-ground); }
    .roads { fill: var(--pm-road); }
    /* SVG has no inside strokes: a 2px line clipped to the road shape leaves 1px inside it. */
    .road-edge { fill: none; stroke: var(--pm-road-stroke); stroke-width: 2px; vector-effect: non-scaling-stroke; }
    .green { fill: var(--pm-green); }
    .tree { fill: var(--pm-tree); }
    .shadow { fill: var(--pm-shadow); }
    .bldg { fill: var(--pm-building); stroke: var(--pm-building-stroke); vector-effect: non-scaling-stroke; stroke-width: 0.75px; stroke-linejoin: round; }
    .bldg.active { fill: var(--pm-active); stroke: var(--pm-active-stroke); cursor: pointer; transition: fill 0.2s; }
    .bldg.active.muted { fill: var(--pm-building); stroke: var(--pm-building-stroke); cursor: default; }
    .bldg.active:hover, .bldg.active:focus-visible, .bldg.selected { stroke: var(--pm-ink); stroke-width: 1.5px; outline: none; }
    /* No browser focus ring when the map is clicked (clicking focuses it so the arrow
       keys can pan it). Keyboard focus on a building shows the map's own navy outline. */
    svg.map:focus, .bldg:focus { outline: none; }
    .bldg:focus-visible { stroke: var(--pm-ink); stroke-width: 1.5px; }
    :host([pick]) .bldg { cursor: crosshair; }
    :host([pick]) .bldg:hover { stroke: var(--pm-ink); stroke-width: 1.5px; }
    /* Brand page: the brand's building in full yellow, the other tenancies paler. */
    .focused .bldg.active:not(.focus):not(.muted) { fill: var(--pm-nearby); }
    .bldg.focus { fill: var(--pm-active); stroke: none; }
    .label { fill: var(--pm-label); font-family: inherit; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; pointer-events: none;
             paint-order: stroke; stroke: var(--pm-road); stroke-width: 3px; stroke-linejoin: round; }
    .label.place { fill: var(--pm-ink); stroke: var(--pm-shop-halo); }
    .shop { fill: var(--pm-shop-label); font-family: inherit; font-weight: 600; letter-spacing: 0.01em; pointer-events: none;
            paint-order: stroke; stroke: var(--pm-shop-halo); stroke-linejoin: round; }
    :host([no-labels]) .shops { display: none; }
    .station circle { fill: var(--pm-ink); }
    .station .glyph { fill: #fff; stroke: #fff; stroke-width: 1.2; stroke-linecap: round; }
    .station .window { fill: var(--pm-ink); }
    .station text { fill: var(--pm-ink); font-family: inherit; paint-order: stroke; stroke: var(--pm-shop-halo); stroke-width: 3px; stroke-linejoin: round; pointer-events: none; }
    .station .name { font-size: 11px; font-weight: 700; }
    .station .lines { font-size: 10px; }
    /* Full screen: the browser's own, or (iPhone, which only allows video) the page-filling fallback. */
    :host([data-fullscreen]) { position: fixed !important; inset: 0 !important; width: auto !important; height: auto !important;
                               z-index: 2147483000; margin: 0 !important; max-width: none !important; }
    .ctl .exit, :host([data-fullscreen]) .ctl .enter { display: none; }
    :host([data-fullscreen]) .ctl .exit { display: block; }
    :host([no-fullscreen]) [data-act="full"] { display: none; }
    .me .dot { fill: #1A73E8; stroke: #fff; vector-effect: non-scaling-stroke; stroke-width: 3px; }
    .me .acc { fill: rgba(26,115,232,0.10); stroke: rgba(26,115,232,0.4); vector-effect: non-scaling-stroke; stroke-width: 1px; }
    .pin-label {
      position: absolute; transform: translate(-50%, calc(-100% - 7px)); pointer-events: none; /* 7px above the building */
      background: var(--pm-ink); color: #fff; padding: 6px 10px; font-size: 13px; line-height: 1.2; white-space: nowrap;
    }
    .controls { position: absolute; right: 12px; bottom: 28px; display: flex; flex-direction: column; gap: 6px; }
    :host([static]) .controls { display: none; }
    button { font: inherit; color: var(--pm-ink); }
    .ctl {
      width: 36px; height: 36px; border: 0; background: #fff; cursor: pointer; display: grid; place-items: center;
      box-shadow: 0 1px 3px rgba(57,73,89,0.25); font-size: 18px; line-height: 1;
    }
    .ctl:focus-visible, .card a:focus-visible, .card button:focus-visible { outline: 2px solid var(--pm-ink); outline-offset: 2px; }
    .tip {
      position: absolute; pointer-events: none; transform: translate(-50%, calc(-100% - 12px));
      background: var(--pm-ink); color: #fff; padding: 5px 9px; font-size: 13px; white-space: nowrap;
    }
    .hint {
      position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none;
      background: rgba(57,73,89,0.45); color: #fff; font-size: 15px; opacity: 0; transition: opacity 0.25s;
    }
    .hint.show { opacity: 1; }
    .msg { position: absolute; left: 50%; top: 12px; transform: translateX(-50%); background: var(--pm-ink); color: #fff; padding: 6px 12px; font-size: 13px; }
    .card {
      position: absolute; left: 12px; bottom: 28px; width: min(320px, calc(100% - 24px)); background: var(--pm-card-bg);
      box-shadow: 0 6px 24px rgba(57,73,89,0.25); padding: 18px 20px 20px; color: var(--pm-ink);
    }
    .card .close { position: absolute; right: 6px; top: 4px; border: 0; background: none; font-size: 22px; cursor: pointer; padding: 4px 8px; }
    .card .cat { margin: 0 0 6px; font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; }
    .card h3 { margin: 0 0 6px; font-weight: 400; font-size: 22px; line-height: 1.2; padding-right: 20px; }
    .card p { margin: 0 0 12px; font-size: 14px; opacity: 0.8; }
    .card ul { list-style: none; margin: 0 0 12px; padding: 0; }
    .card li { border-top: 1px solid rgba(57,73,89,0.15); padding: 8px 0; }
    .card li:first-child { border-top: 0; }
    .card a { color: var(--pm-ink); }
    .card .more { display: inline-block; font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; text-decoration: none; border-bottom: 1px solid currentColor; padding-bottom: 2px; }
    .attr { position: absolute; right: 0; bottom: 0; font-size: 10px; padding: 2px 6px; background: rgba(255,255,255,0.75); }
    .attr a { color: inherit; }
    /* Brand search (overview only): a type-ahead box in the top-left corner. */
    .search { position: absolute; left: 12px; top: 12px; width: min(300px, calc(100% - 24px)); background: #fff;
              box-shadow: 0 1px 3px rgba(57,73,89,0.25); color: var(--pm-ink); }
    .search input { box-sizing: border-box; width: 100%; height: 36px; margin: 0; border: 0; border-radius: 0; padding: 0 12px 0 36px;
                    background: transparent; font: inherit; font-size: 14px; color: inherit; -webkit-appearance: none; appearance: none; }
    .search input::placeholder { color: inherit; opacity: 0.65; }
    .search input::-webkit-search-cancel-button { cursor: pointer; }
    .search input:focus-visible { outline: 2px solid var(--pm-ink); outline-offset: 2px; }
    .search .mag { position: absolute; left: 11px; top: 9px; pointer-events: none; }
    .search ul { list-style: none; margin: 0; padding: 4px 0; max-height: 264px; overflow: auto; border-top: 1px solid rgba(57,73,89,0.15); }
    .search li { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding: 9px 12px; font-size: 14px; cursor: pointer; }
    .search li span:first-child { min-width: 0; }
    .search li .cat { font-size: 11px; letter-spacing: 0.14em; text-transform: uppercase; opacity: 0.7; white-space: nowrap; }
    .search li[aria-selected="true"], .search li:not(.none):hover { background: rgba(57,73,89,0.07); }
    .search li.none { cursor: default; opacity: 0.7; }
    [hidden] { display: none !important; }
    /* Touch screens: 44px targets. */
    @media (pointer: coarse) {
      .ctl { width: 44px; height: 44px; }
      .controls { gap: 8px; }
      .card .close { width: 44px; height: 44px; right: 0; top: 0; }
      .search input { height: 44px; font-size: 16px; } /* 16px stops iOS zooming the page on focus */
      .search .mag { top: 13px; }
      .search li { padding: 12px; }
    }
  `;

  class PimlicoMap extends HTMLElement {
    static get observedAttributes() { return ['mode', 'brand', 'category', 'src', 'brands', 'radius', 'margin', 'locate', 'pick', 'no-labels', 'no-search', 'static']; }

    constructor() {
      super();
      this.root = this.attachShadow({ mode: 'open' });
      this.vb = null;          // current viewBox [x, y, w, h]
      this.home = null;        // fitted viewBox to return to
      this.ready = false;
      this.loadToken = 0;      // drops results of superseded loads
      this.focusInfo = null;   // brand mode: { brand, c }
    }

    connectedCallback() {
      if (!this.svg) {
        this.build();
        this.init();
      }
      this.ro.observe(this);
      for (const ev of ['fullscreenchange', 'webkitfullscreenchange']) document.addEventListener(ev, this.onFsChange);
    }

    disconnectedCallback() {
      this.ro.disconnect();
      for (const ev of ['fullscreenchange', 'webkitfullscreenchange']) document.removeEventListener(ev, this.onFsChange);
      this.stopLocate();
      if (this.isFull) this.exitFullscreen();
    }

    // ---------------------------------------------------------------- full screen

    get isFull() { return this.hasAttribute('data-fullscreen'); }

    toggleFullscreen() { if (this.isFull) this.exitFullscreen(); else this.enterFullscreen(); }

    enterFullscreen() {
      const req = this.requestFullscreen || this.webkitRequestFullscreen;
      // The CSS fallback fills the window straight away and the page stops
      // scrolling behind it; the browser's full screen goes on top where allowed.
      this.setFull(true);
      this.prevOverflow = document.documentElement.style.overflow;
      document.documentElement.style.overflow = 'hidden';
      if (req) try { Promise.resolve(req.call(this)).catch(() => {}); } catch { /* fallback stays */ }
    }

    exitFullscreen() {
      const doc = document, cur = doc.fullscreenElement || doc.webkitFullscreenElement;
      this.nativeFs = false;
      if (cur === this) (doc.exitFullscreen || doc.webkitExitFullscreen).call(doc);
      this.setFull(false);
    }

    setFull(on) {
      if (on === this.isFull) return;
      this.toggleAttribute('data-fullscreen', on);
      // A toggle button: fixed name, state in aria-pressed (screen readers say "Full screen, pressed").
      this.root.querySelector('[data-act="full"]').setAttribute('aria-pressed', String(on));
      if (!on && this.prevOverflow !== undefined) { document.documentElement.style.overflow = this.prevOverflow; this.prevOverflow = undefined; }
      // Re-fit after the size change, so leaving full screen also resets the view.
      requestAnimationFrame(() => this.fit(true));
    }

    attributeChangedCallback(name, oldV, newV) {
      if (oldV === newV || !this.svg) return;
      if (name === 'src' || name === 'brands') return void this.init();
      if (name === 'locate') {
        this.root.querySelector('[data-act="locate"]').hidden = newV === null;
        if (newV === null) this.stopLocate();
        return;
      }
      if (!this.ready) return;
      if (name === 'no-labels') return void this.placeShopLabels();
      if (name === 'no-search' || name === 'static') return void this.updateSearch();
      if (name === 'category' || name === 'pick') { this.closeCard(); this.applyCategory(); this.updateSearch(); }
      else { this.applyFocus(); this.updateSearch(); this.fit(true); } // mode, brand, radius, margin
    }

    build() {
      // A constructed stylesheet where supported: shared by every map on the page and
      // allowed under a strict Content-Security-Policy (no 'unsafe-inline' needed).
      let styleTag = '';
      try {
        sheet ||= new CSSStyleSheet();
        if (!sheet.cssRules.length) sheet.replaceSync(CSS);
        this.root.adoptedStyleSheets = [sheet];
      } catch { styleTag = `<style>${CSS}</style>`; }
      this.root.innerHTML = `${styleTag}
        <svg class="map" part="svg" role="group" aria-label="Map of Pimlico Road"></svg>
        <div class="controls" part="controls">
          <button class="ctl" data-act="in" aria-label="Zoom in">+</button>
          <button class="ctl" data-act="out" aria-label="Zoom out">\u2212</button>
          <button class="ctl" data-act="full" aria-label="Full screen" aria-pressed="false">
            <svg class="enter" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
            <svg class="exit" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
          </button>
          <button class="ctl" data-act="locate" aria-label="Show my location" hidden>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="3.5" fill="currentColor"/><circle cx="12" cy="12" r="7.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 1.5v3M12 19.5v3M1.5 12h3M19.5 12h3" stroke="currentColor" stroke-width="1.6"/></svg>
          </button>
        </div>
        <div class="search" part="search" role="search" hidden>
          <svg class="mag" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M15.5 15.5l5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <input type="search" role="combobox" aria-label="Search brands" placeholder="Search brands" aria-autocomplete="list"
                 aria-expanded="false" aria-controls="pm-results" autocomplete="off" spellcheck="false">
          <ul id="pm-results" role="listbox" aria-label="Matching brands" hidden></ul>
        </div>
        <div class="pin-label" part="pin-label" hidden></div>
        <div class="tip" hidden></div>
        <div class="hint" aria-hidden="true"></div>
        <div class="msg" role="status" hidden></div>
        <section class="card" part="card" role="region" aria-label="Brand details" hidden></section>
        <div class="attr" part="attribution"></div>`;
      const $ = (s) => this.root.querySelector(s);
      this.svg = $('svg.map');
      this.tip = $('.tip');
      this.hint = $('.hint');
      this.msg = $('.msg');
      this.card = $('.card');
      this.pinLabel = $('.pin-label');
      $('[data-act="locate"]').hidden = !this.hasAttribute('locate');
      $('.controls').addEventListener('click', (e) => {
        if (!this.vb) return;
        const act = e.target.closest('button')?.dataset.act;
        if (act === 'in') this.zoomBy(1.6);
        if (act === 'out') this.zoomBy(1 / 1.6);
        if (act === 'full') this.toggleFullscreen();
        if (act === 'locate') this.locate();
      });
      this.search = $('.search');
      this.bindPointer();
      this.bindKeys();
      this.bindSearch();
      // Leaving the browser's full screen by Esc or a system gesture.
      this.onFsChange = () => {
        const cur = document.fullscreenElement || document.webkitFullscreenElement;
        if (cur === this) this.nativeFs = true;
        else if (this.nativeFs) { this.nativeFs = false; this.setFull(false); }
      };
      this.ro = new ResizeObserver(() => this.onResize());
    }

    // ---------------------------------------------------------------- data

    async init() {
      const src = this.getAttribute('src');
      if (!src) return;
      const token = ++this.loadToken;
      try {
        const inline = this.querySelector('script[type="application/json"]');
        const brandsUrl = this.getAttribute('brands');
        const [base, brandData] = await Promise.all([
          load(new URL(src, document.baseURI).href),
          inline ? JSON.parse(inline.textContent) : brandsUrl ? load(new URL(brandsUrl, document.baseURI).href) : { brands: [] },
        ]);
        if (token !== this.loadToken) return;
        this.base = base;
        // { brands: [...] } or a bare array (e.g. a Drupal Views REST export).
        const list = Array.isArray(brandData) ? brandData : brandData.brands || [];
        this.brands = list.filter((b) => (b.status || 'active') === 'active');
        this.byBuilding = new Map();
        for (const b of this.brands) for (const id of b.buildings || []) {
          if (!this.byBuilding.has(id)) this.byBuilding.set(id, []);
          this.byBuilding.get(id).push(b);
        }
        this.buildingById = new Map(base.buildings.map((b) => [b.id, b]));
        this.msg.hidden = true;
        this.closeCard();
        this.draw();
        this.applyCategory();
        this.applyFocus();
        this.updateSearch();
        this.vb = null;
        this.fit(true);
        this.ready = true;
        this.dispatchEvent(new CustomEvent('pimlico-map:ready', { bubbles: true, composed: true, detail: { categories: this.categories } }));
      } catch (err) {
        if (token !== this.loadToken) return;
        this.showMsg('The map couldn\u2019t be loaded.', 0);
        console.error('pimlico-map:', err);
      }
    }

    // Categories of the active brands, in feed order (also sent with pimlico-map:ready).
    get categories() { return [...new Set((this.brands || []).map((b) => b.category).filter(Boolean))]; }

    // ---------------------------------------------------------------- drawing

    draw() {
      const b = this.base;
      const svg = this.svg;
      svg.textContent = '';
      // The data fills its bounds edge to edge, and the view never leaves them.
      const [x0, y0, x1, y1] = b.bounds;
      const defs = el('defs', {}, svg);
      el('rect', { class: 'ground', x: x0, y: y0, width: x1 - x0, height: y1 - y0 }, svg);
      const world = el('g', {}, svg);
      // Even-odd, so the blocks between roads are holes whatever the ring direction.
      el('path', { class: 'green', d: b.green, 'fill-rule': 'evenodd' }, world);
      el('path', { class: 'roads', d: b.roads, 'fill-rule': 'evenodd' }, world);
      // Road outline, inside the road edge only (clipped to the roads). Roads are cut
      // off at the map's edge, so a second clip, kept a pixel inside the map in
      // onScale(), stops that cut showing as a line along the frame.
      const roadClip = el('clipPath', { id: 'pm-roads' }, defs);
      el('path', { d: b.roads, 'clip-rule': 'evenodd' }, roadClip);
      this.edgeClip = el('rect', { x: x0, y: y0, width: x1 - x0, height: y1 - y0 }, el('clipPath', { id: 'pm-inner' }, defs));
      el('path', { class: 'road-edge', d: b.roads, 'clip-path': 'url(#pm-roads)' }, el('g', { 'clip-path': 'url(#pm-inner)' }, world));
      const trees = el('g', {}, world);
      for (const [x, y] of b.trees || []) el('circle', { class: 'tree', cx: x, cy: y, r: 2.2 }, trees);

      // Labels sit under the buildings so they never cover them.
      this.labelEls = [];
      const labels = el('g', { 'aria-hidden': 'true' }, world);
      b.labels.forEach((l, i) => {
        el('path', { id: `pm-l${i}`, d: l.d, fill: 'none' }, defs);
        const t = el('text', { class: `label${l.main ? ' main' : ''}${l.place ? ' place' : ''}`, dy: '0.35em', 'text-anchor': 'middle' }, labels);
        const tp = el('textPath', { href: `#pm-l${i}`, startOffset: '50%' }, t);
        tp.textContent = l.name;
        // Midpoint and direction of the street line, so shop names can keep clear of it.
        const pts = l.d.slice(1).split('L').map((p) => p.split(',').map(Number));
        let acc = 0, mid = pts[0], ang = 0;
        for (let k = 1; k < pts.length; k++) {
          const [ax, ay] = pts[k - 1], [bx, by] = pts[k], seg = Math.hypot(bx - ax, by - ay);
          if (acc + seg >= l.length / 2 || k === pts.length - 1) {
            const f = seg ? Math.min(1, (l.length / 2 - acc) / seg) : 0;
            mid = [ax + f * (bx - ax), ay + f * (by - ay)]; ang = Math.atan2(by - ay, bx - ax); break;
          }
          acc += seg;
        }
        this.labelEls.push({ t, l, mid, ang });
      });

      // Buildings in left-to-right order, so Tab moves along the street.
      const shadows = el('g', { class: 'shadow', transform: 'translate(1.2 1.6)' }, world);
      const blds = el('g', {}, world);
      this.bEls = new Map();
      for (const bd of b.buildings.slice().sort((p, q) => p.c[0] - q.c[0])) {
        el('path', { d: bd.d }, shadows);
        const p = el('path', { d: bd.d, class: this.byBuilding.has(bd.id) ? 'bldg active' : 'bldg', 'data-id': bd.id }, blds);
        this.bEls.set(bd.id, p);
      }

      // Shop names, one per tenancy building ("Name +1" when it has more than one
      // brand). Above the buildings; which ones show is decided in placeShopLabels().
      const shops = el('g', { class: 'shops', 'aria-hidden': 'true' }, world);
      this.shopEls = [];
      for (const [id, brands] of this.byBuilding) {
        const bd = this.buildingById.get(id);
        if (!bd) continue;
        const first = brands[0].label || brands[0].name; // optional short map label from the feed
        const text = brands.length > 1 ? `${first} +${brands.length - 1}` : first;
        const t = el('text', { class: 'shop', x: bd.c[0], y: bd.c[1], dy: '0.35em', 'text-anchor': 'middle' }, shops);
        t.textContent = text;
        this.shopEls.push({ t, id, c: bd.c, len: text.length, area: bd.a || 0 });
      }
      // Bigger buildings claim their space first.
      this.shopEls.sort((p, q) => q.area - p.area);
      // Site names (e.g. Newson's Yard) run across buildings, so unlike street names
      // they go above them; shop names keep clear of them like street names.
      const places = el('g', { class: 'places', 'aria-hidden': 'true' }, world);
      for (const { t, l } of this.labelEls) if (l.place) places.append(t);

      // Stations (e.g. Sloane Square): a navy disc with a train symbol and the name.
      // Drawn in screen pixels and scaled in onScale(), so they keep their size.
      const stations = el('g', { class: 'stations' }, world);
      this.stationEls = (b.stations || []).map((s) => {
        const g = el('g', { class: 'station', role: 'img', 'aria-label': `${s.name} station${s.lines ? `, ${s.lines}` : ''}` }, stations);
        el('circle', { r: 8 }, g);
        el('path', { class: 'glyph', d: TRAIN }, g);
        el('rect', { class: 'window', x: -3, y: -3.6, width: 6, height: 2.8, rx: 0.6 }, g);
        const name = el('text', { class: 'name', x: 12, y: s.lines ? -1.5 : 0, dy: '0.35em' }, g);
        name.textContent = s.name;
        if (s.lines) { const l = el('text', { class: 'lines', x: 12, y: 10.5, dy: '0.35em' }, g); l.textContent = s.lines; }
        const w = 12 + Math.max(s.name.length * 11 * 0.6, (s.lines || '').length * 10 * 0.55);
        return { g, c: s.c, box: [-9, -9, w + 2, s.lines ? 17 : 9] }; // screen px around the point
      });
      this.meG = el('g', { class: 'me', visibility: 'hidden' }, svg);
      el('circle', { class: 'acc', r: 0 }, this.meG);
      el('circle', { class: 'dot', r: 0 }, this.meG);

      const attr = this.root.querySelector('.attr');
      attr.textContent = '';
      if (/openstreetmap/i.test(b.attribution)) {
        const a = document.createElement('a');
        a.href = 'https://www.openstreetmap.org/copyright'; a.target = '_blank'; a.rel = 'noopener';
        a.textContent = b.attribution;
        attr.append(a);
      } else {
        attr.textContent = b.attribution || '';
      }
    }

    // Which buildings are lit, focusable and announced.
    applyCategory() {
      const cat = this.getAttribute('category') || '';
      const pick = this.hasAttribute('pick');
      for (const [id, p] of this.bEls || []) {
        const brands = this.byBuilding.get(id);
        const match = !!brands && (!cat || brands.some((b) => b.category === cat));
        p.classList.toggle('muted', !!brands && !match);
        if (match || pick) {
          const bd = this.buildingById.get(id);
          p.setAttribute('tabindex', '0');
          p.setAttribute('role', 'button');
          p.setAttribute('aria-label', brands ? brands.map((x) => x.name).join(', ') : `Building ${bd.address || id}`);
        } else {
          p.removeAttribute('tabindex');
          p.removeAttribute('role');
          p.removeAttribute('aria-label');
        }
      }
      this.placeShopLabels();
    }

    applyFocus() {
      for (const p of this.bEls.values()) p.classList.remove('focus');
      this.svg.classList.remove('focused');
      this.pinLabel.hidden = true;
      this.focusInfo = null;
      if (this.getAttribute('mode') !== 'brand') return;
      const id = this.getAttribute('brand');
      const brand = this.brands.find((b) => b.id === id);
      const blds = (brand?.buildings || []).map((bid) => this.buildingById.get(bid)).filter(Boolean);
      if (!blds.length) {
        console.warn(`pimlico-map: brand "${id}" isn't active or has no building on the map; showing the overview.`);
        return;
      }
      for (const b of blds) this.bEls.get(b.id)?.classList.add('focus');
      this.svg.classList.add('focused');
      // The name label sits just above the top of the brand's building, centred on it.
      const nums = blds[0].d.match(/-?\d+(?:\.\d+)?/g).map(Number);
      const xs = nums.filter((_, i) => i % 2 === 0), ys = nums.filter((_, i) => i % 2 === 1);
      const top = [(Math.min(...xs) + Math.max(...xs)) / 2, Math.min(...ys)];
      this.focusInfo = { brand, c: blds[0].c, top };
      this.pinLabel.textContent = brand.name;
      this.pinLabel.hidden = false;
    }

    // ---------------------------------------------------------------- view

    hasSize() { return this.clientWidth > 0 && this.clientHeight > 0; }
    aspect() { return this.clientWidth / this.clientHeight; }

    // viewBox that contains [x0,y0,x1,y1] at the element's aspect ratio
    boxFor([x0, y0, x1, y1]) {
      const a = this.aspect();
      let w = x1 - x0, h = y1 - y0;
      if (w / h > a) h = w / a; else w = h * a;
      return [(x0 + x1) / 2 - w / 2, (y0 + y1) / 2 - h / 2, w, h];
    }

    // The fitted view and zoom limits for the current size and mode.
    computeHome() {
      // Default views are framed inside the core area; the rest of the map (e.g.
      // the extension that reaches Sloane Square) is there for zooming out.
      const core = this.base.core || this.base.bounds;
      const [x0, y0, x1, y1] = core;
      if (this.focusInfo) {
        const r = +(this.getAttribute('radius') || 110);
        const [cx, cy] = this.focusInfo.c;
        this.home = this.boxFor([cx - r, cy - r, cx + r, cy + r]);
      } else {
        // Overview: every tenancy plus some context, inside the mapped area.
        const pts = [...this.byBuilding.keys()].map((id) => this.buildingById.get(id)?.c).filter(Boolean);
        const m = +(this.getAttribute('margin') || 70);
        const box = pts.length
          ? [Math.min(...pts.map((p) => p[0])) - m, Math.min(...pts.map((p) => p[1])) - m, Math.max(...pts.map((p) => p[0])) + m, Math.max(...pts.map((p) => p[1])) + m]
          : [x0, y0, x1, y1];
        this.home = this.boxFor([Math.max(x0, box[0]), Math.max(y0, box[1]), Math.min(x1, box[2]), Math.min(y1, box[3])]);
      }
      // Furthest zoom-out: the largest view that stays inside the whole map, so its
      // edges are never revealed. The fitted view is shrunk to fit inside the core.
      const [bx0, by0, bx1, by1] = this.base.bounds;
      this.maxW = Math.min(bx1 - bx0, (by1 - by0) * this.aspect());
      const coreW = Math.min(x1 - x0, (y1 - y0) * this.aspect());
      this.minW = Math.min(this.home[2], coreW) / MAX_ZOOM;
      this.home = this.clamp(this.home, core, coreW);
      // Centre of the fully zoomed-out view: it must contain every station marker
      // (with its label), and otherwise stays as close to the default view as it can.
      const st = this.base.stations || [];
      if (st.length) {
        const vw = this.maxW, vh = vw / this.aspect(), px = vw / this.clientWidth; // map units per px at full zoom-out
        const need = [
          Math.min(...st.map((s) => s.c[0] - 14 * px)), Math.min(...st.map((s) => s.c[1] - (this.search.hidden ? 14 : 64) * px)), // clear of the search box,
          Math.max(...st.map((s) => s.c[0] + 140 * px)), Math.max(...st.map((s) => s.c[1] + 26 * px)),
        ];
        const fit = (want, lo, hi, size) => Math.min(lo + size / 2, Math.max(hi - size / 2, want));
        const [hx, hy, hw, hh] = this.home;
        this.wide = [fit(hx + hw / 2, need[0], need[2], vw), fit(hy + hh / 2, need[1], need[3], vh)];
      } else this.wide = null;
      this.chrome = null; // re-measured on the next label pass
    }

    // Sizes of the HTML on top of the map that shop names must avoid.
    measureChrome() {
      const c = this.root.querySelector('.controls');
      this.chrome = {
        pin: [this.pinLabel.offsetWidth || 120, this.pinLabel.offsetHeight || 28],
        ctl: c.offsetWidth ? [c.offsetLeft - 4, c.offsetTop - 4, c.offsetLeft + c.offsetWidth + 4, c.offsetTop + c.offsetHeight + 4] : null,
        search: this.search.hidden ? null : [0, 0, this.search.offsetLeft + this.search.offsetWidth + 6, this.search.offsetTop + this.search.querySelector('input').offsetHeight + 6],
      };
      return this.chrome;
    }

    fit(immediate) {
      if (!this.base || !this.hasSize()) return; // a hidden map fits when it gets a size
      this.computeHome();
      if (immediate) this.setView(this.home); else this.animateTo(this.home);
    }

    onResize() {
      if (!this.base || !this.hasSize()) return;
      if (!this.vb) return this.fit(true);
      // Keep the centre and the width in map units; change the shape.
      const [x, y, w, h] = this.vb;
      this.computeHome();
      this.setView([x, y + h / 2 - w / this.aspect() / 2, w, w / this.aspect()]);
    }

    clampW(w) { return Math.min(this.maxW, Math.max(this.minW, w)); }

    // Keep the view's size within the zoom limits and the whole view inside the map.
    clamp([x, y, w, h], [bx0, by0, bx1, by1] = this.base.bounds, maxW = this.maxW) {
      const nw = Math.min(maxW, this.clampW(w)), nh = nw / this.aspect();
      const cx = Math.min(bx1 - nw / 2, Math.max(bx0 + nw / 2, x + w / 2));
      const cy = Math.min(by1 - nh / 2, Math.max(by0 + nh / 2, y + h / 2));
      return [cx - nw / 2, cy - nh / 2, nw, nh];
    }

    setView(vb) {
      if (!this.hasSize()) return;
      this.vb = this.clamp(vb);
      this.svg.setAttribute('viewBox', this.vb.map((v) => v.toFixed(2)).join(' '));
      this.onScale();
    }

    animateTo(target) {
      if (!target) return;
      const from = this.vb;
      if (!from || reduceMotion()) { this.setView(target); return; }
      const t0 = performance.now(), dur = 380;
      cancelAnimationFrame(this.raf);
      const step = (t) => {
        const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
        this.setView(from.map((v, i) => v + (target[i] - v) * e));
        if (k < 1) this.raf = requestAnimationFrame(step);
      };
      this.raf = requestAnimationFrame(step);
    }

    // Zoom `vb` by f so the map point under screen point (ax, ay) ends up under
    // (bx, by). The zoom is clamped first, so at the limits nothing slides.
    zoomAround(vb, f, ax, ay, bx = ax, by = ay) {
      const [x, y, w, h] = vb;
      const nw = this.clampW(w / f), nh = nw / this.aspect();
      const ux = x + (ax / this.clientWidth) * w, uy = y + (ay / this.clientHeight) * h;
      let nx = ux - (bx / this.clientWidth) * nw, ny = uy - (by / this.clientHeight) * nh;
      // Zooming out drifts towards the wide view (shops + stations), arriving there
      // at full zoom-out, so the stations come into view without having to pan.
      if (this.wide && nw > w && this.maxW > w) {
        const k = Math.min(1, (nw - w) / (this.maxW - w));
        nx += (this.wide[0] - nw / 2 - nx) * k;
        ny += (this.wide[1] - nh / 2 - ny) * k;
      }
      return [nx, ny, nw, nh];
    }

    zoomBy(f, px, py) {
      if (!this.vb) return;
      const animate = px == null;
      const target = this.zoomAround(this.vb, f, animate ? this.clientWidth / 2 : px, animate ? this.clientHeight / 2 : py);
      if (animate) this.animateTo(target); else this.setView(target);
    }

    panBy(fx, fy) {
      if (!this.vb) return;
      const [x, y, w, h] = this.vb;
      this.animateTo([x + fx * w, y + fy * h, w, h]);
    }

    // Screen-constant sizes: labels, pin, blue dot.
    onScale() {
      const u = this.vb[2] / this.clientWidth; // map units per screen px
      const fs = LABEL_PX * u;
      for (const { t, l } of this.labelEls || []) {
        t.setAttribute('font-size', fs.toFixed(2));
        t.style.strokeWidth = (3 * u).toFixed(2);
        // Hide labels that don't fit their street at this zoom.
        t.setAttribute('visibility', l.name.length * fs * LABEL_CHAR_EM < l.length * 0.92 ? 'visible' : 'hidden');
      }
      if (this.focusInfo) {
        const [sx, sy] = this.toScreen(this.focusInfo.top);
        this.pinLabel.style.left = sx + 'px';
        this.pinLabel.style.top = sy + 'px';
      }
      if (this.edgeClip) {
        const [x0, y0, x1, y1] = this.base.bounds, m = 1.5 * u;
        this.edgeClip.setAttribute('x', x0 + m); this.edgeClip.setAttribute('y', y0 + m);
        this.edgeClip.setAttribute('width', x1 - x0 - 2 * m); this.edgeClip.setAttribute('height', y1 - y0 - 2 * m);
      }
      for (const s of this.stationEls || []) s.g.setAttribute('transform', `translate(${s.c[0]} ${s.c[1]}) scale(${u.toFixed(4)})`);
      this.placeShopLabels(u);
      if (this.me) this.placeMe();
    }

    // Show as many shop names as fit without overlapping each other, the brand
    // pin or the controls; skip buildings muted by the category filter and the
    // brand-page focus (its name is already on the pin).
    placeShopLabels(u = this.vb && this.vb[2] / this.clientWidth) {
      if (!this.shopEls || !u || this.hasAttribute('no-labels')) return;
      const fs = SHOP_LABEL_PX * u, halo = (2.5 * u).toFixed(2), H = SHOP_LABEL_PX * 1.3;
      const taken = [];
      const hits = (b) => taken.some((o) => b[0] < o[2] && b[2] > o[0] && b[1] < o[3] && b[3] > o[1]);
      // Pin label and controls are measured once per resize (measureChrome), not per
      // frame, so pans and pinches never force a layout.
      const { pin, ctl, search } = this.chrome || this.measureChrome();
      if (this.focusInfo && !this.pinLabel.hidden) {
        const [px, py] = this.toScreen(this.focusInfo.top);
        taken.push([px - pin[0] / 2 - 4, py - 7 - pin[1] - 4, px + pin[0] / 2 + 4, py + 2]);
      }
      if (ctl) taken.push(ctl);
      if (search) taken.push(search);
      for (const s of this.stationEls || []) {
        const [sx, sy] = this.toScreen(s.c);
        taken.push([sx + s.box[0] - 3, sy + s.box[1] - 3, sx + s.box[2] + 3, sy + s.box[3] + 3]);
      }
      // Visible street names, as rotated rectangles in screen px.
      const streets = [];
      for (const { t, l, mid, ang } of this.labelEls || []) {
        if (t.getAttribute('visibility') !== 'visible') continue;
        streets.push(rotRect(...this.toScreen(mid), l.name.length * LABEL_PX * LABEL_CHAR_EM / 2 + 3, LABEL_PX * 0.8, ang));
      }
      const onStreet = (b) => { const R = [[b[0], b[1]], [b[2], b[1]], [b[2], b[3]], [b[0], b[3]]]; return streets.some((S) => overlaps(R, S)); };
      const focusIds = new Set(this.focusInfo?.brand.buildings || []);
      // The selected building's name goes first, then the rest by size.
      const sel = this.svg.querySelector('.bldg.selected')?.dataset.id;
      const order = sel ? [...this.shopEls.filter((x) => x.id === sel), ...this.shopEls.filter((x) => x.id !== sel)] : this.shopEls;
      for (const s of order) {
        const muted = this.bEls.get(s.id)?.classList.contains('muted');
        const [x, y] = this.toScreen(s.c);
        const w = s.len * SHOP_LABEL_PX * SHOP_CHAR_EM + 4;
        const box = [x - w / 2, y - H / 2, x + w / 2, y + H / 2];
        const onScreen = box[0] >= 4 && box[2] <= this.clientWidth - 4 && box[1] >= 4 && box[3] <= this.clientHeight - 4; // never cut off at an edge
        const show = !muted && !focusIds.has(s.id) && onScreen && !hits(box) && !onStreet(box);
        if (show) taken.push(box);
        // Under the hover tooltip its own name would show twice: hide it, keep its space.
        const under = show && s.id === this.tipId;
        s.t.setAttribute('visibility', show && !under ? 'visible' : 'hidden');
        if (show) { s.t.setAttribute('font-size', fs.toFixed(2)); s.t.style.strokeWidth = halo; }
      }
    }

    toScreen([x, y]) {
      const [vx, vy, vw] = this.vb;
      const s = this.clientWidth / vw;
      return [(x - vx) * s, (y - vy) * s];
    }

    // ---------------------------------------------------------------- input

    bindPointer() {
      const svg = this.svg;
      const pts = new Map();
      let moved = false, last = null, pinch = null, gesture = null;
      const local = (cx, cy) => { const r = this.getBoundingClientRect(); return [cx - r.left, cy - r.top]; };

      const flashHint = (text) => {
        this.hint.textContent = text;
        this.hint.classList.add('show');
        clearTimeout(this.hintT);
        this.hintT = setTimeout(() => this.hint.classList.remove('show'), 1200);
      };

      svg.addEventListener('pointerdown', (e) => {
        if (this.hasAttribute('static') || !this.vb) return;
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY, x0: e.clientX, y0: e.clientY, type: e.pointerType });
        moved = false;
        last = { x: e.clientX, y: e.clientY };
        if (pts.size === 2) {
          const [a, b] = [...pts.values()];
          pinch = { d: Math.hypot(a.x - b.x, a.y - b.y), vb: this.vb.slice(), mid: local((a.x + b.x) / 2, (a.y + b.y) / 2) };
        }
      });
      svg.addEventListener('pointermove', (e) => {
        const p = pts.get(e.pointerId);
        if (!p) { this.hover(e); return; }
        p.x = e.clientX; p.y = e.clientY;
        const dist = Math.hypot(p.x - p.x0, p.y - p.y0);
        // Cooperative gestures: one finger scrolls the page; two move the map.
        if (p.type === 'touch' && pts.size === 1 && !this.isFull) {
          if (dist > 12) { moved = true; flashHint('Use two fingers to move the map'); }
          return;
        }
        if (!moved && dist > 4) {
          moved = true;
          svg.setPointerCapture(e.pointerId);
          this.tip.hidden = true; this.setTipId(null);
        }
        if (!moved) return;
        if (pts.size === 2 && pinch) {
          const [a, b] = [...pts.values()];
          const mid = local((a.x + b.x) / 2, (a.y + b.y) / 2);
          this.setView(this.zoomAround(pinch.vb, Math.hypot(a.x - b.x, a.y - b.y) / pinch.d, ...pinch.mid, ...mid));
        } else if (pts.size === 1) {
          const [x, y, w, h] = this.vb;
          const u = w / this.clientWidth;
          this.setView([x - (e.clientX - last.x) * u, y - (e.clientY - last.y) * u, w, h]);
        }
        last = { x: e.clientX, y: e.clientY };
      });
      const end = (e) => {
        const p = pts.get(e.pointerId);
        pts.delete(e.pointerId);
        if (pts.size < 2) pinch = null;
        const rest = [...pts.values()][0];
        if (rest) last = { x: rest.x, y: rest.y };
        if (p && !moved && e.type === 'pointerup') this.onMapClick(e);
      };
      svg.addEventListener('pointerup', end);
      svg.addEventListener('pointercancel', end);
      svg.addEventListener('pointerleave', () => { this.tip.hidden = true; this.setTipId(null); });
      // With `static`, pointerdown is ignored, so taps arrive as plain clicks.
      svg.addEventListener('click', (e) => { if (this.hasAttribute('static')) this.onMapClick(e); });

      // Cooperative wheel: plain scrolling scrolls the page; ⌘/Ctrl + scroll zooms
      // (trackpad pinch arrives as ctrl + wheel in Chrome and Firefox).
      svg.addEventListener('wheel', (e) => {
        if (this.hasAttribute('static') || !this.vb) return;
        if (!(e.ctrlKey || e.metaKey || this.isFull)) { flashHint(`Use ${isMac ? '\u2318' : 'Ctrl'} + scroll to zoom the map`); return; }
        e.preventDefault();
        const k = e.deltaMode === 1 ? 0.05 : 0.0025;
        this.zoomBy(Math.exp(-e.deltaY * k), ...local(e.clientX, e.clientY));
      }, { passive: false });

      // Safari sends trackpad pinch as gesture events instead. Touch pinches are
      // already handled by the pointer code, so only use these with no pointers down.
      svg.addEventListener('gesturestart', (e) => {
        if (this.hasAttribute('static') || !this.vb || pts.size) return;
        e.preventDefault();
        gesture = { vb: this.vb.slice(), at: local(e.clientX, e.clientY) };
      });
      svg.addEventListener('gesturechange', (e) => {
        if (!gesture) return;
        e.preventDefault();
        this.setView(this.zoomAround(gesture.vb, e.scale, ...gesture.at));
      });
      svg.addEventListener('gestureend', () => { gesture = null; });
    }

    bindKeys() {
      // Arrow keys pan, +/− zoom, Escape closes the card.
      this.root.addEventListener('keydown', (e) => {
        if (!this.vb || e.altKey || e.metaKey || e.ctrlKey) return;
        if (e.composedPath().includes(this.search)) return; // typing in the search box
        const inCard = e.composedPath().includes(this.card);
        if (e.key === 'Escape' && !this.card.hidden) { e.preventDefault(); this.closeCard(true); return; }
        if (e.key === 'Escape' && this.isFull) { e.preventDefault(); this.exitFullscreen(); return; }
        if (inCard) return;
        if (e.key === 'Home' || e.key === '0') { e.preventDefault(); this.closeCard(); this.animateTo(this.home); return; }
        const pan = { ArrowLeft: [-0.2, 0], ArrowRight: [0.2, 0], ArrowUp: [0, -0.2], ArrowDown: [0, 0.2] }[e.key];
        if (pan) { e.preventDefault(); this.panBy(...pan); return; }
        if (e.key === '+' || e.key === '=') { e.preventDefault(); this.zoomBy(1.6); }
        if (e.key === '-') { e.preventDefault(); this.zoomBy(1 / 1.6); }
        const bld = e.target.closest?.('.bldg');
        if (bld && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); this.select(bld.dataset.id); }
      });
      // Keep a focused building in view.
      this.svg.addEventListener('focusin', (e) => {
        const id = e.target.dataset?.id;
        const c = id && this.buildingById?.get(id)?.c;
        if (!c || !this.vb) return;
        const [sx, sy] = this.toScreen(c);
        const m = 40;
        if (sx < m || sy < m || sx > this.clientWidth - m || sy > this.clientHeight - m) {
          const [, , w, h] = this.vb;
          this.animateTo([c[0] - w / 2, c[1] - h / 2, w, h]);
        }
      });
    }

    buildingAt(e) {
      const t = this.root.elementFromPoint ? this.root.elementFromPoint(e.clientX, e.clientY) : e.target;
      const p = (t || e.target)?.closest?.(this.hasAttribute('pick') ? '.bldg' : '.bldg.active');
      return p && !p.classList.contains('muted') ? p.dataset.id : null;
    }

    hover(e) {
      if (e.pointerType === 'touch' || !this.base) return;
      const id = this.buildingAt(e);
      this.setTipId(id);
      if (!id) { this.tip.hidden = true; return; }
      const names = (this.byBuilding.get(id) || []).map((b) => b.name);
      const bd = this.buildingById.get(id);
      const text = names.length ? names.join(' \u00b7 ') : (bd.address || '');
      // Pick mode shows the building id, for setting a unit's building.
      this.tip.textContent = this.hasAttribute('pick') ? [text, id].filter(Boolean).join(' \u00b7 ') : text;
      const [x, y] = [e.clientX - this.getBoundingClientRect().left, e.clientY - this.getBoundingClientRect().top];
      this.tip.style.left = `${x}px`;
      this.tip.style.top = `${y}px`;
      this.tip.hidden = false;
    }

    setTipId(id) {
      if (id === this.tipId) return;
      this.tipId = id;
      this.placeShopLabels();
    }

    onMapClick(e) {
      if (!this.base) return;
      const id = this.buildingAt(e);
      if (id) this.select(id); else this.closeCard();
    }

    // ---------------------------------------------------------------- selection

    // Select a building and open its card. `only` limits it to one brand (from the search).
    select(buildingId, only = null) {
      const brands = this.byBuilding.get(buildingId) || [];
      const cat = this.getAttribute('category');
      const shown = only ? [only] : cat ? brands.filter((b) => b.category === cat) : brands;
      for (const p of this.bEls.values()) p.classList.toggle('selected', p.dataset.id === buildingId);
      this.placeShopLabels();
      this.dispatchEvent(new CustomEvent('pimlico-map:select', { bubbles: true, composed: true, detail: { brands: shown, buildingId } }));
      if (this.hasAttribute('no-card') || this.hasAttribute('pick') || !shown.length) return;
      this.openCard(shown, this.bEls.get(buildingId));
    }

    openCard(brands, from) {
      const c = this.card;
      c.textContent = '';
      const add = (tag, cls, text, parent = c) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text) n.textContent = text; parent.append(n); return n; };
      const close = add('button', 'close', '\u00d7');
      close.setAttribute('aria-label', 'Close');
      close.addEventListener('click', () => this.closeCard(true));
      if (brands.length === 1) {
        const b = brands[0];
        add('p', 'cat', b.category || '');
        add('h3', '', b.name);
        if (b.address) add('p', '', b.address);
        if (b.url) add('a', 'more', 'View brand').href = b.url;
      } else {
        add('p', 'cat', `${brands.length} brands`);
        const ul = add('ul');
        for (const b of brands) {
          const a = add(b.url ? 'a' : 'span', '', b.name, add('li', '', '', ul));
          if (b.url) a.href = b.url;
        }
      }
      c.hidden = false;
      this.returnFocus = from || null;
      close.focus({ preventScroll: true });
    }

    // ---------------------------------------------------------------- brand search

    // Shown on the overview only: not on a brand page (mode="brand" with a valid brand),
    // in pick or static mode, or with no-search.
    updateSearch() {
      const off = !!this.focusInfo || ['pick', 'static', 'no-search'].some((a) => this.hasAttribute(a));
      if (off && this.search.contains(this.root.activeElement)) this.search.querySelector('input').blur();
      this.search.hidden = off;
      this.chrome = null;
      this.closeResults?.();
    }

    // A combobox (ARIA 1.2 pattern): type to list matching brands, arrows to move,
    // Enter or click to go there. Matches name or short label, ignoring case and accents;
    // names that start with the text come first. Respects the category filter.
    bindSearch() {
      const input = this.search.querySelector('input'), list = this.search.querySelector('ul');
      const norm = (t) => (t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      let items = [], active = -1;
      const setActive = (i) => {
        active = i;
        [...list.children].forEach((li, k) => li.setAttribute('aria-selected', String(k === i)));
        if (i >= 0 && items[i]) { input.setAttribute('aria-activedescendant', `pm-opt-${i}`); list.children[i].scrollIntoView({ block: 'nearest' }); }
        else input.removeAttribute('aria-activedescendant');
      };
      const close = this.closeResults = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); setActive(-1); };
      const update = () => {
        const q = norm(input.value.trim());
        if (!q || !this.brands) return close();
        const cat = this.getAttribute('category') || '';
        items = this.brands
          .filter((b) => (!cat || b.category === cat) && (b.buildings || []).some((id) => this.buildingById.has(id)))
          .map((b) => {
            const at = [norm(b.name), norm(b.label)].map((n) => { const i = n.indexOf(q); return i < 0 ? Infinity : i === 0 ? 0 : /[a-z0-9]/.test(n[i - 1]) ? 2 : 1; });
            return { b, rank: Math.min(...at) };
          })
          .filter((x) => x.rank < Infinity)
          .sort((p, q2) => p.rank - q2.rank || p.b.name.localeCompare(q2.b.name))
          .slice(0, 8).map((x) => x.b);
        list.textContent = '';
        if (!items.length) {
          const li = document.createElement('li');
          li.className = 'none'; li.setAttribute('role', 'option'); li.setAttribute('aria-disabled', 'true');
          li.textContent = cat ? `No ${cat} brands match` : 'No brands match';
          list.append(li);
        }
        items.forEach((b, i) => {
          const li = document.createElement('li');
          li.id = `pm-opt-${i}`; li.setAttribute('role', 'option');
          const n = document.createElement('span'); n.textContent = b.name;
          const c = document.createElement('span'); c.className = 'cat'; c.textContent = b.category || '';
          li.append(n, c);
          li.addEventListener('pointerdown', (e) => e.preventDefault()); // keep focus in the box
          li.addEventListener('click', () => this.goToBrand(b));
          list.append(li);
        });
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        setActive(-1);
      };
      input.addEventListener('input', update);
      input.addEventListener('focus', () => { if (input.value.trim()) update(); });
      input.addEventListener('blur', close);
      input.addEventListener('keydown', (e) => {
        const open = !list.hidden && items.length;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault();
          if (list.hidden) update();
          if (!items.length) return;
          const step = e.key === 'ArrowDown' ? 1 : -1;
          setActive(active < 0 ? (step > 0 ? 0 : items.length - 1) : (active + step + items.length) % items.length);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (open) this.goToBrand(items[Math.max(0, active)]);
        } else if (e.key === 'Escape') {
          if (!list.hidden) { e.preventDefault(); close(); } else if (input.value) { e.preventDefault(); input.value = ''; }
        }
      });
    }

    // Fly to a brand's building, highlight it and open its card.
    goToBrand(b) {
      const input = this.search.querySelector('input');
      input.value = b.name;
      this.closeResults();
      const id = b.buildings.find((x) => this.buildingById.has(x));
      if (!id || !this.vb) return;
      const [cx, cy] = this.buildingById.get(id).c;
      const [, , w] = this.vb;
      const nw = Math.min(w, Math.max(this.minW, 170)), nh = nw / this.aspect(); // about 170 m across
      this.animateTo([cx - nw / 2, cy - nh * 0.45, nw, nh]); // building a little above centre, clear of the card
      this.select(id, b);
    }

    closeCard(restoreFocus = false) {
      if (!this.card) return;
      const hadFocus = this.card.contains(this.root.activeElement);
      this.card.hidden = true;
      for (const p of this.bEls?.values() || []) p.classList.remove('selected');
      this.placeShopLabels();
      if ((restoreFocus || hadFocus) && this.returnFocus?.isConnected) this.returnFocus.focus({ preventScroll: true });
      this.returnFocus = null;
    }

    // ---------------------------------------------------------------- you are here

    lonlatToMap(lon, lat) {
      const g = this.base.lonlat;
      const dx = lon - g.lon0, dy = lat - g.lat0;
      return [g.x[0] * dx + g.x[1] * dy + g.x[2], g.y[0] * dx + g.y[1] * dy + g.y[2]];
    }

    showMsg(text, ms = 3500) {
      this.msg.textContent = text;
      this.msg.hidden = false;
      clearTimeout(this.msgT);
      if (ms) this.msgT = setTimeout(() => { this.msg.hidden = true; }, ms);
    }

    stopLocate() {
      if (this.watchId != null) navigator.geolocation.clearWatch(this.watchId);
      this.watchId = null;
      this.me = null;
      this.meG?.setAttribute('visibility', 'hidden');
    }

    locate() {
      if (!('geolocation' in navigator)) return this.showMsg('Location isn\u2019t available in this browser.');
      if (this.watchId != null) return this.stopLocate();
      let first = true;
      this.watchId = navigator.geolocation.watchPosition((p) => {
        if (!this.base) return;
        this.me = { pt: this.lonlatToMap(p.coords.longitude, p.coords.latitude), acc: p.coords.accuracy };
        const inside = this.placeMe();
        if (first) {
          if (!inside) this.showMsg('You\u2019re outside the map area.');
          else if (this.vb) { const [, , w, h] = this.vb; this.animateTo([this.me.pt[0] - w / 2, this.me.pt[1] - h / 2, w, h]); }
        }
        first = false;
      }, (err) => {
        this.stopLocate();
        this.showMsg(err.code === 1 ? 'Location permission was denied.' : 'Couldn\u2019t get your location.');
      }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 });
    }

    placeMe() {
      const [x, y] = this.me.pt;
      const [bx0, by0, bx1, by1] = this.base.bounds;
      const inside = x >= bx0 && x <= bx1 && y >= by0 && y <= by1;
      this.meG.setAttribute('visibility', inside ? 'visible' : 'hidden');
      if (!this.vb) return inside;
      const [acc, dot] = this.meG.children;
      for (const c of [acc, dot]) { c.setAttribute('cx', x); c.setAttribute('cy', y); }
      acc.setAttribute('r', Math.max(0, this.me.acc || 0));
      dot.setAttribute('r', ((6 * this.vb[2]) / this.clientWidth).toFixed(2));
      return inside;
    }
  }

  if (!customElements.get('pimlico-map')) customElements.define('pimlico-map', PimlicoMap);
})();
