<?php
/**
 * Plugin Name: Sekiro
 * Plugin URI: https://github.com/quimo/sekiro
 * Description: Area riservata
 * Version: 0.8
 * Author: Simone Alati
 * Author URI: https://www.simonealati.it
 * Text Domain: sekiro
 */

 // termino l'esecuzione se il plugin è richiamato direttamente
 if (!defined('WPINC')) die;

class Sekiro {

    private $data;

    function __construct() {
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));     	
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_shortcode('sekiro_area_riservata','render_docs');      	

        /* attivazione e disattivazione plugin */
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook( __FILE__, array($this, 'deactivation'));

        /* impostazione del ruolo "nullo" come default all'iscrizione */
        add_filter('pre_option_default_role', function ($default_role) {
            return '';
        });

        /* redirect al login per gli utenti 'subscriber' */
        add_filter('login_redirect', function($url, $request, $user) {
            if ($user && is_object($user) && is_a($user, 'WP_User')) {
                if ($user->has_cap('subscriber') && get_option('sekiro_url_area_riservata') != '') {
                    $url = home_url('/' . basename(get_option('sekiro_url_area_riservata')) . '/');
                } else {
                    $url = admin_url();
                }
            }
            return $url;
        }, 10, 3 );

    }

    function activation(){
        $this->add_settings();
    }

    function deactivation(){
        $this->remove_settings();
    }
    
    function init() {
        $page = str_replace('/', '', $_SERVER['REQUEST_URI']);
        if ($page == basename(get_option('sekiro_url_area_riservata')) && !is_user_logged_in()) {
            wp_redirect(home_url());
            exit();
        }
    }

    function enqueue() {
        wp_enqueue_style( 'sekiro', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' , array(), mt_rand());
        wp_enqueue_script('sekiro', plugin_dir_url( __FILE__ ) . 'assets/js/sekiro.js', array('jquery'), mt_rand(), true);
    }

    function hello_world_ajax() {
        echo json_encode(array('Hello', 'world'));
		wp_die();
    }

    function add_settings_page() {
        add_options_page(
            'Sekiro',
            'Sekiro',
            'manage_options',
            'cabi-settings-page',
            array($this,'render_settings_page')
        );
    }

    function add_settings() {
        add_option('sekiro_url_area_riservata', '');
    }

    function remove_settings() {
        delete_option('sekiro_url_area_riservata');
    }

    function render_settings_page() {
        if (!current_user_can('manage_options')) wp_die('Non possiedi i permessi per accedere a questa pagina');
        ?>
        <div class="wrap">
            <h2>Sekiro | Impostazioni</h2>
            <?php
            if (isset($_POST['submit']) && wp_verify_nonce($_POST['modify_settings_nonce'], 'modify_settings')) {
                if ($_POST['sekiro_url']) {
                    update_option('sekiro_url_area_riservata', $_POST['sekiro_url']);
                }
            }
            ?>
            <form method="post">
                <label>Pagina area riservata</label><br>
                <select name="sekiro_url" id="sekiro_url">
                    <option value="">Scegli una pagina...</option>
                    <?php echo $this->render_options() ?>
                </select>
                <?php wp_nonce_field('modify_settings', 'modify_settings_nonce') ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function render_options() {
        $query = new WP_query (array(
            'post_type' => 'page',
            'nopaging' => true,
            'orderby' => 'post_title',
            'order' => 'ASC'
        ));
        if ($query->have_posts()) {
            $options = '';
            while ($query->have_posts()) {
                $query->the_post();
                $options .= '<option';
                if (get_the_permalink() == get_option('sekiro_url_area_riservata')) $options .= ' selected="selected"';
                $options .= ' value="' . get_the_permalink() . '">' . get_the_title() . '</option>';
            }
        }
        wp_reset_query();
        wp_reset_postdata();
        return $options;
    }

    function get_data() {
        /* recupero i dati dal pod */
        $this->data = '';
    }


    function render_docs($atts, $content = null) {
        extract(
            shortcode_atts(
                array(
                    'cat' => '',
                ), 
                $atts, 'sekiro_area_riservata'
            ) 
        );
        //$data = $this->get_data();
    }

}

new Sekiro();