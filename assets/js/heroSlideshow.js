/**
 * ============================================================================
 * CONTROLLER: Hero Slideshow — Rotação Automática de Cortes em Destaque
 * ============================================================================
 * Gerencia o carrossel de imagens na seção hero da Index.php.
 * Troca automaticamente a cada 5 segundos com transição suave (crossfade).
 * Exibe nome do serviço, preço e indicadores de navegação (dots).
 */
(function () {
    'use strict';

    // ── Configuração ──
    const INTERVAL_MS = 5000;       // Intervalo de troca (5 segundos)
    const FADE_DURATION_MS = 800;   // Duração da transição CSS (sincronizado com o CSS inline)

    // ── Elementos DOM ──
    const container = document.getElementById('hero-slideshow');
    if (!container) return;

    const slides = container.querySelectorAll('.hero-slide');
    const dotsWrap = container.querySelector('.slideshow-dots');
    const labelName = container.querySelector('.slide-label-name');
    const labelPrice = container.querySelector('.slide-label-price');

    if (!slides.length) return;

    let currentIndex = 0;
    let timer = null;

    // ── Criar indicadores (dots) ──
    if (dotsWrap) {
        slides.forEach(function (_slide, i) {
            var dot = document.createElement('button');
            dot.className = 'slideshow-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', 'Ir para imagem ' + (i + 1));
            dot.addEventListener('click', function () {
                if (i !== currentIndex) {
                    goToSlide(i);
                    resetTimer();
                }
            });
            dotsWrap.appendChild(dot);
        });
    }

    var dots = dotsWrap ? dotsWrap.querySelectorAll('.slideshow-dot') : [];

    // ── Funções de Controle ──
    function goToSlide(index) {
        // Desativa slide atual
        slides[currentIndex].classList.remove('active');
        if (dots[currentIndex]) dots[currentIndex].classList.remove('active');

        // Ativa novo slide
        currentIndex = index;
        slides[currentIndex].classList.add('active');
        if (dots[currentIndex]) dots[currentIndex].classList.add('active');

        // Atualiza label de texto
        if (labelName) {
            labelName.textContent = slides[currentIndex].getAttribute('data-name') || '';
        }
        if (labelPrice) {
            labelPrice.textContent = slides[currentIndex].getAttribute('data-price') || '';
        }
    }

    function nextSlide() {
        var next = (currentIndex + 1) % slides.length;
        goToSlide(next);
    }

    function startTimer() {
        timer = setInterval(nextSlide, INTERVAL_MS);
    }

    function resetTimer() {
        clearInterval(timer);
        startTimer();
    }

    // ── Pausar ao passar o mouse por cima ──
    container.addEventListener('mouseenter', function () {
        clearInterval(timer);
    });

    container.addEventListener('mouseleave', function () {
        startTimer();
    });

    // ── Inicializar ──
    // Garantir que o primeiro slide está ativo
    if (!slides[0].classList.contains('active')) {
        slides[0].classList.add('active');
    }
    if (labelName) {
        labelName.textContent = slides[0].getAttribute('data-name') || '';
    }
    if (labelPrice) {
        labelPrice.textContent = slides[0].getAttribute('data-price') || '';
    }

    startTimer();

})();
