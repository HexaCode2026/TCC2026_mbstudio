<?php
require_once __DIR__ . '/../../core/Session.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$footerBasePath = Session::getBasePath();
?>
<!-- =================================================================
     RODAPÉ GLOBAL DE ALTO PADRÃO (FOOTER)
     ================================================================= -->
<footer class="salon-footer" id="contato">
    <div class="footer-grid">
        <div class="footer-col">
            <h3 class="footer-brand-heading">
                <span class="footer-brand-star" aria-hidden="true">✦</span> MB STUDIO
            </h3>
            <p>
                Referência em sofisticação, visagismo e cuidados com a beleza feminina. Um espaço feito para
                valorizar a sua autoestima com excelência e carinho.
            </p>
            <div class="footer-social-links">
                <a href="https://www.instagram.com/maison.bellestudio/" target="_blank" rel="noopener noreferrer" class="social-icon-btn"
                    title="Instagram" aria-label="Instagram do MB Studio">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </a>
                <a href="https://wa.me/5511991075433?text=Olá, gostaria de agendar um horário" target="_blank" rel="noopener noreferrer"
                    class="social-icon-btn" title="WhatsApp" aria-label="WhatsApp do MB Studio">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z">
                        </path>
                    </svg>
                </a>
            </div>
        </div>

        <div class="footer-col">
            <h3>Navegação</h3>
            <ul class="footer-links">
                <li><a href="<?= $footerBasePath ?>/Index.php">Início</a></li>
                <li><a href="<?= $footerBasePath ?>/View/cliente/Servicos.php">Nossos Serviços</a></li>
                <li><a href="<?= $footerBasePath ?>/View/cliente/Funcionarios.php">Nossa Equipe</a></li>
                <li><a href="#" onclick="checkAuthAndExecute(event, '<?= $footerBasePath ?>/View/cliente/Servicos.php')">Agendamento Online</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h3>Horário de Funcionamento</h3>
            <p class="footer-label"><strong>Terça a Sexta:</strong></p>
            <p class="footer-highlight footer-highlight-gap">09h00 às 19h00</p>
            <p class="footer-label"><strong>Sábados:</strong></p>
            <p class="footer-highlight">08h30 às 18h00</p>
        </div>

        <div class="footer-col">
            <h3>Localização</h3>
            <p>
                Rua Mangaratiba, 449 - Vila Santo Antônio<br>
                São Paulo - SP
            </p>
            <p class="footer-highlight footer-contact-gap">
                (11) 99107-5433<br>
                cidapedroso@terra.com.br
            </p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> MB Studio. Todos os direitos reservados.</p>
        <p>Elegância e Beleza em Cada Detalhe.</p>
    </div>
</footer>
