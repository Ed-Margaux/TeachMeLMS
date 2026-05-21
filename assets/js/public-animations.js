/**
 * Scroll-triggered section reveals (respects prefers-reduced-motion)
 */
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const selectors = [
        '.page-home .section',
        '.page-home .stats-section',
        '.km-page .km-wrap > section',
        '.km-page .km-about-intro',
        '.km-page .contact-split',
    ];

    const nodes = document.querySelectorAll(selectors.join(', '));
    if (!nodes.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('km-reveal--visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -5% 0px', threshold: 0.06 }
    );

    nodes.forEach((el) => {
        el.classList.add('km-reveal');
        observer.observe(el);
    });
})();

/**
 * Home testimonial carousel: syncs quotes with parent-photo cluster (data-src-N on images).
 */
(function () {
    document.querySelectorAll('[data-tm-carousel]').forEach((root) => {
        const slides = Array.from(root.querySelectorAll('.tm-testimonial__slide'));
        const faces = root.querySelectorAll('.tm-testimonial__face');
        const prevBtn = root.querySelector('.tm-testimonial__nav-btn--prev');
        const nextBtn = root.querySelector('.tm-testimonial__nav-btn--next');
        if (!slides.length) {
            return;
        }

        let idx = 0;
        const n = slides.length;

        function applyIndex(nextIdx) {
            idx = (nextIdx + n) % n;
            slides.forEach((el, j) => {
                const on = j === idx;
                el.classList.toggle('is-active', on);
                el.hidden = !on;
            });
            faces.forEach((img) => {
                const url = img.getAttribute(`data-src-${idx}`);
                if (url) {
                    img.setAttribute('src', url);
                }
            });
        }

        prevBtn?.addEventListener('click', () => applyIndex(idx - 1));
        nextBtn?.addEventListener('click', () => applyIndex(idx + 1));
    });
})();

/**
 * Count-up for any block with [data-stats-animate] or legacy [data-stats-strip].
 * Animates [data-stat-count] (stat-number, rating-number, km-stat-mini__num, …).
 */
(function () {
    const strips = document.querySelectorAll('[data-stats-animate], [data-stats-strip]');
    if (!strips.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function easeOutQuart(t) {
        return 1 - (1 - t) ** 4;
    }

    function formatValue(value, endRaw, suffix) {
        const endStr = String(endRaw ?? '').trim();
        const decPart = endStr.includes('.') ? endStr.split('.')[1] : '';
        const decCount = decPart ? decPart.length : 0;

        if (decCount > 0) {
            return value.toFixed(decCount) + suffix;
        }

        const n = Math.round(value);
        if (n >= 1000) {
            return n.toLocaleString('en-US') + suffix;
        }
        return String(n) + suffix;
    }

    function runCount(el, durationMs) {
        const endRaw = String(el.dataset.statCount ?? '').trim();
        const end = parseFloat(endRaw, 10);
        if (Number.isNaN(end)) {
            return;
        }
        const suffix = el.dataset.statSuffix ?? '';
        const start = performance.now();

        function frame(now) {
            const t = Math.min(1, (now - start) / durationMs);
            const eased = easeOutQuart(t);
            const current = end * eased;
            el.textContent = formatValue(current, endRaw, suffix);
            if (t < 1) {
                requestAnimationFrame(frame);
            } else {
                el.textContent = formatValue(end, endRaw, suffix);
            }
        }

        requestAnimationFrame(frame);
    }

    function bindStrip(strip) {
        const nums = strip.querySelectorAll('[data-stat-count]');
        if (!nums.length) {
            return;
        }

        function play() {
            const duration = reduceMotion ? 0 : 1400;
            nums.forEach((el, i) => {
                if (duration === 0) {
                    const endRaw = String(el.dataset.statCount ?? '').trim();
                    const end = parseFloat(endRaw, 10);
                    if (!Number.isNaN(end)) {
                        el.textContent = formatValue(end, endRaw, el.dataset.statSuffix ?? '');
                    }
                    return;
                }
                window.setTimeout(() => runCount(el, duration), i * 90);
            });
        }

        if (reduceMotion) {
            play();
            return;
        }

        const obs = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        play();
                        observer.unobserve(entry.target);
                    }
                });
            },
            { rootMargin: '0px 0px -8% 0px', threshold: 0.2 }
        );

        obs.observe(strip);
    }

    strips.forEach(bindStrip);
})();
