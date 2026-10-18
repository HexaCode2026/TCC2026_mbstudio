/**
 * ============================================================================
 * CONTROLLER: Hero Slideshow — Rotação Automática (4s) + Botões Laterais
 * ============================================================================
 * Opera diretamente sobre o DOM vivo (#hero-slideshow), garantindo funcionamento
 * imediato nos cliques dos botões laterais, dots, swipe mobile e transições AJAX.
 */
(function () {
    'use strict';

    var INTERVAL_MS = 4000;
    var activeTimer = null;

    function getActiveSlideIndex(slides) {
        for (var i = 0; i < slides.length; i++) {
            if (slides[i].classList.contains('active')) {
                return i;
            }
        }
        return 0;
    }

    function applySlideByIndex(targetIndex) {
        var container = document.getElementById('hero-slideshow');
        if (!container) return;

        var slides = container.querySelectorAll('.hero-slide');
        if (!slides || slides.length <= 1) return;

        var total = slides.length;
        var nextIdx = ((targetIndex % total) + total) % total;

        for (var i = 0; i < total; i++) {
            if (i === nextIdx) {
                slides[i].classList.add('active');
            } else {
                slides[i].classList.remove('active');
            }
        }

        var dots = container.querySelectorAll('.slideshow-dot');
        for (var d = 0; d < dots.length; d++) {
            if (d === nextIdx) {
                dots[d].classList.add('active');
            } else {
                dots[d].classList.remove('active');
            }
        }

        var labelName = container.querySelector('.slide-label-name');
        var labelPrice = container.querySelector('.slide-label-price');
        if (labelName) {
            labelName.textContent = slides[nextIdx].getAttribute('data-name') || '';
        }
        if (labelPrice) {
            labelPrice.textContent = slides[nextIdx].getAttribute('data-price') || '';
        }
    }

    function startAutoTimer() {
        if (activeTimer) {
            clearInterval(activeTimer);
            activeTimer = null;
        }
        activeTimer = setInterval(function () {
            var container = document.getElementById('hero-slideshow');
            if (!container) return;
            if (container.matches(':hover')) return;
            var slides = container.querySelectorAll('.hero-slide');
            if (!slides || slides.length <= 1) return;
            var current = getActiveSlideIndex(slides);
            applySlideByIndex(current + 1);
        }, INTERVAL_MS);
    }

    window.heroSlideStep = function (step, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var container = document.getElementById('hero-slideshow');
        if (!container) return;
        var slides = container.querySelectorAll('.hero-slide');
        if (!slides || slides.length <= 1) return;

        var current = getActiveSlideIndex(slides);
        applySlideByIndex(current + step);
        startAutoTimer();
    };

    window.heroSlideGoTo = function (index, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        applySlideByIndex(index);
        startAutoTimer();
    };

    window.initHeroSlideshow = function () {
        var container = document.getElementById('hero-slideshow');
        if (!container) return;

        var slides = container.querySelectorAll('.hero-slide');
        if (!slides.length) return;

        // Garante que os dots existam caso não tenham vindo do servidor
        var dotsWrap = container.querySelector('.slideshow-dots');
        if (dotsWrap && dotsWrap.children.length !== slides.length && slides.length > 1) {
            dotsWrap.innerHTML = '';
            for (var i = 0; i < slides.length; i++) {
                (function (idx) {
                    var dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'slideshow-dot' + (idx === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', 'Ir para imagem ' + (idx + 1));
                    dot.onclick = function (e) {
                        window.heroSlideGoTo(idx, e);
                    };
                    dotsWrap.appendChild(dot);
                })(i);
            }
        }

        // Suporte a swipe touch no celular
        if (!container.__swipeBound) {
            var touchStartX = 0;
            container.addEventListener('touchstart', function (e) {
                if (e.changedTouches && e.changedTouches.length > 0) {
                    touchStartX = e.changedTouches[0].screenX;
                }
            }, { passive: true });

            container.addEventListener('touchend', function (e) {
                if (e.changedTouches && e.changedTouches.length > 0) {
                    var diffX = e.changedTouches[0].screenX - touchStartX;
                    if (Math.abs(diffX) > 40) {
                        window.heroSlideStep(diffX < 0 ? 1 : -1);
                    }
                }
            }, { passive: true });

            container.__swipeBound = true;
        }

        startAutoTimer();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initHeroSlideshow);
    } else {
        window.initHeroSlideshow();
    }
})();
