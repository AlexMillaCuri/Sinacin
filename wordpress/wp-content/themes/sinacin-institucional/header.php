
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="sinacin-header">

    <div class="sinacin-topbar">
        <div class="container">
            <span>Sitio web oficial de SINACIN</span>
            <span>Chile</span>
        </div>
    </div>

    <div class="sinacin-navbar">
        <div class="container sinacin-navbar-inner">

            <a class="sinacin-brand"
               href="<?php echo esc_url(home_url('/')); ?>"
               aria-label="SINACIN - Inicio">

                <img
                    src="<?php echo esc_url(
                        get_template_directory_uri() .
                        '/assets/images/logo-sinacin.png'
                    ); ?>"
                    alt="SINACIN"
                >
            </a>

            <button
                class="sinacin-menu-toggle"
                type="button"
                aria-label="Abrir menú"
                aria-expanded="false"
                aria-controls="sinacin-menu"
            >
                ☰
            </button>

            <nav
                id="sinacin-menu"
                class="sinacin-navigation"
                aria-label="Navegación principal"
            >
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'menu-principal',
                    'container'      => false,
                    'menu_class'     => 'sinacin-menu',
                    'fallback_cb'    => false
                ));
                ?>
            </nav>

        </div>
    </div>

</header>
