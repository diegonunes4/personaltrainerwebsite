<?php

class SWPM_DYNAMIC_MENUS_ADMIN {

    var $menus;
    var $levels;

    function __construct() {
	add_action( 'swpm_after_main_admin_menu', array( $this, 'add_admin_menu' ) );
	if ( isset( $_POST[ 'swpm_dm_settings_update' ] ) ) {
	    $this->update_settings();
	}
    }

    function add_admin_menu( $parent_slug ) {
	add_submenu_page( $parent_slug, __( "Dynamic Menus", 'simple-membership' ), __( "Dynamic Menus", 'simple-membership' ), 'manage_options', 'swpm-dynamic-menus', array( $this, 'show_settings_page' ) );
    }

    function update_settings() {
	if ( isset( $_POST[ 'swpm_dm_logged_in_menu' ] ) ) {
	    update_option( 'swpm_dm_logged_in_menu', sanitize_text_field( $_POST[ 'swpm_dm_logged_in_menu' ] ) );
	}
	if ( isset( $_POST[ 'swpm_dm_menu_level' ] ) ) {
	    update_option( 'swpm_dm_menu_level', $_POST[ 'swpm_dm_menu_level' ] );
	}
	add_action( 'admin_notices', array( $this, 'settings_updated__success' ) );
    }

    function settings_updated__success() {
	?>
	<div class="notice notice-success is-dismissible">
	    <p><?php _e( 'Settings updated.', 'simple-membership' ); ?></p>
	</div>
	<?php
    }

    private function gen_menu_opts( $selected = false, $default = false ) {
	$str = '';
	foreach ( $this->menus as $menu ) {
	    $sel = '';
	    if ( $selected !== false ) {
		if ( $selected === $menu->name ) {
		    $sel = ' selected';
		}
	    }
	    $str .= sprintf( '<option value="%s"%s>%s</option>', $menu->name, $sel, $menu->name );
	}
	if ( $default ) {
	    $str = '<option value="0">(Default)</option>' . $str;
	}
	return $str;
    }

    private function gen_levels() {
	if ( ! $this->levels ) {
	    return 'No membership levels created yet.';
	}
	$str		 = '';
	$level_opts	 = get_option( 'swpm_dm_menu_level', true );
	foreach ( $this->levels as $id => $name ) {
	    $sel = false;
	    if ( isset( $level_opts[ $id ] ) ) {
		$sel = $level_opts[ $id ];
	    }
	    $str .= sprintf( '<tr valign="top"><td>"%s" Membership Level Menu</td><td align="left"><select name="swpm_dm_menu_level[%d]">%s</select><p class="description">Select menu for "%1$s" membership level. Select (Default) if you want to use the one selected for logged in members.</p></td></tr>', $name, $id, $this->gen_menu_opts( $sel, true ) );
	}
	return $str;
    }

    function show_settings_page() {
	$this->menus	 = get_terms( 'nav_menu', array( 'hide_empty' => true ) );
	$this->levels	 = SwpmMembershipLevelUtils::get_all_membership_levels_in_array();
	?>
	<div class="wrap"><h2>Dynamic Menus Settings</h2><div id="poststuff"><div id="post-body">
		    <form method="post" action="">
			<div class="postbox">
			    <h3 class="hndle"><label for="title">Dynamic Menus</label></h3>
			    <div class="inside">
				<table class="form-table">
				    <tbody>
					<tr valign="top"><td width="25%" align="left">
						Logged In Members Menu
					    </td><td align="left">
						<select name="swpm_dm_logged_in_menu">
						    <?php echo $this->gen_menu_opts( get_option( 'swpm_dm_logged_in_menu', true ) ); ?>
						</select>
						<p class="description">Select menu to be displayed for logged in members.</p>
					    </td></tr>
					<?php echo $this->gen_levels(); ?>
				    </tbody>
				</table>
			    </div></div>
			<div class="submit">
			    <input type="submit" class="button-primary" name="swpm_dm_settings_update" value="Update">
			</div>
		    </form>
		</div>
	    </div>
	</div>
	<?php
    }

}

new SWPM_DYNAMIC_MENUS_ADMIN();
