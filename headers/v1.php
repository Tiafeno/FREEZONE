<header id="apus-header" class="apus-header header-v1 hidden-sm hidden-xs" role="banner">
    <?php
    $social_links = yozi_get_config('header_social_links_link');
    $social_icons = yozi_get_config('header_social_links_icon');
    ?>
    <?php if (is_active_sidebar('sidebar-topbar-left') || is_active_sidebar('sidebar-topbar-right') || !empty($social_links)) { ?>
        <!--        <div id="apus-topbar" class="apus-topbar clearfix">-->
        <!--            <div class="wrapper-large">-->
        <!--                <div class="container-fluid">-->
        <!--                    --><?php //if ( is_active_sidebar( 'sidebar-topbar-left' ) ) { ?>
        <!--                        <div class="pull-left">-->
        <!--                            <div class="topbar-left">-->
        <!--                                --><?php //dynamic_sidebar( 'sidebar-topbar-left' ); ?>
        <!--                            </div>-->
        <!--                        </div>-->
        <!--                    --><?php //} ?>
        <!--                    <div class="topbar-right pull-right">-->
        <!--                        --><?php
//                            if ( !empty($social_links) ) {
//                                ?>
        <!--                                <ul class="social-top pull-right">-->
        <!--                                    --><?php //foreach ($social_links as $key => $value) { ?>
        <!--                                        <li class="social-item">-->
        <!--                                            <a href="--><?php //echo esc_url($value); ?><!--">-->
        <!--                                                <i class="--><?php //echo esc_attr($social_icons[$key]); ?><!--"></i>-->
        <!--                                            </a>-->
        <!--                                        </li>-->
        <!--                                    --><?php //} ?>
        <!--                                </ul>-->
        <!--                                --><?php
//                            }
//                        ?>
        <!--                        --><?php //if ( is_active_sidebar( 'sidebar-topbar-right' ) ) { ?>
        <!--                            <div class="pull-right">-->
        <!--                                <div class="topbar-right-inner">-->
        <!--                                    --><?php //dynamic_sidebar( 'sidebar-topbar-right' ); ?>
        <!--                                </div>-->
        <!--                            </div>-->
        <!--                        --><?php //} ?>
        <!--                    </div>-->
        <!--                </div>-->
        <!--            </div>  -->
        <!--        </div>-->
    <?php } ?>
    <div class="wrapper-large">
        <div class="<?php echo(yozi_get_config('keep_header') ? 'main-sticky-header-wrapper' : ''); ?>">
            <div class="<?php echo(yozi_get_config('keep_header') ? 'main-sticky-header' : ''); ?>">
                <div class="container-fluid bg-header-freeezone">
                    <div class="header-middle">
                        <div class="row">
                            <div class="table-visiable-dk">
                                <div class="col-md-2">
                                    <div class="logo-in-theme ">
                                        <?php get_template_part('template-parts/logo/logo'); ?>
                                    </div>
                                </div>
                                <?php if (has_nav_menu('primary')) : ?>
                                    <div class="col-md-8">
                                        <div class="main-menu">
                                            <nav data-duration="400"
                                                 class="hidden-xs hidden-sm apus-megamenu slide animate navbar p-static"
                                                 role="navigation">
                                                <?php $args = [
                                                    'theme_location' => 'primary',
                                                    'container_class' => 'collapse navbar-collapse no-padding',
                                                    'menu_class' => 'nav navbar-nav megamenu',
                                                    'fallback_cb' => '',
                                                    'menu_id' => 'primary-menu',
                                                    'walker' => new Yozi_Nav_Menu()
                                                ];
                                                wp_nav_menu($args);
                                                ?>
                                            </nav>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <?php if (!is_user_logged_in()) { ?>
                                        <div class="login-topbar pull-right">
                                            <a class="login "
                                               href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"
                                               title="<?php esc_html_e('Sign in', 'yozi'); ?>"><?php esc_html_e('Login in /', 'yozi'); ?></a>
                                            <a class="register "
                                               href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>"
                                               title="<?php esc_html_e('Register', 'yozi'); ?>"><?php esc_html_e('Register', 'yozi'); ?></a>
                                        </div>
                                    <?php } else { ?>
                                        <?php if (has_nav_menu('top-menu')): ?>
                                            <div class="wrapper-topmenu pull-right">
                                                <div class="dropdown">
                                                    <a href="#" data-toggle="dropdown" aria-expanded="true"
                                                       role="button" aria-haspopup="true" data-delay="0"
                                                       class="btn btn-success">
                                                        <?php esc_html_e('My Account', 'yozi'); ?><span
                                                                class="caret"></span>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <?php
                                                        $args = [
                                                            'theme_location' => 'top-menu',
                                                            'container_class' => 'collapse navbar-collapse',
                                                            'menu_class' => 'nav navbar-nav topmenu-menu',
                                                            'fallback_cb' => '',
                                                            'menu_id' => 'topmenu-menu',
                                                            'walker' => new Yozi_Nav_Menu()
                                                        ];
                                                        wp_nav_menu($args);
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="header-bottom clearfix">
                    <?php if (has_nav_menu('vertical-menu')): ?>
                        <div class="col-md-2">
                            <div class="vertical-wrapper">
                                <div class="title-vertical bg-theme"><i class="fa fa-bars" aria-hidden="true"></i>
                                    <span class="text-title" style="font-size: 11px">
										<?php echo esc_html__('all Departments', 'yozi') ?>
									</span>
                                    <i class="fa fa-angle-down show-down" aria-hidden="true"></i></div>
                                <?php
                                $args = [
                                    'theme_location' => 'vertical-menu',
                                    'container_class' => 'content-vertical',
                                    'menu_class' => 'apus-vertical-menu nav navbar-nav',
                                    'fallback_cb' => '',
                                    'menu_id' => 'vertical-menu',
                                    'walker' => new Yozi_Nav_Menu()
                                ];
                                wp_nav_menu($args);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-7">
                        <?php if (yozi_get_config('show_searchform')): ?>
                            <?php get_template_part('template-parts/productsearchform'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <div class="header-right clearfix">
                            <?php if (defined('YOZI_WOOCOMMERCE_ACTIVED') && yozi_get_config('show_cartbtn') && !yozi_get_config('enable_shop_catalog')): ?>
                                <div class="pull-right">
                                    <?php get_template_part('woocommerce/cart/mini-cart-button'); ?>
                                </div>
                            <?php endif; ?>
                            <!-- Wishlist -->
                            <?php if (class_exists('YITH_WCWL')):
                                $wishlist_url = YITH_WCWL()->get_wishlist_url();
                                ?>
                                <div class="pull-right">
                                    <a class="wishlist-icon" href="<?php echo esc_url($wishlist_url); ?>"
                                       title="<?php esc_html_e('View Your Wishlist', 'yozi'); ?>"><i
                                                class="ti-heart"></i>
                                        <?php if (function_exists('yith_wcwl_count_products')) { ?>
                                            <span class="count"><?php echo yith_wcwl_count_products(); ?></span>
                                        <?php } ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php
                            $tract_order_text = yozi_get_config('header_tract_order');
                            if (!empty($tract_order_text)) { ?>
                                <div class="pull-right">
                                    <div class="header-order">
                                        <?php echo trim($tract_order_text); ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>