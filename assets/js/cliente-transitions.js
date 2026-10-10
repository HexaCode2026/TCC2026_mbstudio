/**
 * MB STUDIO - TRANSIÇÕES DE TELA E ANIMAÇÕES (SOMENTE CLIENTES)
 * 
 * 1. Efeito Crossfade Direto (Dissolve Simultâneo) entre telas de clientes:
 *    - Zero FOUC: CSS e imagens da próxima tela são pré-carregados e decodificados
 *      antes de iniciar a transição.
 *    - Preservação 100% da formatação e estilos originais de cada tela.
 *    - O conteúdo antigo dissolve diretamente no novo (SEM tela preta ou desformatação).
 *    - O cabeçalho (.main-header) permanece 100% INTACTO, estático e fixo.
 * 2. Efeito de descida suave exclusivo para Index.php <-> Index.php#contato.
 */

(function () {
    if (window.__MB_CLIENTE_TRANSITIONS__) return;
    window.__MB_CLIENTE_TRANSITIONS__ = true;

    let isNavigating = false;
    let scrollAnimId = null;

    /**
     * Retorna o elemento de conteúdo da tela abaixo do cabeçalho
     */
    function getPageContentElement(doc) {
        if (!doc) doc = document;
        return doc.getElementById('page-content') ||
            doc.querySelector('.page-content-wrapper') ||
            doc.querySelector('main.servicos-page') ||
            doc.querySelector('.team-container') ||
            doc.querySelector('main');
    }

    /**
     * Verifica se o caminho corresponde à Index.php (raiz do site)
     */
    function isIndexUrl(url) {
        if (!url) return false;
        const path = (url.pathname || '').toLowerCase().replace(/\/+$/, '');
        return path.endsWith('/index.php') ||
            path.endsWith('/tcc2026_mbstudio') ||
            path === '' ||
            path === '/';
    }

    /**
     * Verifica se uma URL pertence ao escopo de páginas de cliente
     * SOMENTE essas URLs recebem o efeito de crossfade.
     * Qualquer rota de admin, funcionário, controller ou outra é navegada normalmente.
     */
    function isClientUrl(url) {
        if (!url) return false;
        if (isIndexUrl(url)) return true;
        const path = (url.pathname || '').toLowerCase();
        // Rotas permitidas: Index.php e View/cliente/
        return path.includes('/view/cliente/');
    }

    /**
     * Verifica se duas URLs apontam para a mesma página física (ignorando hash)
     */
    function isSamePage(urlA, urlB) {
        if (!urlA || !urlB) return false;
        if (urlA.origin !== urlB.origin) return false;

        if (isIndexUrl(urlA) && isIndexUrl(urlB)) {
            return true;
        }

        const pathA = (urlA.pathname || '').toLowerCase().replace(/\/+$/, '');
        const pathB = (urlB.pathname || '').toLowerCase().replace(/\/+$/, '');
        return pathA === pathB;
    }

    /**
     * Cancela animação de rolagem ativa
     */
    function cancelScrollAnimation() {
        if (scrollAnimId) {
            cancelAnimationFrame(scrollAnimId);
            scrollAnimId = null;
        }
    }

    /**
     * Rolagem animada suave com curva cúbica
     */
    function animateScrollTo(targetY, duration = 900) {
        cancelScrollAnimation();

        const startY = window.pageYOffset || document.documentElement.scrollTop;
        const distance = targetY - startY;

        if (Math.abs(distance) < 5) return;

        let startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            const elapsed = timestamp - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const ease = progress < 0.5
                ? 4 * progress * progress * progress
                : 1 - Math.pow(-2 * progress + 2, 3) / 2;

            window.scrollTo(0, startY + (distance * ease));

            if (elapsed < duration) {
                scrollAnimId = requestAnimationFrame(step);
            } else {
                scrollAnimId = null;
            }
        }

        const cancelUserInteraction = function () {
            cancelScrollAnimation();
            window.removeEventListener('wheel', cancelUserInteraction);
            window.removeEventListener('touchmove', cancelUserInteraction);
        };
        window.addEventListener('wheel', cancelUserInteraction, { passive: true, once: true });
        window.addEventListener('touchmove', cancelUserInteraction, { passive: true, once: true });

        scrollAnimId = requestAnimationFrame(step);
    }

    /**
     * Efeito de descida suave para os contatos no rodapé da Index
     */
    function smoothScrollToContato() {
        const contatoEl = document.getElementById('contato');
        if (!contatoEl) return;

        const header = document.querySelector('.main-header');
        const headerOffset = header ? header.offsetHeight : 70;
        const elementPosition = contatoEl.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop);
        const targetPosition = Math.max(0, elementPosition - headerOffset);

        animateScrollTo(targetPosition, 900);

        if (history.pushState) {
            history.pushState(null, '', '#contato');
        } else {
            location.hash = '#contato';
        }
    }

    /**
     * Efeito de subida suave para o topo da Index
     */
    function smoothScrollToTop() {
        animateScrollTo(0, 750);

        if (history.pushState) {
            history.pushState(null, '', window.location.pathname);
        }
    }

    /**
     * Sincroniza e pré-carrega as folhas de estilo da tela de destino antes da transição
     * garantindo que todo o CSS esteja 100% pronto antes de qualquer renderização.
     */
    async function preparePageStylesheets(targetDoc, targetUrl) {
        const incomingLinks = targetDoc.querySelectorAll('link[rel="stylesheet"]');
        const promises = [];

        incomingLinks.forEach(link => {
            const rawHref = link.getAttribute('href');
            if (!rawHref) return;

            const fullHref = new URL(rawHref, targetUrl.href).href;

            if (fullHref.includes('global.css') || fullHref.includes('header.css') || fullHref.includes('cliente-transitions.css')) {
                return;
            }

            const alreadyExists = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).some(l => l.href === fullHref);
            if (!alreadyExists) {
                const newLink = document.createElement('link');
                newLink.rel = 'stylesheet';
                newLink.href = fullHref;
                newLink.setAttribute('data-dynamic-page-css', 'true');

                const pr = new Promise(resolve => {
                    newLink.onload = resolve;
                    newLink.onerror = resolve;
                    setTimeout(resolve, 300);
                });
                promises.push(pr);
                document.head.appendChild(newLink);
            }
        });

        // Sincronizar style tags inline da tela de destino se houver
        const incomingStyles = targetDoc.querySelectorAll('style');
        incomingStyles.forEach(st => {
            if (!st.textContent.trim()) return;
            const alreadyPresent = Array.from(document.querySelectorAll('style[data-dynamic-page-css]')).some(
                s => s.textContent === st.textContent
            );
            if (!alreadyPresent) {
                const newSt = document.createElement('style');
                newSt.setAttribute('data-dynamic-page-css', 'true');
                newSt.textContent = st.textContent;
                document.head.appendChild(newSt);
            }
        });

        await Promise.all(promises);

        // Força o navegador a calcular o novo CSSOM
        void document.documentElement.offsetHeight;
    }

    /**
     * Limpa os estilos da tela anterior no momento exato da troca de DOM
     */
    function cleanupOldStylesheets(targetUrl, incomingLinks) {
        const isTargetHome = isIndexUrl(targetUrl);

        const homeCss = document.getElementById('theme-home-css');
        const homeInline = document.getElementById('home-inline-styles');
        if (homeCss) homeCss.disabled = !isTargetHome;
        if (homeInline) homeInline.disabled = !isTargetHome;

        const incomingFullHrefs = Array.from(incomingLinks).map(l => new URL(l.getAttribute('href'), targetUrl.href).href);
        document.querySelectorAll('link[data-dynamic-page-css]').forEach(l => {
            if (!incomingFullHrefs.includes(l.href)) {
                l.remove();
            }
        });
    }

    /**
     * Resolve caminhos relativos e pré-decodifica imagens da nova tela
     * para que nenhuma imagem pisque sem carregar.
     */
    async function prepareNewContentImages(newContent, targetUrl) {
        newContent.querySelectorAll('img[src]').forEach(img => {
            const raw = img.getAttribute('src');
            if (raw && !raw.startsWith('http') && !raw.startsWith('/') && !raw.startsWith('data:')) {
                img.src = new URL(raw, targetUrl.href).href;
            }
        });
        newContent.querySelectorAll('a[href]').forEach(a => {
            const raw = a.getAttribute('href');
            if (raw && !raw.startsWith('#') && !raw.startsWith('http') && !raw.startsWith('/') && !raw.startsWith('javascript:')) {
                a.href = new URL(raw, targetUrl.href).href;
            }
        });

        // Pré-decodifica as imagens em memória antes de exibir
        const imgPromises = Array.from(newContent.querySelectorAll('img')).map(img => {
            if (img.complete) return Promise.resolve();
            return img.decode ? img.decode().catch(() => { }) : Promise.resolve();
        });

        await Promise.all(imgPromises);
    }

    /**
     * Transição Crossfade Direta (Dissolve simultâneo sem tela preta e sem FOUC)
     */
    async function transitionToPage(targetHref, updateHistory = true) {
        if (isNavigating) return;
        isNavigating = true;

        try {
            const targetUrl = new URL(targetHref, window.location.href);

            // 1. Carrega o HTML da próxima tela em segundo plano
            const response = await fetch(targetUrl.href);
            if (!response.ok) {
                window.location.href = targetUrl.href;
                return;
            }

            const htmlText = await response.text();
            const parser = new DOMParser();
            const targetDoc = parser.parseFromString(htmlText, 'text/html');

            const newContent = getPageContentElement(targetDoc);
            const currentContent = getPageContentElement(document);

            if (!newContent || !currentContent) {
                window.location.href = targetUrl.href;
                return;
            }

            // 2. Prepara URLs e pré-decodifica imagens da nova tela
            await prepareNewContentImages(newContent, targetUrl);

            // 3. Pré-carrega o CSS da nova tela no <head> e aguarda aplicação completa
            await preparePageStylesheets(targetDoc, targetUrl);

            // 4. Aguarda fontes estarem carregadas
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            // 5. Garante um ciclo de renderização completo para o novo estilo assentar
            await new Promise(r => requestAnimationFrame(r));

            const targetTitle = targetDoc.title || document.title;
            const targetBodyClass = targetDoc.body.className;
            const targetHtmlClass = targetDoc.documentElement.className;
            const isGoingToContato = isIndexUrl(targetUrl) && (targetUrl.hash === '#contato');
            const incomingLinks = targetDoc.querySelectorAll('link[rel="stylesheet"]');

            const applyNewState = () => {
                // Remove estilos antigos apenas agora, no instante exato da troca de DOM
                cleanupOldStylesheets(targetUrl, incomingLinks);

                currentContent.replaceWith(newContent);
                document.title = targetTitle;
                document.body.className = targetBodyClass || '';
                document.documentElement.className = targetHtmlClass || '';

                if (updateHistory) {
                    history.pushState(null, '', targetUrl.href);
                }

                if (isGoingToContato) {
                    window.scrollTo(0, 0);
                    setTimeout(smoothScrollToContato, 100);
                } else {
                    window.scrollTo(0, 0);
                }

                hookAuthExecution();
            };

            // 6. Executa a View Transition suave com novo estado 100% pronto e estilizado
            if (document.startViewTransition) {
                const transition = document.startViewTransition(() => {
                    applyNewState();
                });
                await transition.finished;
            } else {
                await fallbackSimultaneousCrossfade(currentContent, newContent, applyNewState);
            }

        } catch (err) {
            console.warn('Transição assistida falhou, usando carregamento padrão:', err);
            window.location.href = targetHref;
        } finally {
            isNavigating = false;
        }
    }

    /**
     * Fallback para crossfade simultâneo quando a View Transitions API não estiver disponível
     */
    function fallbackSimultaneousCrossfade(currentEl, newEl, onComplete) {
        return new Promise((resolve) => {
            const parent = currentEl.parentNode;
            if (!parent) {
                onComplete();
                resolve();
                return;
            }

            const stage = document.createElement('div');
            stage.className = 'crossfade-stage-container';

            parent.insertBefore(stage, currentEl);
            stage.appendChild(currentEl);
            stage.appendChild(newEl);

            currentEl.style.transition = 'opacity 0.55s cubic-bezier(0.25, 1, 0.5, 1)';
            currentEl.style.opacity = '1';

            newEl.style.transition = 'opacity 0.55s cubic-bezier(0.25, 1, 0.5, 1)';
            newEl.style.opacity = '0';

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    currentEl.style.opacity = '0';
                    newEl.style.opacity = '1';

                    setTimeout(() => {
                        currentEl.remove();
                        stage.replaceWith(newEl);
                        newEl.style.transition = '';
                        newEl.style.opacity = '';
                        onComplete();
                        resolve();
                    }, 560);
                });
            });
        });
    }

    /**
     * Interceptador de cliques em links
     */
    function handleLinkClick(e) {
        const link = e.target.closest('a');
        if (!link) return;

        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;

        const rawHref = link.getAttribute('href');
        if (!rawHref || rawHref === '#' || rawHref.startsWith('javascript:')) return;

        let targetUrl;
        try {
            targetUrl = new URL(link.href, window.location.href);
        } catch (err) {
            return;
        }

        if (targetUrl.origin !== window.location.origin) return;

        const currentUrl = new URL(window.location.href);
        const currentIsIndex = isIndexUrl(currentUrl);
        const targetIsIndex = isIndexUrl(targetUrl);
        const targetIsContato = (targetUrl.hash === '#contato');

        // =========================================================================
        // PARTICULARIDADE: Index.php <-> Index.php#contato (Sem crossfade, apenas descida/subida)
        // =========================================================================
        if (currentIsIndex && targetIsIndex) {
            if (targetIsContato) {
                e.preventDefault();
                smoothScrollToContato();
                return;
            } else if (!targetUrl.hash || targetUrl.hash === '#') {
                const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
                if (currentScroll > 40 || window.location.hash) {
                    e.preventDefault();
                    smoothScrollToTop();
                    return;
                } else {
                    e.preventDefault();
                    return;
                }
            }
        }

        // Âncora na mesma página que não seja #contato
        if (isSamePage(currentUrl, targetUrl) && targetUrl.hash && !targetIsContato) {
            return;
        }

        // =========================================================================
        // SOMENTE NAVEGAÇÕES ENTRE TELAS DE CLIENTES -> CROSSFADE DIRETO
        // Qualquer link para admin, funcionário, controller, etc. navega normalmente.
        // =========================================================================
        if (!isSamePage(currentUrl, targetUrl)) {
            // Só intercepta se AMBAS as URLs forem de escopo de cliente
            if (isClientUrl(currentUrl) && isClientUrl(targetUrl)) {
                e.preventDefault();
                transitionToPage(targetUrl.href);
            }
            // Caso contrário deixa o navegador seguir normalmente (admin/funcionário)
        }
    }

    /**
     * Intercepta a função global checkAuthAndExecute para aplicar crossfade se logado
     * SOMENTE para destinos dentro do escopo de cliente (Index, View/cliente/).
     * Rotas de admin ou funcionário navegam normalmente via window.location.href.
     */
    function hookAuthExecution() {
        if (typeof window.checkAuthAndExecute === 'function' && !window.checkAuthAndExecute.__fadeHooked) {
            const originalFn = window.checkAuthAndExecute;
            window.checkAuthAndExecute = function (event, url) {
                if (event && event.preventDefault) event.preventDefault();

                if (window.isLoggedIn) {
                    // Só aplica crossfade se o destino for uma URL de cliente
                    try {
                        const destUrl = new URL(url, window.location.href);
                        if (isClientUrl(destUrl)) {
                            transitionToPage(url);
                        } else {
                            window.location.href = url;
                        }
                    } catch (e) {
                        window.location.href = url;
                    }
                } else {
                    originalFn(event, url);
                }
            };
            window.checkAuthAndExecute.__fadeHooked = true;
        }
    }

    /**
     * Trata o carregamento inicial da página com hash #contato
     */
    function handleInitialHash() {
        if (isIndexUrl(window.location) && window.location.hash === '#contato') {
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
            window.scrollTo(0, 0);
            setTimeout(function () {
                smoothScrollToContato();
            }, 250);
        }
    }

    // Inicialização ao carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            hookAuthExecution();
            handleInitialHash();
        });
    } else {
        hookAuthExecution();
        handleInitialHash();
    }

    window.addEventListener('load', function () {
        hookAuthExecution();
    });

    // Suporte ao histórico do navegador (Botões Voltar e Avançar) com crossfade direto
    // Somente para URLs de cliente; caso contrário recarrega normalmente
    window.addEventListener('popstate', function () {
        try {
            const destUrl = new URL(window.location.href);
            if (isClientUrl(destUrl)) {
                transitionToPage(window.location.href, false);
            } else {
                window.location.reload();
            }
        } catch (e) {
            window.location.reload();
        }
    });

    document.addEventListener('click', handleLinkClick);

    window.navigateWithClientFade = transitionToPage;
    window.smoothScrollToContato = smoothScrollToContato;
})();
