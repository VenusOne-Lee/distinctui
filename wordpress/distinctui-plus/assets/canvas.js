/*
 * distinctUI+ canvas.
 *
 * The page in the middle is the real page, drawn by WordPress with the live
 * site's CSS. You pick layouts and type on it; there's no settings panel.
 * Every change is a small step in the page spec, so undo is instant, and
 * every change is checked against the twelve rules.
 *
 * No framework and no build step: this file is what runs.
 */

const D = JSON.parse(document.getElementById('dui-data').textContent);
const app = document.getElementById('dui-app');

/* ------------------------------------------------------------ State */

const S = {
	page: D.page,                      // from the server, or null for a new page
	spec: D.page ? D.page.spec : null,
	title: D.page ? D.page.title : '',
	rev: D.page ? D.page.rev : 0,
	check: D.page ? D.page.check : null,
	htmlCheck: null,
	sel: -1,
	view: D.builder.phone_beside ? 'both' : 'desktop',
	undo: [],
	redo: [],
	saving: false,
	dirty: false,
	note: '',                          // a plain line under the bar ("Claude made 3 changes")
	head: null,                        // the iframe <head> from the server
	cache: new Map(),                  // section JSON -> { html, css }
	css: new Set(),
	asks: D.page ? D.page.asks : [],
	editing: false,
};
const canBuild = !!D.canBuild;

/* ---------------------------------------------------------- Helpers */

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const clone = (o) => JSON.parse(JSON.stringify(o));
const key = (s) => JSON.stringify(s);
const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

async function api(path, { method = 'GET', body } = {}) {
	const res = await fetch(D.rest + path, {
		method,
		headers: { 'X-WP-Nonce': D.nonce, ...(body ? { 'Content-Type': 'application/json' } : {}) },
		body: body ? JSON.stringify(body) : undefined,
		credentials: 'same-origin',
	});
	const data = await res.json().catch(() => ({}));
	if (!res.ok) {
		const err = new Error(data.message || 'Something went wrong on the server. Your work is still here; try again.');
		err.code = data.code; err.status = res.status; err.data = data.data;
		throw err;
	}
	return data;
}

/* Bold a word in a heading, the same way the server does. */
function boldHTML(text, bold) {
	const t = String(text || '');
	const b = String(bold || '').trim();
	if (!b) return esc(t);
	const at = t.toLowerCase().indexOf(b.toLowerCase());
	if (at < 0) return esc(t);
	return esc(t.slice(0, at)) + '<strong>' + esc(t.slice(at, at + b.length)) + '</strong>' + esc(t.slice(at + b.length));
}

function getPath(obj, path) { return path.split('.').reduce((o, k) => (o == null ? o : o[k]), obj); }
function setPath(obj, path, value) {
	const parts = path.split('.');
	let o = obj;
	for (let i = 0; i < parts.length - 1; i++) {
		const k = parts[i];
		if (o[k] == null) o[k] = /^\d+$/.test(parts[i + 1]) ? [] : {};
		o = o[k];
	}
	o[parts[parts.length - 1]] = value;
}

/* --------------------------------------------------------- The shell */

function shell() {
	app.removeAttribute('aria-busy');
	app.innerHTML = `
	<header class="bar">
		<a class="back" href="${esc(D.back)}" aria-label="Back to your pages">←</a>
		<span class="mark-mini ${S.page && S.page.live ? 'is-live' : ''}" style="--sig:${esc(D.signature.colour)}" aria-hidden="true"></span>
		<label class="title"><span class="sr">Page title</span><input id="dui-title" value="${esc(S.title)}" placeholder="Page title" ${canBuild ? '' : 'readonly'}></label>
		<span class="saved" id="dui-saved" role="status" aria-live="polite"></span>
		<span class="grow"></span>
		<div class="seg" role="group" aria-label="What to show">
			<button type="button" data-view="desktop">Desktop</button>
			<button type="button" data-view="both">Both</button>
			<button type="button" data-view="phone">Phone</button>
		</div>
		<button type="button" class="icon" id="dui-undo" aria-label="Undo" title="Undo (Ctrl or Cmd + Z)">↶</button>
		<button type="button" class="icon" id="dui-redo" aria-label="Redo" title="Redo (Shift + Ctrl or Cmd + Z)">↷</button>
		<button type="button" class="ghost" id="dui-versions">Versions</button>
		<a class="ghost" id="dui-preview" target="_blank" rel="noopener">Preview</a>
		<button type="button" class="pill" id="dui-publish">Publish</button>
	</header>
	<p class="note" id="dui-note" hidden></p>
	<div class="body">
		<aside class="rail" aria-label="Sections">
			<h2>On this page</h2>
			<ol id="dui-list" class="list"></ol>
			<h2>Add a section</h2>
			<div id="dui-add" class="add"></div>
			<h2>Ask your Claude</h2>
			<form id="dui-ask" class="ask">
				<label class="sr" for="dui-ask-text">What should Claude do?</label>
				<textarea id="dui-ask-text" rows="3" placeholder="Add three plans: Solo R149, Shop R399, Group R899" ${canBuild ? '' : 'disabled'}></textarea>
				<button type="submit" class="ghost" ${canBuild ? '' : 'disabled'}>Send to Claude</button>
				<div id="dui-asks"></div>
			</form>
		</aside>
		<main class="stage" id="dui-stage">
			<div class="frame desktop" id="dui-desk"><iframe title="Your page on a desktop" id="dui-frame"></iframe></div>
			<div class="frame phone" id="dui-phone-wrap"><iframe title="Your page on a phone" id="dui-phone"></iframe></div>
		</main>
	</div>
	<footer class="rules" id="dui-rules" aria-live="polite"></footer>
	<div class="scrim" id="dui-scrim" hidden></div>`;

	$('#dui-title').addEventListener('input', (e) => { S.title = e.target.value; save(); });
	$$('[data-view]').forEach((b) => b.addEventListener('click', () => setView(b.dataset.view)));
	$('#dui-undo').addEventListener('click', undo);
	$('#dui-redo').addEventListener('click', redo);
	$('#dui-publish').addEventListener('click', publish);
	$('#dui-versions').addEventListener('click', versions);
	$('#dui-ask').addEventListener('submit', ask);
	$('#dui-add').innerHTML = Object.entries(D.types).map(([type, t]) =>
		`<button type="button" data-add="${type}" title="${esc(t.about)}" ${canBuild ? '' : 'disabled'}><b>${esc(t.name)}</b><span>${esc(t.about)}</span></button>`).join('');
	$('#dui-add').addEventListener('click', (e) => { const b = e.target.closest('[data-add]'); if (b) addSection(b.dataset.add); });
	$('#dui-list').addEventListener('click', (e) => { const b = e.target.closest('[data-pick]'); if (b) select(+b.dataset.pick, true); });
	document.addEventListener('keydown', keys);
	new ResizeObserver(fit).observe($('#dui-stage'));
	setView(S.view);
}

function setView(v) {
	S.view = v;
	$('#dui-stage').dataset.view = v;
	$$('[data-view]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.view === v)));
	fit();
}

/* Scale the 1280px desktop to fit; the phone stays 390px wide. */
function fit() {
	const stage = $('#dui-stage');
	if (!stage) return;
	const desk = $('#dui-desk');
	const frame = $('#dui-frame');
	const w = desk.clientWidth;
	const h = desk.clientHeight;
	const narrow = window.innerWidth < 900;
	const width = narrow ? w : 1280;
	const scale = narrow ? 1 : Math.min(1, w / 1280);
	frame.style.width = width + 'px';
	frame.style.height = (h / scale) + 'px';
	frame.style.transform = `scale(${scale})`;
}

/* ------------------------------------------------------- The iframes */

function frameDoc(id) { const f = document.getElementById(id); return f && f.contentDocument; }

const FRAME_CSS = `
html{scroll-behavior:smooth}
body{margin:0;padding-bottom:6rem}
.dui-sec{position:relative;transition:outline-color 120ms}
#dui-page>.dui-sec{max-width:var(--wp--style--global--wide-size)!important;margin-left:auto!important;margin-right:auto!important}
.dui-sec>.alignwide{max-width:none}
.dui-canvas .dui-sec{outline:1px solid transparent;outline-offset:-1px}
.dui-canvas .dui-sec:hover{outline-color:#a3a3a3}
.dui-canvas .dui-sec.is-sel{outline:2px solid #000;outline-offset:-2px}
.dui-tools{position:absolute;top:12px;right:12px;z-index:5;display:none;gap:4px;flex-wrap:wrap;justify-content:flex-end;max-width:70%;font:500 13px/1 "Bricolage Grotesque",ui-sans-serif,system-ui,sans-serif}
.dui-sec.is-sel>.dui-tools{display:flex}
.dui-tools button{border:1px solid #e5e5e5;background:#fff;color:#000;border-radius:999px;padding:7px 12px;cursor:pointer;font:inherit}
.dui-tools button:hover{border-color:#000}
.dui-tools button[aria-pressed="true"]{background:#000;color:#fff;border-color:#000}
.dui-tools .sep{width:8px}
.dui-tag{position:absolute;top:12px;left:12px;z-index:5;display:none;font:500 12px/1 "Bricolage Grotesque",ui-sans-serif,system-ui,sans-serif;background:#000;color:#fff;border-radius:999px;padding:6px 10px}
.dui-sec.is-sel>.dui-tag{display:block}
[data-dui-slot][contenteditable]{outline:none;cursor:text;border-radius:4px}
[data-dui-slot][contenteditable]:hover{box-shadow:0 0 0 1px #e5e5e5}
[data-dui-slot][contenteditable]:focus{box-shadow:0 0 0 2px #000}
.dui-empty{display:grid;place-items:center;min-height:60vh;text-align:center;color:#6b6b6b;font-family:"Bricolage Grotesque",ui-sans-serif,system-ui,sans-serif}
.dui-swap{animation:dui-in 200ms cubic-bezier(.2,0,0,1)}
@keyframes dui-in{from{opacity:.4}to{opacity:1}}
@media (prefers-reduced-motion:reduce){.dui-swap{animation:none}}
`;

async function head() {
	if (!S.head) S.head = (await api('frame')).head;
	return S.head;
}

async function buildFrames() {
	const h = await head();
	const doc = (cls) => `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${h}<style id="dui-layout"></style><style>${FRAME_CSS}</style></head><body class="${cls}"><div class="wp-site-blocks"><main id="dui-page" class="wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained"></main></div></body></html>`;
	await Promise.all([
		load('dui-frame', doc('dui-canvas')),
		load('dui-phone', doc('dui-phone')),
	]);
	wireFrame();
}

function load(id, html) {
	return new Promise((resolve) => {
		const f = document.getElementById(id);
		f.addEventListener('load', () => resolve(), { once: true });
		f.srcdoc = html;
	});
}

/* Fetch whatever sections aren't cached yet. */
async function ensure(indices) {
	const missing = indices.filter((i) => !S.cache.has(key(S.spec.sections[i])));
	if (!missing.length) return;
	const res = await api('render', { method: 'POST', body: { spec: S.spec, only: missing, post_id: S.page ? S.page.id : 0, live: false } });
	res.sections.forEach((r) => S.cache.set(key(S.spec.sections[r.i]), { html: r.html, css: res.css }));
	if (res.css) S.css.add(res.css);
	S.check = res.check;
}

function applyCss() {
	const css = Array.from(S.css).join('\n');
	['dui-frame', 'dui-phone'].forEach((id) => { const d = frameDoc(id); if (d) d.getElementById('dui-layout').textContent = css; });
}

function sectionHTML(i, withTools) {
	const s = S.spec.sections[i];
	const c = S.cache.get(key(s));
	const t = D.types[s.type] || { name: s.type, layouts: {} };
	const layouts = Object.entries(t.layouts || {});
	const tools = withTools && canBuild ? `<span class="dui-tag">${esc(t.name)}</span><div class="dui-tools" role="toolbar" aria-label="${esc(t.name)} section">
		${layouts.length > 1 ? layouts.map(([k, n], j) => `<button type="button" data-layout="${k}" aria-pressed="${k === s.layout}" title="Press ${j + 1}">${esc(n)}</button>`).join('') + '<span class="sep"></span>' : ''}
		<button type="button" data-move="-1" aria-label="Move up">↑</button><button type="button" data-move="1" aria-label="Move down">↓</button><button type="button" data-remove>Remove</button></div>` : '';
	return `<div class="dui-sec ${i === S.sel && withTools ? 'is-sel' : ''}" data-i="${i}">${tools}${c ? c.html : ''}</div>`;
}

/* Draw the page. With `only`, just those sections are swapped in place. */
async function draw(only) {
	if (!S.spec) return;
	const all = S.spec.sections.map((_, i) => i);
	await ensure(only || all);
	applyCss();
	const desk = frameDoc('dui-frame');
	const phone = frameDoc('dui-phone');
	if (!desk || !phone) return;
	if (!S.spec.sections.length) {
		const empty = `<div class="dui-empty"><p>Add a section from the left to start.</p></div>`;
		desk.getElementById('dui-page').innerHTML = empty;
		phone.getElementById('dui-page').innerHTML = empty;
	} else if (only && desk.querySelectorAll('.dui-sec').length === S.spec.sections.length) {
		only.forEach((i) => {
			const d = desk.querySelector(`.dui-sec[data-i="${i}"]`);
			const p = phone.querySelector(`.dui-sec[data-i="${i}"]`);
			if (d) { d.outerHTML = sectionHTML(i, true); desk.querySelector(`.dui-sec[data-i="${i}"]`).classList.add('dui-swap'); }
			if (p) p.outerHTML = sectionHTML(i, false);
		});
	} else {
		desk.getElementById('dui-page').innerHTML = all.map((i) => sectionHTML(i, true)).join('');
		phone.getElementById('dui-page').innerHTML = all.map((i) => sectionHTML(i, false)).join('');
	}
	makeEditable();
	list();
	rules();
	htmlCheck();
}

function makeEditable() {
	const desk = frameDoc('dui-frame');
	if (!desk || !canBuild) return;
	$$('[data-dui-slot]', desk).forEach((el) => {
		el.setAttribute('contenteditable', 'true');
		el.setAttribute('spellcheck', 'true');
	});
}

function wireFrame() {
	const desk = frameDoc('dui-frame');
	desk.addEventListener('click', (e) => {
		const tool = e.target.closest('.dui-tools button');
		const sec = e.target.closest('.dui-sec');
		if (e.target.closest('a, button, summary, input, select, textarea, label') && !e.target.closest('[contenteditable]')) e.preventDefault();
		if (e.target.closest('summary')) e.preventDefault();
		if (!sec) return;
		const i = +sec.dataset.i;
		if (tool) {
			if (tool.dataset.layout) swapLayout(i, tool.dataset.layout);
			else if (tool.dataset.move) move(i, +tool.dataset.move);
			else if ('remove' in tool.dataset) remove(i);
			return;
		}
		if (i !== S.sel) select(i, false);
	});
	desk.addEventListener('submit', (e) => e.preventDefault());
	desk.addEventListener('input', (e) => {
		const el = e.target.closest('[data-dui-slot]');
		if (el) typed(el);
	});
	desk.addEventListener('keydown', (e) => {
		const el = e.target.closest('[data-dui-slot]');
		if (el && e.key === 'Enter' && !/^(lede|text|items\.\d+\.(text|a))$/.test(el.dataset.duiSlot)) { e.preventDefault(); el.blur(); }
		if (el && e.key === 'Escape') el.blur();
		keys(e);
	});
	desk.addEventListener('focusin', (e) => { if (e.target.closest('[data-dui-slot]')) S.editing = true; });
	desk.addEventListener('focusout', () => { S.editing = false; textStep = null; });
	desk.addEventListener('paste', (e) => {
		if (!e.target.closest('[data-dui-slot]')) return;
		e.preventDefault();
		desk.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text/plain'));
	});
	// Double-click a heading word to make it the bold one.
	desk.addEventListener('dblclick', (e) => {
		const h = e.target.closest('[data-dui-slot="heading"]');
		if (!h || !canBuild) return;
		const sel = desk.getSelection();
		const word = String(sel).trim().replace(/[^\p{L}\p{N}'-]/gu, '');
		if (!word) return;
		const i = +h.closest('.dui-sec').dataset.i;
		change(() => { S.spec.sections[i].bold = word; }, { redraw: false });
		h.innerHTML = boldHTML(S.spec.sections[i].heading, word);
		mirror(i, 'heading', h.innerHTML);
		note(`"${word}" is the bold word now.`);
	});
}

/* Typing on the page: update the spec in place, without redrawing. */
let textStep = null;
function typed(el) {
	const sec = el.closest('.dui-sec');
	const i = +sec.dataset.i;
	const path = el.dataset.duiSlot;
	const s = S.spec.sections[i];
	if (textStep !== `${i}:${path}`) { snapshot(); textStep = `${i}:${path}`; }
	if (path === 'heading') {
		s.heading = el.textContent.replace(/\s+/g, ' ').trim();
		const b = el.querySelector('strong, b');
		s.bold = b ? b.textContent.trim() : '';
	} else {
		setPath(s, path, el.innerText.replace(/ /g, ' ').trim());
	}
	mirror(i, path, el.innerHTML);
	S.dirty = true;
	save();
	recheck();
}

function mirror(i, path, html) {
	const p = frameDoc('dui-phone');
	const el = p && p.querySelector(`.dui-sec[data-i="${i}"] [data-dui-slot="${CSS.escape(path)}"]`);
	if (el) el.innerHTML = html;
}

/* ----------------------------------------------------- Changing things */

function snapshot() {
	S.undo.push(JSON.stringify({ spec: S.spec, title: S.title, sel: S.sel }));
	if (S.undo.length > 200) S.undo.shift();
	S.redo = [];
	buttons();
}

function change(fn, { redraw = true, only } = {}) {
	if (!canBuild) return;
	snapshot();
	fn();
	S.dirty = true;
	save();
	if (redraw) draw(only); else { list(); recheck(); }
}

function restoreFrom(stack, other) {
	if (!stack.length) return;
	other.push(JSON.stringify({ spec: S.spec, title: S.title, sel: S.sel }));
	const st = JSON.parse(stack.pop());
	S.spec = st.spec; S.title = st.title; S.sel = Math.min(st.sel, S.spec.sections.length - 1);
	$('#dui-title').value = S.title;
	S.dirty = true;
	save();
	draw();
	buttons();
}
function undo() { restoreFrom(S.undo, S.redo); }
function redo() { restoreFrom(S.redo, S.undo); }

function buttons() {
	$('#dui-undo').disabled = !S.undo.length;
	$('#dui-redo').disabled = !S.redo.length;
}

async function swapLayout(i, layout) {
	if (S.spec.sections[i].layout === layout) return;
	change(() => { S.spec.sections[i].layout = layout; }, { only: [i] });
}

function move(i, by) {
	const j = i + by;
	if (j < 0 || j >= S.spec.sections.length) return;
	change(() => {
		const [s] = S.spec.sections.splice(i, 1);
		S.spec.sections.splice(j, 0, s);
		S.sel = j;
	});
}

function remove(i) {
	const name = (D.types[S.spec.sections[i].type] || {}).name || 'section';
	change(() => { S.spec.sections.splice(i, 1); S.sel = Math.min(i, S.spec.sections.length - 1); });
	note(`Removed the ${name.toLowerCase()} section. Undo brings it back.`);
}

async function addSection(type) {
	const starter = await api('starter/' + type);
	const n = S.spec.sections.length;
	const at = S.sel >= 0 ? S.sel + 1 : (n && S.spec.sections[n - 1].type === 'cta' ? n - 1 : n);
	change(() => { S.spec.sections.splice(at, 0, starter); S.sel = at; });
	setTimeout(() => scrollTo(at), 60);
}

function select(i, scroll) {
	S.sel = i;
	const desk = frameDoc('dui-frame');
	if (desk) $$('.dui-sec', desk).forEach((el) => el.classList.toggle('is-sel', +el.dataset.i === i));
	list();
	if (scroll) scrollTo(i);
	prefetch(i);
}

function scrollTo(i) {
	['dui-frame', 'dui-phone'].forEach((id) => {
		const d = frameDoc(id);
		const el = d && d.querySelector(`.dui-sec[data-i="${i}"]`);
		if (el) el.scrollIntoView({ block: 'start', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
	});
}

/* Draw every layout of the selected section ahead of time, so a swap is instant. */
async function prefetch(i) {
	const s = S.spec && S.spec.sections[i];
	if (!s || !canBuild) return;
	const layouts = Object.keys((D.types[s.type] || {}).layouts || {});
	await Promise.all(layouts.filter((l) => l !== s.layout).map(async (l) => {
		const variant = { ...clone(s), layout: l };
		if (S.cache.has(key(variant))) return;
		const spec = clone(S.spec);
		spec.sections[i] = variant;
		try {
			const res = await api('render', { method: 'POST', body: { spec, only: [i], post_id: S.page ? S.page.id : 0 } });
			if (res.sections[0]) S.cache.set(key(variant), { html: res.sections[0].html, css: res.css });
			if (res.css) S.css.add(res.css);
		} catch (e) { /* a prefetch can fail quietly; the swap will fetch */ }
	}));
}
const prefetchSoon = debounce(() => { if (S.sel >= 0) prefetch(S.sel); }, 900);

function list() {
	if (!S.spec) return;
	$('#dui-list').innerHTML = S.spec.sections.map((s, i) => {
		const t = D.types[s.type] || { name: s.type, layouts: {} };
		const bad = S.check && S.check.findings.some((f) => f.severity === 'error' && f.where.startsWith(`sections[${i}]`));
		return `<li><button type="button" data-pick="${i}" aria-current="${i === S.sel}"><span>${esc(t.name)}${bad ? ' <i class="bang" aria-label="has a rule to fix">!</i>' : ''}</span><small>${esc((t.layouts || {})[s.layout] || '')}</small></button></li>`;
	}).join('');
}

/* ------------------------------------------------------------ Saving */

const save = debounce(async () => {
	if (!canBuild || !S.dirty || !S.spec) return;
	S.dirty = false;
	status('Saving…');
	try {
		let page;
		if (!S.page) {
			page = await api('pages', { method: 'POST', body: { title: S.title || S.spec.name, spec: S.spec } });
			history.replaceState(null, '', location.pathname + '?page=dui-canvas&post=' + page.id);
		} else {
			page = await api('pages/' + S.page.id, { method: 'PUT', body: { spec: S.spec, title: S.title, rev: S.rev } });
		}
		took(page);
		status(page.live ? (page.has_draft ? 'Saved. Not live until you publish.' : 'Saved') : 'Saved as a draft');
	} catch (e) {
		if (e.code === 'dui_plus_behind') {
			note('This page changed somewhere else (maybe your Claude). Your last change wasn\'t saved.', [['Load the new version', reload]]);
		} else {
			S.dirty = true;
			status(e.message, true);
		}
	}
}, 700);

function took(page) {
	S.page = page;
	S.rev = page.rev;
	S.check = page.check;
	S.asks = page.asks || [];
	$('#dui-preview').href = page.live && !page.has_draft ? page.link : page.preview;
	$('.mark-mini').classList.toggle('is-live', !!page.live);
	rules();
	asks();
	list();
}

function status(text, bad) {
	const el = $('#dui-saved');
	el.textContent = text;
	el.classList.toggle('bad', !!bad);
}

async function reload(asStep) {
	const page = await api('pages/' + S.page.id);
	if (asStep) snapshot();
	S.spec = page.spec;
	S.title = page.title;
	$('#dui-title').value = S.title;
	took(page);
	await draw();
}

/* ---------------------------------------------------------- The rules */

const recheck = debounce(async () => {
	try { S.check = await api('check', { method: 'POST', body: { spec: S.spec } }); rules(); list(); } catch (e) { /* keep the last check */ }
	htmlCheck();
	prefetchSoon();
}, 400);

/* The shared checker, on the drawn page: the checks only a finished page can show. */
let checkHTML = null;
async function htmlCheck() {
	try {
		if (!checkHTML) checkHTML = (await import(D.checkJs)).checkHTML;
		const desk = frameDoc('dui-frame');
		const page = desk && desk.getElementById('dui-page');
		if (!page) return;
		const copy = page.cloneNode(true);
		$$('.dui-tools, .dui-tag', copy).forEach((e) => e.remove());
		$$('[contenteditable]', copy).forEach((e) => e.removeAttribute('contenteditable'));
		const r = checkHTML(copy.innerHTML, { allowColours: [D.signature.colour, ...(D.signature.rings || [])] });
		// Only what the spec can't see, and only what a person can act on.
		S.htmlCheck = r.findings.filter((f) => ['access', 10, 12].includes(f.rule));
		rules();
	} catch (e) { S.htmlCheck = null; }
}

function findings() {
	const spec = S.check ? S.check.findings : [];
	const html = (S.htmlCheck || []).map((f) => ({ ...f, severity: 'warning' }));
	return [...spec, ...html];
}

function rules() {
	const el = $('#dui-rules');
	if (!el) return;
	const all = findings();
	const errors = all.filter((f) => f.severity === 'error');
	const warnings = all.filter((f) => f.severity !== 'error');
	const v = S.page && S.page.version ? `v${Math.floor(S.page.version / 10)}.${S.page.version % 10}` : '';
	const state = !S.page ? 'Not saved yet' : S.page.live ? (S.page.has_draft ? `Live ${v} · changes not published` : `Live ${v}`) : 'Not live yet';
	let line;
	if (errors.length) line = `<span class="bang">!</span> <b>${errors.length === 1 ? 'One rule doesn\'t hold' : errors.length + ' rules don\'t hold'}.</b> Fix ${errors.length === 1 ? 'it' : 'them'} before publishing.`;
	else line = `<span class="ok" aria-hidden="true">✓</span> <b>All 12 rules hold.</b>${warnings.length ? ` ${warnings.length} ${warnings.length === 1 ? 'suggestion' : 'suggestions'}.` : ''}`;
	el.innerHTML = `<div class="line">${line}
		${all.length ? `<button type="button" class="text" id="dui-show-rules" aria-expanded="false">Show</button>` : ''}
		${all.length && canBuild ? `<button type="button" class="ghost sm" id="dui-fix-all">Fix ${errors.length ? 'it all' : 'the suggestions'}</button>` : ''}
		<span class="grow"></span><span class="state">${esc(state)}</span>
		${canBuild ? '' : `<a class="text" href="${esc(D.settings)}">${esc(D.licence)}</a>`}</div>
		<ul class="findings" id="dui-findings" hidden>${all.map((f) => `<li class="${f.severity}"><span class="rule">${typeof f.rule === 'number' ? 'Rule ' + f.rule : esc(f.title)}</span><span><b>${esc(f.message)}</b> ${esc(f.fix)}</span>${sectionOf(f) >= 0 ? `<button type="button" class="text" data-goto="${sectionOf(f)}">Show me</button>` : ''}</li>`).join('')}</ul>`;
	const show = $('#dui-show-rules');
	if (show) show.addEventListener('click', () => { const l = $('#dui-findings'); l.hidden = !l.hidden; show.setAttribute('aria-expanded', String(!l.hidden)); show.textContent = l.hidden ? 'Show' : 'Hide'; });
	const fix = $('#dui-fix-all');
	if (fix) fix.addEventListener('click', fixAll);
	$$('[data-goto]', el).forEach((b) => b.addEventListener('click', () => select(+b.dataset.goto, true)));
	const pub = $('#dui-publish');
	pub.disabled = !canBuild || !S.page || errors.length > 0;
	pub.title = errors.length ? 'Fix the rules first' : 'Publish (Ctrl or Cmd + Enter)';
	pub.textContent = S.page && S.page.live && !S.page.has_draft ? 'Live' : 'Publish';
	if (S.page && S.page.live && !S.page.has_draft) pub.disabled = true;
}

function sectionOf(f) { const m = /^sections\[(\d+)\]/.exec(f.where || ''); return m ? +m[1] : -1; }

async function fixAll() {
	const res = await api('fix', { method: 'POST', body: { spec: S.spec } });
	if (!res.changes.length) { note('Nothing to change automatically. The suggestions need your words.'); return; }
	change(() => { S.spec = res.spec; });
	note(`Changed ${res.changes.length} ${res.changes.length === 1 ? 'thing' : 'things'}: ${res.changes.join(' ')}`);
}

/* A plain line under the bar. It stays until the next one; no toasts. */
function note(text, actions = []) {
	const el = $('#dui-note');
	el.hidden = !text;
	el.innerHTML = esc(text) + actions.map((a, k) => ` <button type="button" class="text" data-a="${k}">${esc(a[0])}</button>`).join('') + ' <button type="button" class="close" aria-label="Close">×</button>';
	actions.forEach((a, k) => el.querySelector(`[data-a="${k}"]`).addEventListener('click', () => { el.hidden = true; a[1](); }));
	el.querySelector('.close').addEventListener('click', () => { el.hidden = true; });
}

/* --------------------------------------------------------- Publishing */

async function publish() {
	if (!S.page || $('#dui-publish').disabled) return;
	await flush();
	try {
		const page = await api(`pages/${S.page.id}/publish`, { method: 'POST' });
		took(page);
		const v = `v${Math.floor(page.version / 10)}.${page.version % 10}`;
		dialog(`<div class="live">
			<span class="mark-live" style="--sig:${esc(D.signature.colour)}" aria-hidden="true"></span>
			<h2>Live, <b>${esc(v)}</b></h2>
			<p>Your page is on the web. The mark went solid; every version stays here to go back to.</p>
			<div class="acts"><a class="pill" href="${esc(page.link)}" target="_blank" rel="noopener">View the page</a><button type="button" class="ghost" data-close>Keep building</button></div></div>`);
		status('Published');
	} catch (e) {
		note(e.message);
	}
}

async function flush() {
	if (S.dirty) { await new Promise((r) => setTimeout(r, 750)); }
}

async function versions() {
	if (!S.page) { note('Versions start when you publish for the first time.'); return; }
	const v = (n) => `v${Math.floor(n / 10)}.${n % 10}`;
	const rows = (S.page.versions || []).map((x) => `<li><b>${v(x.v)}</b><span>${new Date(x.time * 1000).toLocaleString()}${x.by === 'claude' ? ' · by Claude' : ''}</span>${canBuild ? `<button type="button" class="ghost sm" data-restore="${x.v}">Bring it back</button>` : ''}</li>`).join('');
	dialog(`<h2>Your <b>versions</b></h2>
		<p class="hint">Bringing one back makes it your draft. Nothing changes on the live page until you publish.</p>
		${rows ? `<ul class="versions">${rows}</ul>` : '<p>No versions yet. The first one is made when you publish.</p>'}
		${S.page.has_draft && canBuild ? '<p><button type="button" class="text" data-discard>Throw away changes not published yet</button></p>' : ''}
		<div class="acts"><button type="button" class="ghost" data-close>Close</button></div>`);
	$$('[data-restore]').forEach((b) => b.addEventListener('click', async () => {
		snapshot();
		const page = await api(`pages/${S.page.id}/restore`, { method: 'POST', body: { version: +b.dataset.restore } });
		S.spec = page.spec; took(page); close(); draw();
		note(`Brought back ${v(+b.dataset.restore)} as your draft. Publish to put it live, or undo.`);
	}));
	const d = $('[data-discard]');
	if (d) d.addEventListener('click', async () => {
		snapshot();
		const page = await api(`pages/${S.page.id}/discard`, { method: 'POST' });
		S.spec = page.spec; took(page); close(); draw();
		note('Back to what\'s live. Undo if you didn\'t mean it.');
	});
}

/* ------------------------------------------------------------- Asking */

async function ask(e) {
	e.preventDefault();
	const text = $('#dui-ask-text').value.trim();
	if (!text) { $('#dui-ask-text').focus(); return; }
	await flush();
	if (!S.page) { await saveNow(); }
	const res = await api(`pages/${S.page.id}/ask`, { method: 'POST', body: { text, section: S.sel } });
	S.asks = res.asks;
	S.rev++;
	$('#dui-ask-text').value = '';
	asks();
	watch();
}

async function saveNow() { S.dirty = true; save(); await new Promise((r) => setTimeout(r, 900)); }

function asks() {
	const el = $('#dui-asks');
	if (!el) return;
	if (!S.asks || !S.asks.length) { el.innerHTML = ''; return; }
	const prompt = `Do my distinctUI+ asks on the page "${S.title || 'Untitled'}" (id ${S.page ? S.page.id : ''}).`;
	el.innerHTML = `<p class="waiting"><span class="dot" aria-hidden="true"></span> Waiting for your Claude: ${S.asks.length} ${S.asks.length === 1 ? 'request' : 'requests'}.</p>
		<p class="hint">In Claude, connected to this site, say:</p>
		<p class="prompt"><code>${esc(prompt)}</code> <button type="button" class="text" id="dui-copy">Copy</button></p>
		<p class="hint">You can keep building. Claude's changes arrive as one step you can undo.</p>`;
	$('#dui-copy').addEventListener('click', () => { navigator.clipboard.writeText(prompt); $('#dui-copy').textContent = 'Copied'; });
	el.insertAdjacentHTML('beforeend', `<p class="hint">Not connected yet? <a href="${esc(D.settings)}">How to connect Claude</a></p>`);
}

/* While requests wait, look for changes every few seconds. */
let watching = null;
function watch() {
	if (watching || !S.page) return;
	let wait = 3000;
	const tick = async () => {
		watching = null;
		if (!S.page || document.hidden) { watching = setTimeout(tick, wait); return; }
		try {
			const { rev } = await api(`pages/${S.page.id}/rev`);
			if (rev > S.rev && !S.dirty && !S.editing) {
				const before = S.spec.sections.length;
				await reload(true);
				note(`Your Claude changed the page${S.spec.sections.length !== before ? ` (${S.spec.sections.length - before > 0 ? 'added' : 'removed'} ${Math.abs(S.spec.sections.length - before)} section${Math.abs(S.spec.sections.length - before) === 1 ? '' : 's'})` : ''}. Undo takes it back.`, [['Undo', undo]]);
			}
		} catch (e) { /* try again */ }
		if (S.asks && S.asks.length) { wait = Math.min(wait * 1.2, 10000); watching = setTimeout(tick, wait); }
	};
	watching = setTimeout(tick, wait);
}

/* -------------------------------------------------------------- Keys */

function keys(e) {
	const mod = e.metaKey || e.ctrlKey;
	if (mod && e.key.toLowerCase() === 'z') { e.preventDefault(); e.shiftKey ? redo() : undo(); return; }
	if (mod && e.key === 'Enter') { e.preventDefault(); publish(); return; }
	const typing = S.editing || /^(INPUT|TEXTAREA|SELECT)$/.test((e.target && e.target.tagName) || '') || (e.target && e.target.isContentEditable);
	if (typing || !S.spec) return;
	if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
		e.preventDefault();
		const n = S.spec.sections.length;
		if (!n) return;
		select(Math.max(0, Math.min(n - 1, S.sel + (e.key === 'ArrowDown' ? 1 : -1))), true);
		return;
	}
	if (/^[1-9]$/.test(e.key) && S.sel >= 0) {
		const s = S.spec.sections[S.sel];
		const l = Object.keys((D.types[s.type] || {}).layouts || {})[+e.key - 1];
		if (l) swapLayout(S.sel, l);
		return;
	}
	if (e.key === 'Escape') { if (!$('#dui-scrim').hidden) close(); else select(-1, false); }
}

/* ----------------------------------------------------------- Dialogs */

function dialog(html, wide) {
	const s = $('#dui-scrim');
	s.hidden = false;
	s.innerHTML = `<div class="dlg ${wide ? 'wide' : ''}" role="dialog" aria-modal="true">${html}</div>`;
	s.onclick = (e) => { if (e.target === s || e.target.closest('[data-close]')) close(); };
	const f = s.querySelector('input, textarea, button, a');
	if (f) f.focus();
	return s.firstElementChild;
}
function close() { const s = $('#dui-scrim'); s.hidden = true; s.innerHTML = ''; }

/* --------------------------------------------------- Content first */

function startScreen() {
	const blank = () => { S.spec = { name: S.title || D.siteName, title: '', description: '', density: 'comfortable', live: false, sections: [] }; return api('starter/hero').then((h) => { S.spec.sections = [h]; S.dirty = true; save(); draw(); close(); }); };
	if (!canBuild) {
		dialog(`<h2>Building is <b>off</b></h2><p>${esc(D.licence)}</p><div class="acts"><a class="pill" href="${esc(D.settings)}">Add your licence key</a><a class="ghost" href="${esc(D.back)}">Back to your pages</a></div>`);
		return;
	}
	if (D.builder.start === 'blank') { blank(); return; }
	const d = dialog(`<h2>Your <b>content</b> first</h2>
		<p class="hint">Write it the way you'd say it. distinctUI+ does the layout, then shows you three designs that all follow the rules.</p>
		${D.otherContent ? '<p class="warn"><span class="bang">!</span> This page already has content made in the block editor. Saving here replaces it. To keep its words, ask your Claude to bring it in instead.</p>' : ''}
		<form class="cform" id="dui-cform">
			<label>Name<input name="name" value="${esc(S.title || D.siteName)}" required></label>
			<label>Main button<input name="button" value="Get started"></label>
			<label class="full">Your promise, in a few words<span>The bold word is picked for you. You can change it later.</span><input name="heading" required placeholder="Fresh bread before you're up"></label>
			<label class="full">What you do, in a sentence or two<textarea name="what" rows="2" placeholder="We bake every night and deliver in Melville before 7am."></textarea></label>
			<label>What it does<span>One per line: a title, a colon, then a line about it</span><textarea name="features" rows="4" placeholder="Baked at night: Every loaf is out of the oven by 5am.\nTo your door: Delivered before 7am, Monday to Saturday.\nNo contract: Pause or stop any week."></textarea></label>
			<label>Prices<span>One per line: name, price, what's in it. Leave empty for none.</span><textarea name="plans" rows="4" placeholder="Weekly, R95, One loaf a week\nFamily, R240, Three loaves and rolls\nOffice, R520, Bread for ten"></textarea></label>
			<label class="full">Questions people ask<span>One per line: the question with its ?, then the answer. Optional.</span><textarea name="questions" rows="3" placeholder="Can I pause? Yes, any week, from your account."></textarea></label>
			<label class="full">Who already uses it<span>Real names only, comma separated, with a line about them. Leave empty and there's no proof section.</span><input name="faces" placeholder="Naledi Dube, Kabelo Sithole"><input name="proof" placeholder="Used by 40 barbers in Soweto" aria-label="A line about them"></label>
			<label>A form<select name="form"><option value="">None</option><option value="contact">Contact</option><option value="booking">Booking request</option><option value="waitlist">Waitlist</option></select></label>
			<fieldset><legend>Also show</legend><label class="check"><input type="checkbox" name="products"> Products</label><label class="check"><input type="checkbox" name="posts"> Latest posts</label></fieldset>
			<div class="acts full"><button type="button" class="text" data-blank>Start with a blank page</button><span class="grow"></span><button class="pill" type="submit">Show me three designs</button></div>
		</form>`, true);
	d.querySelector('[data-blank]').addEventListener('click', blank);
	d.querySelector('form').addEventListener('submit', async (ev) => {
		ev.preventDefault();
		const f = new FormData(ev.target);
		const content = Object.fromEntries(['name', 'heading', 'what', 'features', 'plans', 'questions', 'form', 'faces', 'proof'].map((k) => [k, f.get(k) || '']));
		content.button = { label: f.get('button') || 'Get started', href: '#' };
		content.products = !!f.get('products');
		content.posts = !!f.get('posts');
		const submit = ev.target.querySelector('[type=submit]');
		submit.disabled = true; submit.textContent = 'Drawing three designs…';
		try { showOptions(content, await api('options', { method: 'POST', body: { content } })); }
		catch (e) { submit.disabled = false; submit.textContent = 'Show me three designs'; note(e.message); }
	});
}

async function showOptions(content, options) {
	const h = await head();
	const d = dialog(`<h2>Three designs, <b>one</b> content</h2>
		<p class="hint">Same words, same mark, three ways to lay them out. All 12 rules hold in each. Pick one to keep building; you can change any section's layout later.</p>
		<div class="options">${options.map((o, k) => `<button type="button" class="option" data-option="${k}" aria-label="Use the ${esc(o.name)} design"><span class="pv"><iframe tabindex="-1" aria-hidden="true" title=""></iframe></span><b>${esc(o.name)}</b><span>${esc(o.about)}</span></button>`).join('')}</div>
		<div class="acts"><button type="button" class="ghost" data-back>Change the content</button></div>`, true);
	$$('.option iframe', d).forEach((f, k) => {
		f.style.transform = `scale(${f.parentElement.clientWidth / 1280})`;
		f.srcdoc = `<!doctype html><html><head><meta charset="utf-8">${h}<style>${options[k].css}</style><style>body{margin:0;pointer-events:none}</style></head><body><div class="wp-site-blocks"><main class="wp-block-post-content has-global-padding is-layout-constrained">${options[k].html}</main></div></body></html>`;
	});
	d.querySelector('[data-back]').addEventListener('click', () => startScreen());
	$$('[data-option]', d).forEach((b) => b.addEventListener('click', async () => {
		const o = options[+b.dataset.option];
		if (S.spec) snapshot();
		S.spec = o.spec;
		if (!S.title) { S.title = content.name; $('#dui-title').value = S.title; }
		S.cache.clear();
		close();
		S.dirty = true;
		save();
		await draw();
		note(`${o.name} it is. Click any section to try its other layouts, or type straight on the page.`);
	}));
}

/* -------------------------------------------------------------- Start */

async function start() {
	shell();
	buttons();
	if (S.page) {
		$('#dui-preview').href = S.page.live && !S.page.has_draft ? S.page.link : S.page.preview;
	}
	if (!D.themeActive) note('The distinctUI theme isn\'t active, so this page won\'t look right on your site yet. Install it under Appearance, Themes.');
	if (S.page && S.page.edited_outside) note('This page was changed in the block editor since it was built here. Publishing from here replaces those changes.');
	await buildFrames();
	fit();
	if (!S.spec || !S.spec.sections.length) {
		rules();
		startScreen();
	} else {
		await draw();
		if (S.spec.sections.length) select(0, false);
	}
	asks();
	if (S.asks && S.asks.length) watch();
	window.addEventListener('beforeunload', (e) => { if (S.dirty || S.saving) { e.preventDefault(); e.returnValue = ''; } });
}

start().catch((e) => {
	app.innerHTML = `<div class="dui-boot"><p><b>The canvas didn't open.</b> ${esc(e.message)}</p><p>Your pages are safe. <a href="${esc(D.back)}">Back to your pages</a></p></div>`;
});
