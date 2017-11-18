<?php

/**
 * Generate navigation from menu.
 *
 * @param string $location machine name of the menu.
 * @param array  $args optional arguments.
 */
function timberboots_nav($location, array $args = array())
{
    $default_args = array(
    'theme_location'  => $location,
    'menu'      => '',
    'container'     => '',
    'container_class' => '',
    'menu_class'    => '',
    'menu_id'     => '',
    'echo'      => true,
    'fallback_cb'   => false,
    'before'      => '',
    'after'       => '',
    'link_before'   => '',
    'link_after'    => '',
    'items_wrap'    => '<nav class="%1$s nav--' . $location . '" role="navigation"><ul class="nav">%3$s</ul></nav>',
    'depth'       => 0,
    'walker'      => new timberboots_walker_nav_menu(),
  );
    $args += $default_args;
    wp_nav_menu($args);
}
function has_visible_widgets($sidebar_id)
{
    if (is_active_sidebar($sidebar_id)) {
        ob_start();
        dynamic_sidebar($sidebar_id);
        $sidebar = ob_get_contents();
        ob_end_clean();
        if ($sidebar == "") {
            return false;
        }
    } else {
        return false;
    }
    return true;
}
class timberboots_walker_nav_menu extends Walker_Nav_Menu
{
    // add classes to ul sub-menus
    public function start_lvl(&$output, $depth = 0, $args = array())
    {
        // depth dependent classes
    $indent = ($depth > 0  ? str_repeat("\t", $depth) : ''); // code indent
    $display_depth = ($depth + 1); // because it counts the first submenu as 0
    $classes = array(
      'nav__subnav',
      'sub-menu',
      ($display_depth >=2 ? 'sub-sub-menu' : ''),
      'menu-depth-' . $display_depth
      );
        $class_names = implode(' ', $classes);
        // build html
        $output .= "\n" . $indent . '<ul class="' . $class_names . '">' . "\n";
    }
    // add main/sub classes to li's and links
    public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
    {
        global $wp_query;
        $indent = ($depth > 0 ? str_repeat("\t", $depth) : ''); // code indent
        // depth dependent classes
        $depth_classes = array(
      ($depth == 0 ? 'main-menu__item' : 'sub-menu__item'),
      ($depth >=2 ? 'sub-sub-menu__item' : '')
      );
        $depth_class_names = esc_attr(implode(' ', $depth_classes));
        // passed classes
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $class_names = esc_attr(implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item)));
        // add active class
    if (is_array($class_names)) { // make sure the menu is an array and not empty or only a single item
      $class_names .= in_array("current_page_item", $item->classes) ? ' is-active' : '';
    }
        // build html
        $output .= $indent . '<li class="nav__item ' . $depth_class_names . $class_names .'">';
        // link attributes
        $attributes  = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
        $attributes .= ! empty($item->target)   ? ' target="' . esc_attr($item->target) .'"' : '';
        $attributes .= ! empty($item->xfn)    ? ' rel="'  . esc_attr($item->xfn) .'"' : '';
        $attributes .= ! empty($item->url)    ? ' href="'   . esc_attr($item->url) .'"' : '';
        $attributes .= ' class="nav__link ' . ($depth > 0 ? 'sub-menu__link' : 'main-menu__link') . '"';
        $item_output = sprintf(
        '%1$s<a%2$s>%3$s%4$s%5$s</a>%6$s',
      $args->before,
      $attributes,
      $args->link_before,
      apply_filters('the_title', $item->title, $item->ID),
      $args->link_after,
      $args->after
      );
        // build html
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}
// add first/last classes to menus
function add_first_and_last($output)
{
    // See if the menus have the applied nav__item class, if not the output will remain default
    // TODO: try to apply this class system to custom menus in widgets or in undefined locations
    if (preg_match('/class="nav__item/', $output)) {
        if (count($output) > 1) {
            $output = preg_replace('/class="nav__item/', 'class="first nav__item', $output, 1);
            $output = substr_replace($output, 'class="last nav__item', strripos($output, 'class="nav__item'), strlen('class="menu-item'));
        }
    }
    return $output;
}
add_filter('wp_nav_menu', 'add_first_and_last');

function register_timberboots_menu() {
  register_nav_menus( array(
    'primary' => __('Primary', 'timberboots'),
    'secondary' => __('Secondary', 'timberboots'),
    ));
}
add_action( 'init', 'register_timberboots_menu' );

// Add page slug to body class. Credit: Starkers Wordpress Theme
function add_slug_to_body_class( $classes ) {
  global $post;
  if (is_home()) {
    $key = array_search( 'blog', $classes );
    if ( $key > -1 ) {
      unset( $classes[ $key ] );
    }
  } elseif ( is_page() ) {
    $classes[] = sanitize_html_class( $post->post_name );
  } elseif ( is_singular() ) {
    $classes[] = sanitize_html_class( $post->post_name );
  }
  return $classes;
}
add_filter( 'body_class', 'add_slug_to_body_class' );


if (! class_exists('Timber')) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url(admin_url('plugins.php#timber')) . '">' . esc_url(admin_url('plugins.php')) . '</a></p></div>';
    });
    return;
}

Timber::$dirname = array('templates', 'views');

class StarterSite extends TimberSite
{
    public function __construct()
    {
        add_theme_support('post-formats');
        add_theme_support('post-thumbnails');
        add_theme_support('menus');
        add_filter('timber_context', array( $this, 'add_to_context' ));
        add_filter('get_twig', array( $this, 'add_to_twig' ));
        add_action('init', array( $this, 'register_post_types' ));
        add_action('init', array( $this, 'register_taxonomies' ));
        add_action('init', array( $this, 'load_scripts' ));
        parent::__construct();
    }

    public function register_post_types()
    {
        //this is where you can register custom post types
    }

    public function register_taxonomies()
    {
        //this is where you can register custom taxonomies
    }

    public function add_to_context($context)
    {
        $context['foo'] = 'bar';
        $context['stuff'] = 'I am a value set in your functions.php file';
        $context['notes'] = 'These values are available everytime you call Timber::get_context();';
        $context['menu'] = new TimberMenu();
        $context['site'] = $this;
        return $context;
    }

    public function load_scripts()
    {
        if (!is_admin()) {

            //JQuery
            wp_deregister_script('jquery'); //Unregister Default WP JQuery
            wp_register_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js', array(), false, true); // Google CDN jQuery
            wp_enqueue_script('jquery');

            wp_register_script('tether', 'https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js', array(), false, true);
            wp_enqueue_script('tether');

            // Bootstrap
            wp_register_script('bootstrap-js', get_template_directory_uri() .'/node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js', array('jquery'), false, true);
            wp_enqueue_script('bootstrap-js');

            // Theme
            wp_register_script('theme-js', get_stylesheet_directory_uri() .'/static/site.js', array('jquery'), false, true);
            wp_enqueue_script('theme-js');
        }
    }

    public function add_to_twig($twig)
    {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension(new Twig_Extension_StringLoader());
        $twig->addFilter('myfoo', new Twig_SimpleFilter('myfoo', array($this, 'myfoo')));
        return $twig;
    }

    public static function dump($var)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}

new StarterSite();
