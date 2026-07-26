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
