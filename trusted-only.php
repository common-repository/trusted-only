<?php
/*
Plugin Name: Trusted Only
Plugin URI:  http://andrey.eto-ya.com/wordpress/my-plugins/trusted-only
Description: Makes your site content visible only for several users who are in your trusted list.
Version: 1.1
Author: Andrey K.
Author URI: http://andrey.eto-ya.com/
License: GPL2
*/

/*  Copyright 2012 Andrey K. (http://andrey.eto-ya.com/, email: v5@bk.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain('trusted', false, 'trusted-only');
add_action ( 'init', 'trusted_only_init');
add_filter('login_message', 'trusted_only_login_message');
add_action('admin_menu', 'trusted_only_menu');
add_action('login_head', 'trusted_only_login_head');
add_filter("login_redirect", "trusted_only_login_redirect", 10, 3);

function trusted_only_init() {
	global $current_user;
	$trusted= get_option('trusted_only');
	$trusted_users= (array)$trusted['users'];
	$message= $trusted['message'];

	$exclude= array(wp_login_url(), admin_url('/'), site_url('/wp-signup.php'));
	foreach ( $exclude as $a ) {
		$arr= parse_url($a);
		if ( 1==strpos($_SERVER['REQUEST_URI'], substr($arr['path'], 1)) ) {
			return;
		}
	}
	
	if ( !$current_user->ID ) {
		 wp_redirect( wp_login_url() );
		 die();
	}

	$u= (isset($current_user->data->user_login))?$current_user->data->user_login: $current_user->user_login;
	
	if ( current_user_can('manage_options') || is_super_admin() || in_array(strtolower($u), $trusted_users) ) {}
	else {

		$msg= '<span style="color:#0000c0">'.$u. ' ['. wp_loginout('', false).'</span>]'. ($message?('<br />'.$message):'');

		wp_die( sprintf(__('Sorry, you can not read this site %s because the site owner allows access for several users only.<br />You are signed in now as %s.', 'trusted'), '<strong>'.get_option('blogname').'</strong>', $msg) );
	}
}

function trusted_only_menu() {
	add_submenu_page('users.php', __('Trusted Users List', 'trusted'), __('Trusted Users', 'trusted'), 'manage_options', 'trusted_users_list', 'trusted_only_settings_page');
	add_action( 'admin_init', 'trusted_only_init' );
}

function trusted_only_login_message($str) {
	$trusted= get_option('trusted_only');
	$message= empty($trusted['message'])?'':$trusted['message'];
	return $str.'<div class="trusted-only-message">'.__('Only several authorized users may read this site. If you are one of them please sign in.', 'trusted'). ($message?('<br />'.$message):'') .'</div>';
}

function trusted_only_login_head() { ?>
<style type="text/css" title="">
.trusted-only-message {padding: 10px; text-align: center;}
</style>
<?php
}

function trusted_only_login_redirect($to, $req, $user) {
	if ( is_super_admin($user->ID) || isset( $user->allcaps['edit_posts'] ) && $user->allcaps['edit_posts'] ) {
		return admin_url();
	}
	else {
		return home_url();
	}
}

function trusted_only_settings_page() {
	if (isset($_POST['trusted_only'])) {
		$allow_space= empty($_POST['trusted_only']['allow_space'])?0:1;
		$_POST['trusted_only']['users']= strtolower($_POST['trusted_only']['users']);
		if ($allow_space) {
			$res= explode(',', $_POST['trusted_only']['users']);
			foreach ($res as $key=>$val) {
				$res[$key]= trim(preg_replace('/[^ a-z0-9_-]+/', '', $val));
			}
		}
		else {
			preg_match_all('/[a-z0-9_-]+/', $_POST['trusted_only']['users'], $res );
			$res= $res[0];
		}
		$users= array_unique($res);
		$message= trim( htmlspecialchars(strip_tags( mb_substr($_POST['trusted_only']['message'], 0, 200))));
		update_option('trusted_only', array('users'=> $users, 'message'=> $message, 'allow_space'=>$allow_space) );
		$updated= 1;
	}
	$trusted= get_option('trusted_only');
	$sep= (int)$trusted['allow_space']? ', ':' ';
	echo empty($updated)?'':'<div id="message" class="updated fade"><p>'.__('Options Saved', 'trusted').'.</p></div>';

?>
<div class="wrap">
<div id="icon-users" class="icon32"><br /></div>
<h2><?php _e('List users who may read your blog', 'trusted'); ?></h2>

<form method="post" action="">
<table class="form-table">
<tr>
<th><?php _e('Usernames separated by spaces or commas', 'trusted'); ?>:</th>
<td><textarea name="trusted_only[users]" rows="10" cols="80"><?php echo $trusted['users']?implode($trusted['users'], $sep):''; ?></textarea></td>
</tr>
<tr>
<th><?php _e('Allow spaces in usernames', 'trusted'); ?>:</th>
<td><input type="checkbox" name="trusted_only[allow_space]" value="1" <?php echo $trusted['allow_space']?'checked':''; ?> /> &nbsp; <span class="description"><?php _e('then you should separate usernames by commas', 'trusted'); ?></span></td>
</tr>
<tr>
<th><?php _e('A message for display on the login page (optional)', 'trusted'); ?>:</th>
<td><input type="text" name="trusted_only[message]" maxlength="200" size="80" value="<?php echo $trusted['message']; ?>" /></td>
</tr>
<tr><td> </td>
<td><p><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p></td></tr>
</form>
</div>
<table>

<?php
	
}