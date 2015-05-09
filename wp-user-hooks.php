<?php
/**
 * @package Engine
 * @version 0
 */
/*
Plugin Name: Engine
Plugin URI: 
Description: Plugin to connect with Engine
Author: Vitali Ananiev
Version: 0.1
Author URI: 
*/
if(defined('IN_ENGINE')) return;							// если работаем с движком, то не выполняем плагин
// это хук, почему-то при активации плагина возникает ошибка, если подключать "before.php", избавимся от нее
if(strpos($_SERVER['REQUEST_URI'], '/wp-admin/plugins.php')) return;

include_once( $_SERVER['DOCUMENT_ROOT']. '/../before.php');

class Engine {
	private $generate_password;
	function Engine(){
		$generate_password = NULL;
		
		//add_filter('logout_url', array(&$this, 'logout_url')); 
		//add_filter('login_url', array(&$this, 'login_url')); 
		add_action('wp_logout', array(&$this, 'auto_logout'));
		add_action('wp_login', array(&$this, 'after_login'), 10, 2);		// порядок вызова ==10, передаем аргументов==2
		add_action('set_current_user', array(&$this, 'set_current_user'));	// иногда вместо логина выполняется эта функция
		add_action('admin_menu', array(&$this, 'plugin_menu'));
		add_action('user_register', array(&$this, 'register_user'));
		add_action('profile_update', array(&$this, 'update_user'));
		add_filter('random_password', array(&$this, 'reg_password'), 999);	// стараемся вызвать самым последним ==999
	}

	//------------------------------------ страницы входа/выхода -------------------------------------------------------------
	function logout_url() { return url(NULL, 'USERS', 'exit_user'); }
	//function login_url() { return url(NULL, 'USERS', 'in'); }
	
	//---------------------------------------- логинимся в движке -----------------------------------------------------------
	function after_login($login, $wp_user){
		// логинимся в движке
		$id = get_user_id($login);
		if( $id !==NULL ){
			$eng_usr = get_user( $id, 'password' );
			soft_login($login, $eng_usr['password']);
			}
	}
	
	//---------------------------------------- логинимся в движке -----------------------------------------------------------
	function set_current_user(){
		// логинимся в движке
		global $current_user;
		if( !isset($current_user) || $current_user->ID==0 ) return;
		$id = get_user_id( $current_user->user_login );
		if( $id !==NULL ){
			$eng_usr = get_user( $id, 'password' );
			soft_login($current_user->user_login, $eng_usr['password']);
			}
	}
	
	//--------------------------------------------- автоматически выходим ---------------------------------------------------
	function auto_logout() {
		if( isset($_SESSION['user_id']) ){
			logout();
			}
	}
	//------------------------------------------ выводим меню плагина --------------------------------------------------------
	function plugin_menu() {
		//add_menu_page('Настройка Engine', 'Engine', 'administrator', 'ENGINE', array(&$this, 'admin_page'), false); //administrator
		add_dashboard_page('Настройка', 'Админка сайта', 'read', 'ENGINE', array(&$this, 'admin_page'));
		return true;
	}
	
	//----------------------------------------- пересылаем на администрирование в движке --------------------------------
	function admin_page(){
	$adminka = url(NULL, 'USERS', 'admin/update_user');
	echo<<<END
		<script lang="Java">	
				location.href='$adminka';
		</script>
END;
	}
	
	//---------------------------------- выполняется при обновлении данных пользвателя в WP --------------------------
	function update_user( $user_id ){//, $old_user_data ){
		$usr = get_user_by('id', $user_id); //get_userdata($user_id);
		// ищем id пользователя в движке
		$eng_user_id = get_user_id($usr->user_login);
		if($eng_user_id !== NULL)
			update_user($eng_user_id, array('name'=>$usr->user_firstname, 'sur_name'=>$usr->user_lastname, 'mail'=>$usr->user_email)) ;
		else
			register_user($user_id); // пользователь не найден создаем его
	}
	
	//------------------------- выполняется при регистрации пользователя в WP -----------------------------------------
	function register_user($user_id){
		//global $wpdb;
		$user = get_userdata($user_id);
		$plaintext_pass = NULL;
		if($this->generate_password!=NULL) {$plaintext_pass = $this->generate_password; $this->generate_password = NULL;}
		if($plaintext_pass===NULL) $plaintext_pass = wp_generate_password();
		$ret = reg_user($user->user_login, $plaintext_pass  , array('mail'=>$user->user_email));
		if( true !== $ret ){
			// произошла ошибка при регистрации
			include_once(ABSPATH. '/wp-admin/includes/user.php');
			wp_delete_user( $user_id,  1);	// удаляем пользователя
			echo $ret . '<br>Нажмите назад и попробуйте еще раз.';
			exit;
			}
	}
	
	//--------------------------------- запоминаем пароль --------------------------------------------------------
	function reg_password($password){
		// просто запоминаем, не изменяя значения
		$this->generate_password = $password;
		return $password;
	}
}

$Engine = new Engine();
?>