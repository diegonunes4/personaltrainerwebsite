<?php
if (!defined('ABSPATH'))
    exit; //Exit if accessed directly

function displayCustomCSSSettings() {
    global $wpcci_plugin;
    global $blog_id;
    $css_code = '';
    if (isset($_POST['wpcci_save_settings'])) {
	$css_code = strip_tags(stripslashes($_POST['wpcci_css_entry']));
	$settings_array = array('blog_id' => $blog_id, 'css_code' => $css_code);
	update_option('wpcci_settings', $settings_array); //Store in options
	if (isset($_POST['wpcci_css_entry'])) {
	    
	}

	echo '<div id="message" class="updated fade">';
	echo '<p>Your custom CSS settings were successfully saved</p>';
	echo '</div>';
    }
    $settings = get_option('wpcci_settings');
    if ($settings) {
	if ($settings['blog_id'] == $blog_id) {
	    $css_code = $settings['css_code'];
	    //write the css code to the file
	    file_put_contents($wpcci_plugin->get_css_path(), $css_code);
	} else {
	    $css_code = NULL;
	}
    }
    ?>
    <div id="poststuff">
        <div id="post-body">

    	<div class="postbox">
    	    <h3 class="hndle"><label for="title">Using The WP Custom CSS Inserter Plugin</label></h3>
    	    <div class="inside">
    		<p>This plugin allows you to add custom CSS code to your site without the need to modify any theme or plugin files.</p>
    		<p>The plugin works by accepting your chosen custom CSS code using the settings interface below and consequently inserting this code into a special CSS file which is located in this plugin's directory.</p>
    		<p><a href="https://www.tipsandtricks-hq.com/wordpress-custom-css-plugin-6413">Visit the plugin page</a> for more details and usage instruction.</p>

    	    </div></div>
    	<div class="postbox">
    	    <h3 class="hndle"><label for="title">WP Custom CSS Settings</label></h3>
    	    <div class="inside">
    		<div class="wpcci_blue_box">
			<?php
			echo '<p>' . __('Enter or paste your custom CSS code in the box below and then click the save button.', 'wpcci') . '</p>';
			?>
    		</div>

    		<form action="" method="POST">
    		    <textarea name="wpcci_css_entry" id="wpcci_css_entry" dir="ltr" style="width:100%;height:500px;"><?php echo $css_code; ?></textarea>
    		    <script language="javascript">var editor = CodeMirror.fromTextArea(document.getElementById("wpcci_css_entry"), {lineNumbers: true});</script>

    		    <div style="border-bottom: 1px solid #dedede; height: 10px"></div>
    		    <br />
    		    <input type="submit" name="wpcci_save_settings" value="Save CSS Code" class="button-primary" />
    		</form>

    	    </div>
    	</div>

    	<div style="background:#FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">	
    	    <p>
    	    <ul>
    		<li>View our <a href="https://www.tipsandtricks-hq.com/find-out-which-web-hosting-company-offers-the-cheapest-and-reliable-web-hosting-solution-387" target="_blank">WordPress hosting recommendatation</a></li>
    		<li>View our <a href="https://www.tipsandtricks-hq.com/development-center" target="_blank">Free WordPress Plugins</a></li>
    		<li>View our <a href="https://www.tipsandtricks-hq.com/products" target="_blank">Premium Plugins</a></li>
    	    </ul>
    	    </p>
    	</div>

        </div>
    </div>
    <?php
}
