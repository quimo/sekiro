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

    function __construct() {
        	
        add_action('wp_enqueue_scripts', array($this, 'init'));     	
        add_action('admin_menu', array($this, 'add_settings_page'));      	

        /* azioni ajax */
        add_action('wp_ajax_nopriv_hello_world_ajax', array($this, 'hello_world_ajax'));
        add_action('wp_ajax_hello_world_ajax', array($this, 'hello_world_ajax'));

        /* attivazione e disattivazione plugin */
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook( __FILE__, array($this, 'deactivation'));
    }

    function activation(){
        $this->add_settings();
    }

    function deactivation(){
        $this->remove_settings();
	  }

    function init() {
        wp_enqueue_style( 'sekiro', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' , array(), mt_rand());
        wp_enqueue_script('sekiro', plugin_dir_url( __FILE__ ) . 'assets/js/sekiro.js', array('jquery'), mt_rand(), true);
        wp_localize_script('init', 'init_ajax', array('url' => admin_url( 'admin-ajax.php' )));
    }

    function hello_world_ajax() {
        echo json_encode(array('Hello', 'world'));
		wp_die();
    }

    function add_settings_page() {
        add_options_page(
            'My custom settings page',
            'Custom settings page',
            'manage_options',
            'cabi-settings-page',
            array($this,'render_settings_page')
        );
    }

    function add_settings() {
        //add_option('key', 'value');
    }

    function remove_settings() {
        //delete_option('key');
    }

    function render_settings_page() {
        if (!current_user_can('manage_options')) wp_die('Non possiedi i permessi per accedere a questa pagina');
        ?>
        <div class="wrap">
            <h2>Setting title</h2>
            <?php
            if (isset($_POST['submit']) && wp_verify_nonce($_POST['modify_settings_nonce'], 'modify_settings')) {
                //update_option('key', 'value');
            }
            ?>
            <form method="post">
                <?php wp_nonce_field('modify_settings', 'modify_settings_nonce') ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

}

new Sekiro();
