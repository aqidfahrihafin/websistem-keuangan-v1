import { Chart, registerables } from 'chart.js';
import { toPng } from 'html-to-image';

Chart.register(...registerables);

// Exposed globally so per-component @script blocks in Livewire views (e.g.
// the admin dashboard's charts) can call `new Chart(...)` without each one
// needing its own ES module import - those blocks run as plain inline
// <script> tags, not modules.
window.Chart = Chart;

// Renders a DOM element to a PNG and triggers a browser download - used by
// the kantin QR card's "Unduh Gambar" button. html-to-image (not
// html2canvas) specifically because it serializes the actual DOM via an SVG
// foreignObject rather than manually redrawing each element, which handles
// the card's nested QR/pattern <svg> content reliably - html2canvas has
// long-standing issues rendering nested SVGs.
window.downloadElementAsImage = async function (el, filename) {
    if (!el) return;

    // Let a moment pass for any x-transition (modal scale/opacity) to
    // finish settling before measuring/capturing.
    await new Promise((resolve) => setTimeout(resolve, 100));

    // Deliberately getBoundingClientRect(), NOT scrollWidth/scrollHeight.
    // el has overflow-hidden (its rounded corners clip content), but
    // scrollWidth/scrollHeight measure the full unclipped extent of its
    // content - when that's larger than the visible box (e.g. an
    // absolutely-positioned decorative element sitting a few px past an
    // edge), passing it as toPng's width/height renders a canvas sized to
    // that larger, uncropped extent, pushing the actually-visible card into
    // a smaller portion of it - this is exactly what read as a "cropped"
    // download (see tsayen/dom-to-image#50, the same underlying rendering
    // path html-to-image is built on). getBoundingClientRect() is the
    // element's own rendered box - already correctly clipped.
    const rect = el.getBoundingClientRect();

    const dataUrl = await toPng(el, {
        pixelRatio: 2,
        cacheBust: true,
        width: Math.ceil(rect.width),
        height: Math.ceil(rect.height),
        style: { transform: 'none' },
    });

    const link = document.createElement('a');
    link.download = filename;
    link.href = dataUrl;
    link.click();
};

// Keep dense table actions compact like the Apins Digital admin. The visible
// label becomes a tooltip/accessible name, while Livewire's original button
// or link (and all wire:* handlers) stays intact.
const tableActionIcons = {
    edit: '<path stroke-linecap="round" stroke-linejoin="round" d="m16.86 3.49 3.65 3.65M5 19l3.82-.76L19.75 7.31a2.58 2.58 0 0 0-3.65-3.65L5.18 14.58 5 19Z"/>',
    delete: '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5m4-5v5"/>',
    view: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/>',
    download: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 20h14"/>',
    print: '<path stroke-linecap="round" stroke-linejoin="round" d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-10-3h10v7H7v-7Z"/>',
    sync: '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7v5h-5M4 17v-5h5m10.1-3A8 8 0 0 0 6 6l-2 2m16 8-2 2a8 8 0 0 1-13.1-3"/>',
    toggle: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v9m6.36-6.36a9 9 0 1 1-12.72 0"/>',
    wallet: '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4H19v16H6.5A2.5 2.5 0 0 1 4 17.5v-11ZM4 8h15m-5 4h5v4h-5a2 2 0 1 1 0-4Z"/>',
    user: '<path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10-3v6m3-3h-6"/>',
    default: '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8h.01"/>',
};

function actionIconFor(label) {
    const value = label.toLowerCase();
    if (/hapus|batalkan|tolak/.test(value)) return ['delete', true];
    if (/ubah|edit/.test(value)) return ['edit', false];
    if (/unduh|invoice|kwitansi|bukti/.test(value)) return ['download', false];
    if (/cetak|preview/.test(value)) return ['print', false];
    if (/detail|lihat|panduan/.test(value)) return ['view', false];
    if (/sinkron|sync|pulihkan/.test(value)) return ['sync', false];
    if (/bayar|setoran|penarikan|proses/.test(value)) return ['wallet', false];
    if (/akun|wali/.test(value)) return ['user', false];
    if (/aktif|nonaktif|verifikasi|setujui/.test(value)) return ['toggle', false];
    return ['default', false];
}

function enhanceTableActions(root = document) {
    const selector = '.table-card .btn-link:not([data-action-icon]), .table-card .btn-link-danger:not([data-action-icon])';
    const elements = [];

    // Livewire sometimes replaces the action itself instead of its parent.
    // querySelectorAll() does not include the root node, so check both.
    if (root instanceof Element && root.matches(selector)) {
        elements.push(root);
    }
    if (root.querySelectorAll) {
        elements.push(...root.querySelectorAll(selector));
    }

    elements.forEach((element) => {
        const label = element.textContent.replace(/\s+/g, ' ').trim();
        if (!label) return;

        const [icon, destructive] = actionIconFor(label);
        element.dataset.actionIcon = icon;
        element.title = label;
        element.setAttribute('aria-label', label);
        element.classList.add('table-action-icon');
        if (destructive || element.classList.contains('btn-link-danger')) {
            element.classList.add('table-action-danger');
        }
        element.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">${tableActionIcons[icon]}</svg>`;
    });
}

function refreshTableActions() {
    enhanceTableActions();
    // Cached history and Livewire morphs can finish immediately after their
    // navigation event. Recheck after layout so Back/Forward stays icon-only.
    requestAnimationFrame(() => {
        enhanceTableActions();
        requestAnimationFrame(() => enhanceTableActions());
    });
}

document.addEventListener('DOMContentLoaded', refreshTableActions);
document.addEventListener('livewire:navigated', refreshTableActions);
window.addEventListener('pageshow', refreshTableActions);
window.addEventListener('popstate', refreshTableActions);
new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
            if (node.nodeType === Node.ELEMENT_NODE) enhanceTableActions(node);
        }
    }
}).observe(document.documentElement, { childList: true, subtree: true });
