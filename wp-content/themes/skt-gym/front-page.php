<?php
/**
 * The template for displaying home page.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package SKT Gym
 */
get_header(); 

$hideslide = get_theme_mod('hide_slides', 1);
$secwithcontent = get_theme_mod('hide_sectionone', 1);
$hide_sectiontwo = get_theme_mod('hide_sectiontwo', 1);
$hide_home_third_content = get_theme_mod('hide_home_third_content', 1);

if (!is_home() && is_front_page()) { 
if( $hideslide == '') { ?>
<!-- Slider Section -->
<?php 
$pages = array();
for($sld=7; $sld<10; $sld++) { 
	$mod = absint( get_theme_mod('page-setting'.$sld));
    if ( 'page-none-selected' != $mod ) {
      $pages[] = $mod;
    }	
} 
if( !empty($pages) ) :
$args = array(
      'posts_per_page' => 3,
      'post_type' => 'page',
      'post__in' => $pages,
      'orderby' => 'post__in'
    );
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) :	
	$sld = 7;
?>
<section id="home_slider">
  <div class="slider-wrapper theme-default">
    <div id="slider" class="nivoSlider">
		<?php
        $i = 0;
        while ( $query->have_posts() ) : $query->the_post();
          $i++;
          $skt_gym_slideno[] = $i;
          $skt_gym_slidetitle[] = get_the_title();
		  $skt_gym_slidedesc[] = get_the_excerpt();
          $skt_gym_slidelink[] = esc_url(get_permalink());
          ?>
          <img src="<?php the_post_thumbnail_url('full'); ?>" title="#slidecaption<?php echo esc_attr( $i ); ?>" />
          <?php
        $sld++;
        endwhile;
          ?>
    </div>
        <?php
        $k = 0;
        foreach( $skt_gym_slideno as $skt_gym_sln ){ ?>
    <div id="slidecaption<?php echo esc_attr( $skt_gym_sln ); ?>" class="nivo-html-caption">
      <div class="container">	
      <div class="slide_info">
        <h2><?php echo esc_html($skt_gym_slidetitle[$k] ); ?></h2>
        <p><?php echo esc_html($skt_gym_slidedesc[$k] ); ?></p>
        <div class="clear"></div>
        <a class="slide_more" href="<?php echo esc_url($skt_gym_slidelink[$k] ); ?>">
          <?php esc_html_e('Join Now!', 'skt-gym');?>
          </a>
      </div>
      </div>
    </div>
 	<?php $k++;
       wp_reset_postdata();
      } ?>
<?php endif; endif; ?>
  </div>
  <div class="clear"></div>
</section>
<?php } } 
?>
<?php
	if(!is_home() && is_front_page()){ 
	if( $secwithcontent == '') {
?>
 <section id="sectionone">
 <?php 
    for($v=1; $v<7; $v++) { 
    if( get_theme_mod('seconepage-column'.$v,false)) {
    $seconeblock = new WP_query('page_id='.get_theme_mod('seconepage-column'.$v,true)); 
    while( $seconeblock->have_posts() ) : $seconeblock->the_post(); 
    ?>
    <div class="seconeblock">
        <div class="seconeblockinner">
            <?php if( has_post_thumbnail() ) { ?><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_post_thumbnail('full'); ?></a><?php } ?>
            <h3><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h3>
        </div>
    </div>
    <?php endwhile; wp_reset_postdata(); 
       }} 
 ?>
 </section>
<?php }} ?>
<?php
if (!is_home() && is_front_page()) { 
if( $hide_sectiontwo == '') { ?>
<section class="hometwo_section_area">
    	<div class="center">
            <div class="hometwo_section_content">
 			<div class="sectwocols">
				<?php 
                if( get_theme_mod('sectwofirst',false)) {
                $sectiontwoquery = new WP_query('page_id='.get_theme_mod('sectwofirst',true)); 
                while( $sectiontwoquery->have_posts() ) : $sectiontwoquery->the_post(); 
                ?>
            	<div class="sectwo-block-title">
                	<h2><?php the_title(); ?></h2>
                </div>
                <div class="sectwo-block-desc">
                	<?php the_content(); ?>
                </div>
				<?php endwhile;
                wp_reset_postdata(); 
                } ?>
                <div class="clear"></div>
				<?php 
                for($fl=1; $fl<4; $fl++) { 
                if( get_theme_mod('sectwo-feature'.$fl,false)) {
                $secfeaturelist = new WP_query('page_id='.get_theme_mod('sectwo-feature'.$fl,true)); 
                while( $secfeaturelist->have_posts() ) : $secfeaturelist->the_post(); 
                ?>                
                <div class="sectwo-features-list">
                    <span class="sectwo-features-list-number"><?php echo esc_attr($fl);?></span>
                    <h4><?php the_title(); ?></h4>
                    <?php the_content(); ?>
                </div>
                <?php endwhile; wp_reset_postdata(); 
                   }} 
             ?>    
			<?php
                $sectwo_buttontitle = get_theme_mod('sectwo_buttontitle');
                $sectwo_buttonlink = get_theme_mod('sectwo_buttonlink');
                if(!empty($sectwo_buttontitle)){
            ?>
            <a class="sectwo-block-button" href="<?php echo esc_url($sectwo_buttonlink);?>"><?php echo esc_html($sectwo_buttontitle);?></a>        
            <?php } ?>     
            </div>	
            <div class="clear"></div>
            </div>
        </div>    
    </section>
<?php } } ?>
<?php if (!is_home() && is_front_page()) {
	  if( $hide_home_third_content == '' ){	
?>
<section class="home3_section_area">
  <div class="center">
    <div class="home_section3_content">
		<?php 
            if( get_theme_mod('third-column',false)) {
            $sectionthreequery = new WP_query('page_id='.get_theme_mod('third-column',true)); 
            while( $sectionthreequery->have_posts() ) : $sectionthreequery->the_post(); 
        ?>
     	<div class="sec3-title"><h2><?php the_title(); ?></h2></div>
        <div class="sec3-desc"><?php the_content(); ?></div>
		<?php endwhile;
        wp_reset_postdata(); 
        } ?>
		<?php
        $secthree_buttontitle = get_theme_mod('secthree_buttontitle');
        $secthree_buttonlink = get_theme_mod('secthree_buttonlink');
        if(!empty($secthree_buttontitle)){
        ?>
        <a class="sectwo-block-button" href="<?php echo esc_url($secthree_buttonlink);?>"><?php echo esc_html($secthree_buttontitle);?></a>        
        <?php } ?>
    </div>
  </div>
</section>
<?php } } ?>
<div class="clear"></div>
<div class="container">
     <div class="page_content">
      <?php 
	if ( 'posts' == get_option( 'show_on_front' ) ) {
    ?>
    <section class="site-main">
      <div class="blog-post">
        <?php
                    if ( have_posts() ) :
                        // Start the Loop.
                        while ( have_posts() ) : the_post();
                            /*
                             * Include the post format-specific template for the content. If you want to
                             * use this in a child theme, then include a file called called content-___.php
                             * (where ___ is the post format) and that will be used instead.
                             */
                            get_template_part( 'content', get_post_format() );
                        endwhile;
                        // Previous/next post navigation.
						the_posts_pagination( array(
							'mid_size' => 2,
							'prev_text' => esc_html__( 'Back', 'skt-gym' ),
							'next_text' => esc_html__( 'Next', 'skt-gym' ),
						) );
                    else :
                        // If no content, include the "No posts found" template.
                         get_template_part( 'no-results', 'index' );
                    endif;
                    ?>
      </div>
      <!-- blog-post --> 
    </section>
    <?php
} else {
    ?>
	<section class="site-main">
      <div class="blog-post">
        <?php
                    if ( have_posts() ) :
                        // Start the Loop.
                        while ( have_posts() ) : the_post();
                            /*
                             * Include the post format-specific template for the content. If you want to
                             * use this in a child theme, then include a file called called content-___.php
                             * (where ___ is the post format) and that will be used instead.
                             */
							 ?>
                             <header class="entry-header">           
            				<h1><?php the_title(); ?></h1>
                    		</header>
                             <?php
                            the_content();
                        endwhile;
                        // Previous/next post navigation.
						the_posts_pagination( array(
							'mid_size' => 2,
							'prev_text' => esc_html__( 'Back', 'skt-gym' ),
							'next_text' => esc_html__( 'Next', 'skt-gym' ),
						) );
                    else :
                        // If no content, include the "No posts found" template.
                         get_template_part( 'no-results', 'index' );
                    endif;
                    ?>
      </div>
      <!-- blog-post --> 
    </section>
	<?php
}
	?>
    <?php get_sidebar();?>
    <div class="clear"></div>
  </div><!-- site-aligner -->
</div><!-- content -->
<?php get_footer(); ?>