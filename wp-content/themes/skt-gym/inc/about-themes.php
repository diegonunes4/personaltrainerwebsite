<?php
//about theme info
add_action( 'admin_menu', 'skt_gym_abouttheme' );
function skt_gym_abouttheme() {    	
	add_theme_page( esc_html__('About Theme', 'skt-gym'), esc_html__('About Theme', 'skt-gym'), 'edit_theme_options', 'skt_gym_guide', 'skt_gym_mostrar_guide');   
} 
//guidline for about theme
function skt_gym_mostrar_guide() { 
	//custom function about theme customizer
	$return = add_query_arg( array()) ;
?>
<div class="wrapper-info">
	<div class="col-left">
   		   <div class="col-left-area">
			  <?php esc_attr_e('Theme Information', 'skt-gym'); ?>
		   </div>
          <p><?php esc_attr_e('SKT Gym useful for fitness, yoga, personal trainers, health experts, bootcamps, weight loss, clubs, physiotherapy, wellness, workout, lifestyle, aerobics, boxing, sports, crossfit, spa, massage center, cardio, meditation, advisor. Gutenberg ready, SEO friendly, WooCommerce compatible, multilingual ready.','skt-gym'); ?></p>
		  <a href="<?php echo esc_url(SKT_GYM_SKTTHEMES_PRO_THEME_URL); ?>"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/free-vs-pro.png" alt="" /></a>
	</div><!-- .col-left -->
	<div class="col-right">			
			<div class="centerbold">
				<hr />
				<a href="<?php echo esc_url(SKT_GYM_SKTTHEMES_LIVE_DEMO); ?>" target="_blank"><?php esc_attr_e('Live Demo', 'skt-gym'); ?></a> | 
				<a href="<?php echo esc_url(SKT_GYM_SKTTHEMES_PRO_THEME_URL); ?>"><?php esc_attr_e('Buy Pro', 'skt-gym'); ?></a> | 
				<a href="<?php echo esc_url(SKT_GYM_SKTTHEMES_THEME_DOC); ?>" target="_blank"><?php esc_attr_e('Documentation', 'skt-gym'); ?></a>
                <div class="space5"></div>
				<hr />                
                <a href="<?php echo esc_url(SKT_GYM_SKTTHEMES_THEMES); ?>" target="_blank"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/sktskill.jpg" alt="" /></a>
			</div>		
	</div><!-- .col-right -->
</div><!-- .wrapper-info -->
<?php } ?>