/**
 * Georol — Flag Color Extractor
 * Extracts dominant color from flag images via Canvas API
 * and applies dynamic CSS custom properties.
 */
(function () {
    'use strict';

    /* ── HSL util ── */
    function rgbToHsl(r, g, b) {
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s;
        const l = (max + min) / 2;
        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
                case g: h = ((b - r) / d + 2) / 6; break;
                case b: h = ((r - g) / d + 4) / 6; break;
            }
        }
        return [Math.round(h * 360), Math.round(s * 100), Math.round(l * 100)];
    }

    /* ── Core extraction (canvas, 24×16 sample) ── */
    function extractDominantColor(src, cb) {
        const canvas = document.createElement('canvas');
        canvas.width = 24; canvas.height = 16;
        const ctx = canvas.getContext('2d');

        const img = new Image();
        img.crossOrigin = 'anonymous';

        img.onload = function () {
            try {
                ctx.drawImage(img, 0, 0, 24, 16);
                const { data } = ctx.getImageData(0, 0, 24, 16);

                // Build quantised frequency map
                const freq = {};
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i], g = data[i + 1], b = data[i + 2], a = data[i + 3];
                    if (a < 100) continue;                          // skip transparent
                    if (r > 235 && g > 235 && b > 235) continue;  // skip near-white
                    if (r < 20  && g < 20  && b < 20)  continue;  // skip near-black

                    // Quantise to 32-step buckets
                    const qr = Math.round(r / 32) * 32;
                    const qg = Math.round(g / 32) * 32;
                    const qb = Math.round(b / 32) * 32;
                    const key = `${qr},${qg},${qb}`;
                    freq[key] = (freq[key] || 0) + 1;
                }

                // Most frequent bucket wins
                let bestKey = null, bestCount = 0;
                for (const [key, count] of Object.entries(freq)) {
                    if (count > bestCount) { bestCount = count; bestKey = key; }
                }

                if (bestKey) {
                    const [r, g, b] = bestKey.split(',').map(Number);
                    cb(r, g, b);
                } else {
                    cb(null);
                }
            } catch (_) { cb(null); }
        };

        img.onerror = function () { cb(null); };
        img.src = src;
    }

    /* ── Public API ── */

    /**
     * applyFlagColor(imgSrc, target?, opts?)
     *
     * opts.asAccent   — override --accent / --accent-glow too
     * opts.pageBlur   — set imgSrc as full-page blurred background (default: true)
     * opts.onApply    — callback(r,g,b,h,s,l)
     */
    window.applyFlagColor = function (imgSrc, target, opts) {
        if (!imgSrc) return;
        opts = opts || {};
        const el = target || document.documentElement;

        // ── Page background blur layer ──
        const applyPageBlur = opts.pageBlur !== false; // true by default
        if (applyPageBlur) {
            const layer = document.getElementById('pageBgLayer');
            if (layer) {
                layer.style.backgroundImage = `url('${imgSrc}')`;
                layer.classList.add('active');
            }
        }

        extractDominantColor(imgSrc, function (r, g, b) {
            if (r === null) return;

            const [h, s, l] = rgbToHsl(r, g, b);

            // Clamp: ensure the colour is vivid and readable
            const sat = Math.max(s, 45);
            const lit = Math.min(Math.max(l, 28), 62);

            const col      = `hsl(${h},${sat}%,${lit}%)`;
            const colLight = `hsl(${h},${sat}%,${Math.min(lit + 22, 85)}%)`;
            const colDim   = `hsla(${h},${sat}%,${lit}%,0.13)`;
            const colGlow  = `hsla(${h},${sat}%,${lit}%,0.30)`;
            const colBorder= `hsla(${h},${sat}%,${lit}%,0.40)`;

            el.style.setProperty('--flag-color',        col);
            el.style.setProperty('--flag-color-light',  colLight);
            el.style.setProperty('--flag-color-dim',    colDim);
            el.style.setProperty('--flag-color-glow',   colGlow);
            el.style.setProperty('--flag-color-border', colBorder);
            el.style.setProperty('--flag-h', h);
            el.style.setProperty('--flag-s', sat + '%');
            el.style.setProperty('--flag-l', lit + '%');

            if (opts.asAccent) {
                el.style.setProperty('--accent',      col);
                el.style.setProperty('--accent-glow', colGlow);
            }

            if (typeof opts.onApply === 'function') {
                opts.onApply(r, g, b, h, sat, lit);
            }
        });
    };

    /**
     * applyFlagColorToEl(imgEl, containerEl, opts?)
     * Convenience: takes an <img> element directly.
     */
    window.applyFlagColorToEl = function (imgEl, containerEl, opts) {
        if (!imgEl || !imgEl.src) return;
        window.applyFlagColor(imgEl.src, containerEl, opts);
    };

    /* ── Auto-init for data-flag-target images ──
       Add data-flag-target="#selector" to any <img class="flag-auto">
       and the color will be applied to that selector's element.
    ── */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('img.flag-auto').forEach(function (img) {
            const targetSel = img.dataset.flagTarget;
            const asAccent  = img.dataset.flagAccent === 'true';
            const target    = targetSel ? document.querySelector(targetSel) : null;

            function run() {
                window.applyFlagColorToEl(img, target, { asAccent: asAccent });
            }

            if (img.complete && img.naturalWidth > 0) run();
            else img.addEventListener('load', run);
        });
    });
})();
