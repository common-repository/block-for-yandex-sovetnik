<?php
/*
* Plugin Name: Blocking for Yandex.Advisor
* Author: AntiSovet
* Author URI: antisovet.ru
* Plugin URI: https://antisovet.ru/
* Description: Blocking the Yandex.Advisor extension in all browsers and devices. Demo period 5 days.
* Version: 1.2
*
* Text Domain: antisovet
* Domain Path: /languages/
*/

if(!defined('ABSPATH')) die("FAIL!");

load_plugin_textdomain('antisovet', false, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)));

$antisovet_api_url = 'https://antisovet.ru/api-plugins/';

if(!extension_loaded('openssl')) {
	$antisovet_api_url = str_replace('https:','http:', $antisovet_api_url);
}

define("ANTISOVET_API_URL", $antisovet_api_url);
define("ANTISOVET_PLUGIN_URL", plugin_dir_url(__FILE__));
define("ANTISOVET_PLUGIN_VERSION", "1.2");

function antisovet_register_settings() {
	register_setting('antisovet', 'antisovet_email');
	register_setting('antisovet', 'antisovet_code');
	register_setting('antisovet', 'antisovet_domain');
}

function antisovet_load_plugin() {    
    load_plugin_textdomain('antisovet', false, basename(dirname(__FILE__)).'/languages/');
    
    $domain = mb_strtolower(get_site_url(),'UTF-8');    
    $domain = trim($domain);
    $domain = trim($domain,'.');
    $domain = rtrim($domain,'/');
    $domain = str_replace(array('http://', 'https://'), '', $domain);
    $domain = preg_replace('/^www\.(.+\.)/i', '$1', $domain);

    $antisovet_domain = get_option('antisovet_domain');

    if(empty($antisovet_domain)) {
    	update_option('antisovet_domain',$domain);
    } elseif($antisovet_domain !== $domain) {
    	update_option('antisovet_domain',$domain);
    	update_option('antisovet_code','');
    }
}
add_action('plugins_loaded', 'antisovet_load_plugin');

function antisovet_admin_menu() {	
	antisovet_register_settings();
	add_menu_page(__('Blocking for Yandex.Advisor','antisovet'), __('Blocking for Yandex.Advisor','antisovet'), 'manage_options', basename(__FILE__), 'antisovet_preferences', plugin_dir_url(__FILE__).'assets/icon.png');
}
add_action('admin_menu', 'antisovet_admin_menu');

function antisovet_settings_link($links) {	
	$url = esc_url(add_query_arg('page','antisovet.php', get_admin_url().'admin.php'));
	$settings_link = '<a href="'.$url.'">'.__('Settings').'</a>';
	array_push($links,$settings_link);
	return $links;
}
add_filter('plugin_action_links_antisovet/antisovet.php', 'antisovet_settings_link');

function antisovet_preferences() {
	antisovet::getInstance()->render();
}

function antisovet_register_notice() {
	//
	$antisovet_code = get_option('antisovet_code');
	if(empty($antisovet_code) && $_GET['page'] != 'antisovet.php') {
		echo
		'<div class="notice notice-warning is-dismissible"><p><span class="dashicons dashicons-warning wp-ui-text-notification" aria-hidden="true"></span> '.
		__('Block Yandex.Advisor must be activated on the','antisovet').
		' <a href="'.esc_url(add_query_arg('page','antisovet.php', get_admin_url().'admin.php')).'">'.
		__('plugin settings page','antisovet').'</a></p></div>';
	}
}
add_action('admin_notices', 'antisovet_register_notice');


function antisovet_add_script() { 
	$antisovet_code = get_option('antisovet_code');
    wp_enqueue_script('antisovet_script', '//code.antisovet.ru/'.esc_html($antisovet_code).'.js', array(), false, true);
}
add_action('wp_enqueue_scripts', 'antisovet_add_script', 9999999);

function antisovet_add_admin_style($hook) {
    if('toplevel_page_antisovet' != $hook) return;
    wp_register_style('antisovet_css', plugin_dir_url(__FILE__).'assets/antisovet.css', false, '1.0.0');
    wp_enqueue_style('antisovet_css');
}
add_action('admin_enqueue_scripts', 'antisovet_add_admin_style');

class antisovet {
	
	protected static $instance;
	private function __construct() {}    
    private function __clone() {}
    private function __wakeup() {}

	public static function getInstance() {
		if(is_null(self::$instance)) {
			self::$instance = new antisovet();
		}
		return self::$instance;
	}

	private function getResponse($query) {

    	if (extension_loaded('curl')) {
            $data = wp_remote_post(ANTISOVET_API_URL, $query);           
            if(!is_wp_error($data) && is_array($data)) return $data['body'];
            else return null;
        }

        if (ini_get('allow_url_fopen')) {
            foreach ($query['body'] as $key => $value){
                $content[$key] = $value;
            }

            return file_get_contents(ANTISOVET_API_URL, false,
                stream_context_create(
                    array(
                    	'http' => array(
                            'method' => 'POST',
                            'header' => 'Content-Type: application/x-www-form-urlencoded',
                            'content' => http_build_query($content),
                        ),
                    )
                )
            );
        }

        return null;
    }


	public function render() {
		$antisovet_code = get_option('antisovet_code');
		if(isset($_POST['email']) && empty($antisovet_code)) {
			check_admin_referer('antisovet','check_form_ref');
			$email = sanitize_email($_POST['email']);
			$query = array();
			$query['body']['domain'] = get_option('antisovet_domain');
			$query['body']['email'] = $email;
			$query['body']['type'] = 'wp';
			$query['body']['lang'] = (get_locale() == 'ru_RU' ? 'ru' : 'en');
			$data = $this->getResponse($query);

			$error_message = '';
			if(!empty($data)) {				
				$data = json_decode($data, true);
				if($data['status'] == 'error') {
					$error_message = $data['message'];
				} elseif($data['status'] == 'success' && $data['key']) {
					$code = get_option('antisovet_code');
					if(!$code) {
						update_option('antisovet_code', $data['key']);	
						$antisovet_code = $data['key'];
					}
					update_option('antisovet_email', $email);
				}
			} else $error_message = __('Generation error, please try again later','antisovet');
		}
		?>		
		<div class="wrap">
			<div class="wrap-as">
				<p id="footer-upgrade" class="alignright"><?php _e('Version','antisovet'); ?> <?php echo ANTISOVET_PLUGIN_VERSION; ?></p>
				<h1 class="wp-heading-inline">
					<img src="<?php echo ANTISOVET_PLUGIN_URL; ?>assets/logo.svg" alt="<?php echo __('Blocking for Yandex.Advisor','antisovet'); ?>"> 
				</h1>

				<?php if($error_message) { ?>
					<div class="notice notice-error inline wp-pp-notice">
						<h2><?php echo $error_message; ?></h2>								
					</div>
				<?php
				}

				if(empty($antisovet_code) && !extension_loaded('curl') && !ini_get('allow_url_fopen')) {
					require_once dirname(__FILE__)."/templates/error.php";
				} elseif(empty($antisovet_code)) {
					require_once dirname(__FILE__)."/templates/register.php";
				} else {
					require_once dirname(__FILE__)."/templates/info.php";	
				}
				?>		
			</div>
		</div>
	<?php 
	}
}
?>