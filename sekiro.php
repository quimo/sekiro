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

    private $data = array();
    private $data_counter = 0;

    function __construct() {
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));     	
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_shortcode('sekiro_area_riservata', array($this, 'render_docs'));      	

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

    /** get_data
     *  recupero i dati dal pod 'documenti'
     */
    function get_data() {

        $logged_user = get_userdata(get_current_user_id());
        $user_email = $logged_user->user_email;
        
        $query = new WP_query (array(
            'nopaging' => 'true',
            'posts_per_page' => -1,
            'post_type' => 'documento'
        ));

        if ($query->have_posts()) {
            $k = 0;
            while ($query->have_posts()) {
                $query->the_post();
                $documento = pods('documento', get_the_id()); // recupero il pod
                $allegato = $documento->display('sekiro_allegato');
                $utenti = $documento->field('sekiro_utenti'); // recupero la relazione
                if (!empty($utenti)) {
                    for ($i = 0; $i < count($utenti); $i++) {
                        $email = $utenti[$i]['user_email'];
                        if ($user_email == $email) {
                            //salvo il dato
                            $this->data[$k]['post_title'] = get_the_title();
                            $this->data[$k]['post_content'] = get_the_content();
                            $this->data[$k]['sekiro_allegato'] = $allegato;
                            $k++;
                            break;
                        }
                    }

                }
            }
        }
        wp_reset_query();
        wp_reset_postdata();
        return $this->data;
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
        ob_start();
        $data = $this->get_data();
        if (!empty($data)) {
            $html = '<div class="sekiro">';
            $tmpl_url = plugin_dir_url( __FILE__ ) . 'assets/templates/sekiro.html';
            $template = @file_get_contents($tmpl_url);
            for ($i = 0; $i < count($data); $i++) {
                $this->data_counter = $i;
                $item = $template;
                $item = preg_replace_callback('/\[\+(.+)\+\]/', function($matches) {
                    $dummy = $matches[1];
                    return $this->data[$this->data_counter][$dummy];
                }, $item);
                $html .= $item;
            }

        }
        echo $html . '</div>';
		return ob_get_clean();
        
    }

}

new Sekiro();