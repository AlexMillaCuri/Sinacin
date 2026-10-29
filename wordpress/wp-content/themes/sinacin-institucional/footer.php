
<?php
/**
 * Footer institucional SINACIN
 */
?>

<footer class="sinacin-footer">
    <div class="container">

        <div class="sinacin-footer-grid">

            <!-- LOGO Y DESCRIPCIÓN -->
            <div class="sinacin-footer-columna">

                <a href="<?php echo esc_url(home_url('/')); ?>"
                   class="sinacin-footer-logo"
                   aria-label="SINACIN - Inicio">

                    <img
                        src="<?php echo esc_url(
                            get_template_directory_uri() .
                            '/assets/images/logo-sinacin.png'
                        ); ?>"
                        alt="SINACIN"
                        loading="lazy"
                    >
                </a>

                <p>
                    Sindicato Interempresa Nacional de la
                    Construcción Industrial y Actividades Anexas.
                </p>

                <p class="sinacin-footer-lema">
                    Unidos por los derechos de los trabajadores.
                </p>

                <!-- REDES SOCIALES -->
                <div class="sinacin-footer-redes"
                     aria-label="Redes sociales SINACIN">

                    <a href="https://www.facebook.com/"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                    </a>

                    <a href="https://www.instagram.com/"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Instagram">
                        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                    </a>

                    <a href="https://www.youtube.com/"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="YouTube">
                        <i class="fa-brands fa-youtube" aria-hidden="true"></i>
                    </a>

                    <a href="https://x.com/"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="X">
                        <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                    </a>

                    <a href="https://t.me/"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Telegram">
                        <i class="fa-brands fa-telegram" aria-hidden="true"></i>
                    </a>

                </div>
            </div>

            <!-- ENLACES RÁPIDOS -->
            <div class="sinacin-footer-columna">

                <h3>Enlaces rápidos</h3>

                <nav aria-label="Enlaces del pie de página">
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/')); ?>">Inicio</a></li>
                        <li><a href="<?php echo esc_url(home_url('/nosotros/')); ?>">Nosotros</a></li>
                        <li><a href="<?php echo esc_url(home_url('/noticias/')); ?>">Noticias</a></li>
                        <li><a href="<?php echo esc_url(home_url('/unete-con-nosotros/')); ?>">Afíliate</a></li>
                        <li><a href="<?php echo esc_url(home_url('/finiquitos/')); ?>">Finiquitos</a></li>
                    </ul>
                </nav>
            </div>

            <!-- CONTACTO -->
            <div class="sinacin-footer-columna sinacin-footer-datos">

                <h3>Contacto</h3>

                <a href="tel:+56940294993" class="sinacin-footer-dato">
                    <span class="sinacin-footer-dato-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72l.4 2.81a2 2 0 0 1-.57 1.7L8.1 11a16 16 0 0 0 4.9 4.9l1.77-1.81a2 2 0 0 1 1.7-.57l2.81.4A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </span>
                    <span>+56 9 4029 4993</span>
                </a>

                <a href="mailto:secretaria@sinacin.cl"
                   class="sinacin-footer-dato">
                    <span class="sinacin-footer-dato-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <path d="m2 6 10 7 10-7"/>
                        </svg>
                    </span>
                    <span>secretaria@sinacin.cl</span>
                </a>

                <a href="https://www.google.com/maps/search/?api=1&amp;query=Carrera+Pinto+1982+Nunoa+Chile"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="sinacin-footer-dato">
                    <span class="sinacin-footer-dato-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <span>
                        Carrera Pinto #1982, Ñuñoa,
                        Región Metropolitana
                    </span>
                </a>

            </div>

        </div>

        <!-- BARRA INFERIOR -->
        <div class="sinacin-footer-bottom">
            <p>
                © <?php echo esc_html(wp_date('Y')); ?>
                SINACIN. Todos los derechos reservados.
            </p>
            <p>Sitio web oficial de SINACIN · Chile</p>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
