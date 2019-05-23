<?php
/*
Plugin Name: MealPlannerPro Recipe Plugin
Plugin URI: http://www.mealplannerpro.com/recipe_plugin
Plugin GitHub: https://github.com/Ziplist/recipe_plugin
Description: A plugin that adds all the necessary microdata to your recipes, so they will show up in Google's Recipe Search
Version: 7.8.3.11
Author: MealPlannerPro.com
Author URI: http://www.mealplannerpro.com/
License: GPLv3 or later

Copyright 2011, 2012, 2013, 2014 MealPlannerPro, Inc.
This code is derived from the 1.3.1 build of RecipeSEO released by codeswan: http://sushiday.com/recipe-seo-plugin/ and licensed under GPLv2 or later
*/

/*
    This file is part of MealPlannerPro Recipe Plugin.

    MealPlannerPro Recipe Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MealPlannerPro Recipe Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MealPlannerPro Recipe Plugin. If not, see <http://www.gnu.org/licenses/>.
*/

require_once plugin_dir_path(__FILE__) . 'classes/mpp-api.php';

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hey!  This is just a plugin, not much it can do when called directly.";
	exit;
}

if (!defined('MPPRECIPE_VERSION_KEY'))
    define('MPPRECIPE_VERSION_KEY', 'mpprecipe_version');

if (!defined('MPPRECIPE_VERSION_NUM'))
    define('MPPRECIPE_VERSION_NUM', '7.8.1.2');

if (!defined('MPPRECIPE_PLUGIN_DIRECTORY'))
    define('MPPRECIPE_PLUGIN_DIRECTORY', plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/');


function strip( $i )
{
    // Strip JS, HTML, CSS, Comments
    $search = array(
        '@<script[^>]*?>.*?</script>@si',
        '@<[\/\!]*?[^<>]*?>@si',
        '@<style[^>]*?>.*?</style>@siU',
        '@<![\s\S]*?--[ \t\n\r]*>@'
    );

    $o = preg_replace($search, '', $i);
    return $o;
}
//TODO: Wordpress now offers prepared statements. Transition all queries to prepared type for simpler injection risk mitigation.
function sanitize( $i )
{
    return $i;
    if (is_array($i))
    {
        $o = array();
        foreach($i as $k=>$val)
            $o[$k] = sanitize($val);
    }
    else
    {
        if (get_magic_quotes_gpc())
            $i = stripslashes($i);

        $o = strip($i);
    }
    return $o;
}

add_option(MPPRECIPE_VERSION_KEY, MPPRECIPE_VERSION_NUM);  // sort of useless as is never updated
add_option("mpprecipe_db_version"); // used to store DB version

add_option('mealplannerpro_recipe_button_hide', '');
add_option('mpprecipe_printed_permalink_hide', '');
add_option('mpprecipe_printed_copyright_statement', '');
add_option('mpprecipe_stylesheet', 'mpprecipe-design23');
add_option('mpprecipe_image_hide', '');
add_option('mpprecipe_image_hide_print', '');
add_option('mpprecipe_print_link_hide', '');
add_option('mpprecipe_ingredient_label', 'Ingredients');
add_option('mpprecipe_ingredient_label_hide', '');
add_option('mpprecipe_ingredient_list_type', 'ul');
add_option('mpprecipe_instruction_label', 'Instructions');
add_option('mpprecipe_instruction_label_hide', '');
add_option('mpprecipe_instruction_list_type', 'ol');
add_option('mpprecipe_notes_label', 'Notes');
add_option('mpprecipe_notes_label_hide', '');
add_option('mpprecipe_prep_time_label', 'Prep Time');
add_option('mpprecipe_prep_time_label_hide', '');
add_option('mpprecipe_cook_time_label', 'Cook Time');
add_option('mpprecipe_cook_time_label_hide', '');
add_option('mpprecipe_total_time_label', 'Total Time');
add_option('mpprecipe_total_time_label_hide', '');
add_option('mpprecipe_yield_label', 'Yields');
add_option('mpprecipe_yield_label_hide', '');
add_option('mpprecipe_serving_size_label', 'Serves');
add_option('mpprecipe_serving_size_label_hide', '');
add_option('mpprecipe_outer_border_style', '');
add_option('mpprecipe_custom_save_image', '');
add_option('mpprecipe_custom_print_image', '');

add_option('mpprecipe_personalizedplugin', 'Show');
add_option('mpprecipe_subdomain', '');
add_option('mpprecipe_subdomain_id', '');
add_option('mpprecipe_nutrition', '');
add_option('mpprecipe_swoop_id', '');
add_option('mpprecipe_ratings', '');

add_option('mpprecipe_custom_html', '');
add_option('mpprecipe_nutrition_style', '');

add_option( 'mpprecipe_primary_color','' );
add_option( 'mpprecipe_secondary_color','' );
add_option( 'mpprecipe_text_color','' );
add_option( 'mpprecipe_link_color','' );
add_option( 'mpprecipe_link_hover_color','' );
add_option( 'mpprecipe_link_underline','' );

add_option( 'mpprecipe_font','' );
add_option( 'mpprecipe_font_size','' );

define('MPPRECIPE_AUTO_HANDLE_TOTALTIME',0);

register_activation_hook(__FILE__, 'mpprecipe_install');
add_action('plugins_loaded', 'mpprecipe_upgradedb');

add_action('admin_init', 'mpprecipe_add_recipe_button');
add_action('admin_head','mpprecipe_js_vars');

define('MPPRECIPE_PROTOCOL', "https://" );
define('MPPRECIPE_DOMAIN', 'mealplannerpro.com');

function mpprecipe_register()
{
    if (get_option('mpprecipe_subdomain') )
        return;

    $h  = mpp_gethostname();
    $u = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/api/wordpress/register";
    $r  = mpprequest( 'get', $u, array( 'host' => $h ) );
    mpp_update_subdomain( $r );
}
function mpp_update_subdomain( $response )
{
    $wl = json_decode( $response );
    update_option('mpprecipe_subdomain_id', $wl->id );
    update_option('mpprecipe_subdomain',    $wl->subdomain );
}
function mpp_gethostname()
{
    if( isset( $_SERVER['SERVER_NAME'] ) )
        return $_SERVER['SERVER_NAME'];
    elseif( isset( $_SERVER['HOST_NAME'] ) )
        return $_SERVER['HOST_NAME'];
}

/**
 * Sends a copy of the recipe to the server and receives in response a 1D array containing:
 *  - a server-side recipe identifier
 *  - ESHA nutrition data
 */
function mpprecipe_register_recipe( $recipe )
{
    global $wpdb;

    $h                    = mpp_gethostname();
    $id                   = $recipe['recipe_id'];
    $post_id              = $recipe['post_id'];
    $recipe['published']  = (get_post_status($post_id) == 'publish' && (get_option('mpprecipe_recipe_to_mpp') == 'Show')) ? 1 : 0;
    $recipe['source_url'] = get_post_permalink($post_id);
    if (empty( $recipe['server_recipe_id'] ) )
        $recipe['server_recipe_id'] = $wpdb->get_var( "SELECT server_recipe_id FROM " . $wpdb->prefix ."mpprecipe_recipes where recipe_id='$id' " );

    $data = array(
        'host'   => $h,
        'recipe' => json_encode( $recipe )
    );

    $response   = mpprequest( "post", MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN. "/api/wordpress/saverecipe", $data);
    $nutrition  = json_decode( $response, true );
    $wpdb->update( $wpdb->prefix . "mpprecipe_recipes", $nutrition, array( 'recipe_id' => $id ));
}

function mppcurl(  $type, $url, $encoded_data )
{
    $ch = curl_init();

    if( strtolower($type) === "post" )
    {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
    }
    else
    {
        curl_setopt($ch, CURLOPT_URL, $url . "?" . $encoded_data);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function mpprecipe_jsonld_new( $recipe )
{

    $output ='
    <script type="application/ld+json">
      {
        "@context": "http://schema.org/",
    	"name": "New Recipe",
    	"@type": "Recipe",';

    $output .= sprintf(' "URL": %s , ', json_encode($recipe->recipe_image ));

    $output .= sprintf('
        "image": {
          "@type": "ImageObject",
          "url": %s
         }
    ', json_encode($recipe->recipe_image ));

	$output .= '}';
	$output .= '</script>';
	$output = '
<script type="application/ld+json">
{
  "@context": "http://schema.org/", 
  "@type": "Recipe", 
  "name": "Apple Pie",
  "image": "https://mealplannerpro.com/images/recipes/recipes/0/470/470054/1157702.jpg",
  "description": "The best apple pie",
  "keywords": "apple pie crusty",
  "author": {
    "@type": "Person",
    "name": ""
  },
  "prepTime": "",
  "cookTime": "", 
  "totalTime": "", 
  "nutrition": {
    "@type": "NutritionInformation",
    "calories": ""
  },
  "recipeIngredient": ""  
}
</script>';

    return $output;
}

/**
 * Create JSON-LD formatted Recipe
 */
function mpprecipe_jsonld( $recipe )
{
    global $wpdb;

    $recipe_jsonld_mapping = array(
        "rating"        => "ratingValue",
        "rating_count"  => "ratingCount",
        "serving_size"  => "serving_size",
        "recipe_title"  => "name",
        "recipe_image"  => "image",
        "summary"       => "description",
        "prep_time"     => "prepTime",
        "cook_time"     => "cookTime",
        "yield"         => "recipeYield",
        "fat"           => "fat",
        "author"        => "author",
        // "type"          => "recipeCategory",
        "created_at"    => "datePublished",

        "ingredients"   => "recipeIngredient",
        "instructions"  => "recipeInstructions",

        "calories"      => "calories",
        "carbs"         => "carbs",
        "protein"       => "protein",
        "fiber"         => "fiber",
        "transfat"      => "transfat",
        "unsatfat"      => "unsatfat",
        "satfat"        => "satfat",
        "sodium"        => "sodium",
        "sugar"         => "sugar",
        "cholesterol"   => "cholesterol",
    );

    $simple = array(
        "recipe_title",
        "summary",
        "prep_time",
        "cook_time",
        "yield",
        // "type",
        "created_at",
        //"instructions"
    );

    $nl = "\n";
    $output ='
    <script type="application/ld+json">
      {
        "@context": "http://schema.org/",
    ';

	// BDJ
 	$permalink = get_permalink();
    $output .= sprintf(' "url": [ %s ],', json_encode($permalink ));

    foreach( $simple as $rprop )
    {
        $v      = $recipe->$rprop;
        if( $v )
            $output .= sprintf( '"%s" : %s,'.$nl, $recipe_jsonld_mapping[$rprop], json_encode($v) );
    }

    //instructions
    //$jsonInstructions = explode("\n", $recipe->instructions);
    $jsonInstructions = @array_filter(preg_split('/[\n\r]+/', $recipe->instructions));

    $output .= '"recipeInstructions":[';
    $sections = array();
    $step = array();
    foreach ($jsonInstructions as $key => $jsonInstruction){
        $section_flag = false;
        if($jsonInstruction[0] == '!'){
            $section_flag = true;
            $section = ' {
              "@type": "HowToSection",
              "name": "' . ucfirst(strtolower(str_replace("!", "", $jsonInstruction))) . '",
              "itemListElement": [';


        }

        if (!$section_flag) {
            $step[] .= sprintf('
            {
              "@type": "HowToStep",
              "text": %s
            }' . $nl, json_encode($jsonInstruction)  );
        }

        if ((@$jsonInstructions[$key + 1][0] == '!' || !isset($jsonInstructions[$key + 1][0])) && isset($section)) {
            $section    .= implode(",", $step);
            $section    .= ']}';
            $sections[] = $section;
            $step       = array();
            unset($section);
        }
    }

    if (!empty($sections)){
        $output .= implode(",",$sections);
    } else {
        $output .= implode(",",$step);
    }

    $output .= '],';


    //$output .= sprintf( '"%s" : %s,'.$nl, $recipe_jsonld_mapping['instructions'], json_encode($v) );

    $tagged_data = unserialize($recipe->tagged);
    if (isset($recipe->tagged) && is_array($tagged_data)) {
        // Category
        $courses = @array_filter($tagged_data['text']['courses']);
        if (!empty($courses) && is_array($courses)) {
            $output .= sprintf('"%s" : %s,' . $nl, 'recipeCategory', json_encode(array_values($courses)));
        }
        // Cuisines
        $cuisines = @array_filter($tagged_data['text']['cuisines']);
        if (!empty($cuisines) && is_array($cuisines)) {
            $output .= sprintf('"%s" : %s,' . $nl, 'recipeCuisine', json_encode(array_values($cuisines)));
        }

        // Occasions
        $occasions = @array_filter($tagged_data['text']['occasions']);
        if (!empty($occasions)) {
            $keywords[] = implode(", ", array_values($occasions));
        }

        // keywords
        $keywords = array($recipe->recipe_title);
        $diet     = @array_filter((array)$tagged_data['text']['diet']);
        $allergy  = @array_filter((array)$tagged_data['text']['allergy']);
		$keywords = array_merge($keywords,(array)$diet,(array)$allergy);

        $output .= sprintf('"%s" : %s,' . $nl, 'keywords', json_encode(implode(", ", $keywords)));

		/*
        // suitableForDiet
        $diet            = @array_filter($tagged_data['text']['diet']);
        $allergy         = @array_filter($tagged_data['text']['allergy']);
        $suitableForDiet = @array_filter(@array_merge(@array_values($diet), @array_values($allergy)));
        if (!empty($suitableForDiet)) {
            $output .= sprintf('"%s" : %s,' . $nl, 'suitableForDiet', json_encode(implode(", ", $suitableForDiet)));
        }
		*/

        // Cooking
        $cooking = @array_filter($tagged_data['text']['cooking']);
        if (!empty($cooking) && is_array($cooking)) {
            $output .= sprintf('"%s" : %s,' . $nl, 'cookingMethod', json_encode(implode(", ", array_values($cooking))));
        }

    }

	// BDJ - Do not use links for ingredients in JSON-LD.
    $jsonIngredients  = array();
	/*
    $arrayIngredients = preg_split(
        "/\\r\\n|\\r|\\n/", mpprecipe_richify_item($recipe->ingredients)
    );
	*/
    $arrayIngredients = preg_split( "/\\r\\n|\\r|\\n/", $recipe->ingredients);

    $pattern = "#\[(.*?)\| *(.*?)( (.*?))?\]#";
	$replacement =  '\\1';
    $arrayIngredients = preg_replace( $pattern,$replacement, $arrayIngredients);

	
    if (is_array($arrayIngredients)) {
        foreach ($arrayIngredients as $jsonIngredient) {
			
           	if ($jsonIngredient != '') {
           		if ($jsonIngredient[0] != '!') {
               		$jsonIngredients[] = $jsonIngredient;
				}
			}
        }
    }

    $output .= sprintf('"%s" : %s, ' . $nl, $recipe_jsonld_mapping["ingredients"], json_encode($jsonIngredients));


	if ($recipe->recipe_image) {
			$output .= sprintf('
				"image": {
				  "@type": "ImageObject",
				  "url": %s
				 },
			', json_encode($recipe->recipe_image ));
	}



    $output .= sprintf('
        "author": {
          "@type": "Person",
          "name": %s
         },
    ', json_encode( $recipe->author ) );

    if ((int)$recipe->rating > 0 && (int)$recipe->rating_count > 0) {

        $args = array(
            'post_id' => $recipe->post_id,
        );
        $comments = get_comments( $args );
        $count = 0;
        $total = count($comments);
        /*
        $output .= '"review": [';
        foreach ( $comments as $comment ) :
            $count++;
            $user_rating_qry = "SELECT rating FROM " . $wpdb->prefix . "mpprecipe_ratings WHERE  comment_id=" . $comment->comment_ID;
            $rating          = $wpdb->get_row($user_rating_qry);
            if ($rating) {
                $ratingValue = $rating->rating;
            } else {
                $ratingValue = "";
            }
            
            $output .= '
        	{
                "@type" : "Review",
                "dateCreated" : "'.$comment->comment_date.'",
                "reviewBody" : "'.strip_tags($comment->comment_content).'",
                "author" : {"@type" : "Person", "name" : "'.$comment->comment_author.'"},
                "reviewRating" : { "@type" : "Rating" , "ratingValue" : "'.$ratingValue.'" }
            }';

            if ($count != $total) {
                $output .= ",";
            }

        endforeach;
        $output .= ' ],';
        */
        $output .= sprintf('
        "aggregateRating": {
          "@type": "AggregateRating",
          "ratingValue": %s,
          "ratingCount": %s
         },
    ', json_encode($recipe->rating), json_encode($recipe->rating_count));
    }

    if( $recipe->calories || $recipe->fat || $recipe->serving_size )
    {
        $output .= '"nutrition": { "@type": "NutritionInformation"';

        if( $recipe->serving_size )
            $output .= sprintf( ', "servingsize": %s', json_encode((int)$recipe->serving_size . " serving") );

        if( $recipe->calories )
            $output .= sprintf( ', "calories": %s', json_encode((int)$recipe->calories . " kcal") );

        if( $recipe->fat )
            $output .= sprintf( ', "fatContent": %s', json_encode((int)$recipe->fat . " g") );

        if( $recipe->fat )
            $output .= sprintf( ', "saturatedFatContent": %s', json_encode((int)$recipe->satfat . " g") );

        if( $recipe->fat )
            $output .= sprintf( ', "cholesterolContent": %s', json_encode((int)$recipe->cholesterol . " mg") );

        if( $recipe->fat )
            $output .= sprintf( ', "sodiumContent": %s', json_encode((int)$recipe->sodium . " mg") );

        if( $recipe->fat )
            $output .= sprintf( ', "carbohydrateContent": %s', json_encode((int)$recipe->carbs . " g") );

        if( $recipe->fat )
            $output .= sprintf( ', "sugarContent": %s', json_encode((int)$recipe->sugar . " g") );

        if( $recipe->fat )
            $output .= sprintf( ', "proteinContent": %s', json_encode((int)$recipe->protein . " mg") );

        $output .= '},';
    }
    $output .= '"@type": "Recipe"} </script>';

    return $output;

}

function mpprequest( $type, $url, $data )
{

    $encoded_data = http_build_query($data);

    if( function_exists('curl_version') )
        return mppcurl( $type, $url, $encoded_data );
    else
    {
        if( strtolower($type) === "post" )
        {
            $context   = stream_context_create( array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => $encoded_data
                    ),
                ));
            return file_get_contents($url , false, $context);
        }
        else
            return file_get_contents( $url . "?" . $encoded_data );
    }

}

function mpprecipe_js_vars() {
    global $current_screen;
    $type = $current_screen->post_type;

    if (is_admin()) {
        ?>
        <script type="text/javascript">
        var mpp_post_id = '<?php global $post; echo $post->ID; ?>';
        </script>
        <?php
    }
}


if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=mpprecipe') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	mpprecipe_iframe_content( sanitize($_POST), sanitize($_REQUEST) );
	exit;
}


global $mpprecipe_db_version;
// This must be changed when the DB structure is modified
$mpprecipe_db_version = "4.3.1";

// Creates MPPRecipe tables in the db if they don't exist already.
// Don't do any data initialization in this routine as it is called on both install as well as
//   every plugin load as an upgrade check.
//
// Updates the table if needed
// Plugin Ver         DB Ver
//   1.0 - 1.3        3.0
//   1.4x - 3.1       3.1  Adds Notes column to recipes table
//   3.9.2            3.2  Adds author,original columns to recipes table
//   3.9.4            3.3  Adds cuisine,type columns to recipes table
//   4.8              3.4  Adds original_*
//   5.3              3.5  Adds carb + protein + server_recipe_id + nutrition_tags
//   5.4              3.6  Test Increment
//   5.5              3.7  Test Increment
//   6.x              3.9  ER Nutr support
//   6.8              4.0  Ratings
//   6.8              4.1  Ratings ER support
//   6.9              4.2  Serving size description
//   6.9              4.2.3  Remove serving size description
//   7.7              4.2.4  Set default is enable for feature post recipe to mealplannerpro.com
//   7.8              4.3  Tag manager
function mpprecipe_upgradedb() {
    global $wpdb;
    global $mpprecipe_db_version;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $recipes_table        = $wpdb->prefix . "mpprecipe_recipes";
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";
    $recipe_tags_table    = $wpdb->prefix . "mpprecipe_tags";
    $installed_db_ver     = get_option("mpprecipe_db_version");
    $installed_plugin_ver = get_option(MPPRECIPE_VERSION_KEY);
    $cmp_db_version       = strcmp($installed_db_ver, $mpprecipe_db_version);

    if(strcmp($installed_plugin_ver, MPPRECIPE_VERSION_NUM ) != 0)
    {
    }

    // An older (or no) database table exists
    if($cmp_db_version != 0) {
        $sql = "CREATE TABLE IF NOT EXISTS " . $recipes_table . " (
            recipe_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT(20)   UNSIGNED NOT NULL,
            recipe_title         TEXT,
            recipe_image         TEXT,
            summary              TEXT,
            rating               TEXT,
            rating_count         TEXT,
            prep_time            TEXT,
            cook_time            TEXT,
            total_time           TEXT,
            yield                TEXT,
            serving_size         VARCHAR(50),
            calories             VARCHAR(50),
            fat                  VARCHAR(50),
            ingredients          TEXT,
            instructions         TEXT,
            notes                TEXT,
            author               TEXT,
            original             TEXT,
            original_excerpt     TEXT,
            original_type        TEXT,
            cuisine              TEXT,
            type                 TEXT,
            created_at           TIMESTAMP DEFAULT NOW(),
            carbs                VARCHAR(50),
            protein              VARCHAR(50),
            fiber                VARCHAR(50),
            transfat             VARCHAR(50),
            unsatfat             VARCHAR(50),
            satfat               VARCHAR(50),
            sodium               VARCHAR(50),
            sugar                VARCHAR(50),
            cholesterol          VARCHAR(50),
            potassium            VARCHAR(50),
            vitamin_a            VARCHAR(50),
            calcium              VARCHAR(50),
            iron                 VARCHAR(50),
            nutrition_tags       TEXT,
            keywords             TEXT,
            server_recipe_id     VARCHAR(50)
        	);";

        $ratings_sql = "CREATE TABLE IF NOT EXISTS " . $recipe_ratings_table . " (
            rating_id  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            recipe_id  BIGINT(20) UNSIGNED NOT NULL,
            user_id    BIGINT(20) UNSIGNED NOT NULL,
            comment_id BIGINT(20) UNSIGNED NOT NULL,
            rating               TEXT,
            created_at           TIMESTAMP DEFAULT NOW()
        	);";

        $tags_sql = "CREATE TABLE IF NOT EXISTS " . $recipe_tags_table . " (
            recipe_id bigint(20)    UNSIGNED NOT NULL PRIMARY KEY,
            tagged                  TEXT,
            created_at              TIMESTAMP DEFAULT NOW()
            );";

        dbDelta($sql);
        dbDelta($ratings_sql);
        dbDelta($tags_sql);
    }

    // upgrade to 4.2.3
    if($cmp_db_version != 0) {
        $serving_description = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
            DB_NAME, $recipes_table, 'serving_description'
        ) );
        if ( ! empty( $serving_description ) ) {
            $sql = "ALTER TABLE " . $recipes_table . " DROP serving_description;";
            $wpdb->query($sql);
        }
    }

    // upgrade to 4.2.5
    if ($cmp_db_version != 0) {
        $keywords = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
            DB_NAME, $recipes_table, 'keywords'
        ));
        if (empty($keywords)) {
            $sql = "ALTER TABLE " . $recipes_table . " ADD keywords TEXT NULL DEFAULT NULL AFTER nutrition_tags;";
            $wpdb->query($sql);
        }

        $potassium = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
            DB_NAME, $recipes_table, 'potassium'
        ));
        if (empty($potassium)) {
            $sql = "ALTER TABLE " . $recipes_table . " ADD potassium VARCHAR(50) NULL DEFAULT NULL AFTER cholesterol;";
            $wpdb->query($sql);

            $sql = "ALTER TABLE " . $recipes_table . " ADD vitamin_a VARCHAR(50) NULL DEFAULT NULL AFTER cholesterol;";
            $wpdb->query($sql);

            $sql = "ALTER TABLE " . $recipes_table . " ADD calcium VARCHAR(50) NULL DEFAULT NULL AFTER cholesterol;";
            $wpdb->query($sql);

            $sql = "ALTER TABLE " . $recipes_table . " ADD iron VARCHAR(50) NULL DEFAULT NULL AFTER cholesterol;";
            $wpdb->query($sql);
        }
    }


    // enable feature post recipe to mealplannerpro.com
    if($cmp_db_version != 0) {
        if (!get_option('mpprecipe_recipe_to_mpp')){
            update_option("mpprecipe_recipe_to_mpp", 'Show');
        }
    }

    // update db version
    if($cmp_db_version != 0) {
        update_option("mpprecipe_db_version", $mpprecipe_db_version);
    }
}

function mpprecipe_install() {
    mpprecipe_register();

    // Auto-enable personalized option on activation
    update_option("mpprecipe_personalizedplugin", 'Show');
    update_option("mpprecipe_recipe_to_mpp", 'Show');
    mpprecipe_upgradedb();
}

add_action('admin_menu', 'mpprecipe_menu_pages');

// Adds module to left sidebar in wp-admin for MPPRecipe
function mpprecipe_menu_pages() {
    // Add the top-level admin menu
    $page_title = 'MealPlannerPro Recipe Plugin Settings';
    $menu_title = 'MealPlannerPro Recipe Plugin';
    $capability = 'manage_options';
    $menu_slug = 'mpprecipe-settings';
    $function = 'mpprecipe_settings';
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

    // Add submenu page with same slug as parent to ensure no duplicates
    $settings_title = 'Settings';
    add_submenu_page($menu_slug, $page_title, $settings_title, $capability, $menu_slug, $function);
}

function mpprecipe_print_options($opt_name, $opts_array)
{
    $options = '';

    foreach ($opts_array as $k => $v )
    {
        $o = get_option($opt_name);
    	if ($v === $o)
        	$options .= "<option value='$v' selected> $k </option>";
        else
         	$options .= "<option value='$v'> $k </option>";
	}

    return $options;
}
function mpprecipe_showhide_func( $self, $comp )
{
    return (strcmp($self, $comp) == 0 ? 'checked="checked"' : '');
}
// Adds 'Settings' page to the MPPRecipe module
function mpprecipe_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $mpprecipe_icon = MPPRECIPE_PLUGIN_DIRECTORY . "mpprecipe.png";

    if (!empty($_POST)) {

        $p = sanitize($_POST);

        $mealplannerpro_recipe_button_hide = $p['mealplannerpro-recipe-button-hide'];
        $mealplannerpro_attribution_hide   = $p['mealplannerpro-attribution-hide'];
        $printed_permalink_hide            = $p['printed-permalink-hide'];
        $printed_copyright_statement       = $p['printed-copyright-statement'];
        $stylesheet                        = $p['stylesheet'];
        $image_hide                        = $p['image-hide'];
        $image_hide_print                  = $p['image-hide-print'];
        $print_link_hide                   = $p['print-link-hide'];
        $ingredient_label                  = $p['ingredient-label'];
        $ingredient_label_hide             = $p['ingredient-label-hide'];
        $ingredient_list_type              = $p['ingredient-list-type'];
        $instruction_label                 = $p['instruction-label'];
        $instruction_label_hide            = $p['instruction-label-hide'];
        $instruction_list_type             = $p['instruction-list-type'];
        $notes_label                       = $p['notes-label'];
        $notes_label_hide                  = $p['notes-label-hide'];
        $prep_time_label                   = $p['prep-time-label'];
        $prep_time_label_hide              = $p['prep-time-label-hide'];
        $cook_time_label                   = $p['cook-time-label'];
        $cook_time_label_hide              = $p['cook-time-label-hide'];
        $total_time_label                  = $p['total-time-label'];
        $total_time_label_hide             = $p['total-time-label-hide'];
        $yield_label                       = $p['yield-label'];
        $yield_label_hide                  = $p['yield-label-hide'];
        $serving_size_label                = $p['serving-size-label'];
        $serving_size_label_hide           = $p['serving-size-label-hide'];
        $outer_border_style                = $p['outer-border-style'];
        $tagged_display                    = $p['tagged-display'];
        $custom_save_image                 = $p['custom-save-image'];
        $custom_print_image                = $p['custom-print-image'];
        $personalizedplugin                = $p['personalizedplugin'];
        $ratings                           = $p['ratings'];
        $recipe_to_mpp                     = $p['recipe_to_mpp'];
        $nutrition                         = $p['nutrition'];
        $swoop_id                          = $p['swoop_id'];
        $custom_html                       = $p['custom_html'];
        $nutrition_style                   = $p['nutrition_style'];
        $primary_color                     = $p['primary_color'];
        $secondary_color                   = $p['secondary_color'];
        $text_color                        = $p['text_color'];
        $link_color                        = $p['link_color'];
        $link_hover_color                  = $p['link_hover_color'];
        $link_underline                    = $p['link-underline'];
        $font                              = $p['font'];
        $font_size                         = $p['font_size'];

        update_option('mealplannerpro_recipe_button_hide', $mealplannerpro_recipe_button_hide);
        update_option('mealplannerpro_attribution_hide', $mealplannerpro_attribution_hide);
        update_option('mpprecipe_printed_permalink_hide', $printed_permalink_hide );
        update_option('mpprecipe_printed_copyright_statement', $printed_copyright_statement);
        update_option('mpprecipe_stylesheet', $stylesheet);
        update_option('mpprecipe_image_hide', $image_hide);
        update_option('mpprecipe_image_hide_print', $image_hide_print);
        update_option('mpprecipe_print_link_hide', $print_link_hide);
        update_option('mpprecipe_ingredient_label', $ingredient_label);
        update_option('mpprecipe_ingredient_label_hide', $ingredient_label_hide);
        update_option('mpprecipe_ingredient_list_type', $ingredient_list_type);
        update_option('mpprecipe_instruction_label', $instruction_label);
        update_option('mpprecipe_instruction_label_hide', $instruction_label_hide);
        update_option('mpprecipe_instruction_list_type', $instruction_list_type);
        update_option('mpprecipe_notes_label', $notes_label);
        update_option('mpprecipe_notes_label_hide', $notes_label_hide);
        update_option('mpprecipe_prep_time_label', $prep_time_label);
        update_option('mpprecipe_prep_time_label_hide', $prep_time_label_hide);
        update_option('mpprecipe_cook_time_label', $cook_time_label);
        update_option('mpprecipe_cook_time_label_hide', $cook_time_label_hide);
        update_option('mpprecipe_total_time_label', $total_time_label);
        update_option('mpprecipe_total_time_label_hide', $total_time_label_hide);
        update_option('mpprecipe_yield_label', $yield_label);
        update_option('mpprecipe_yield_label_hide', $yield_label_hide);
        update_option('mpprecipe_serving_size_label', $serving_size_label);
        update_option('mpprecipe_serving_size_label_hide', $serving_size_label_hide);
        update_option('mpprecipe_calories_label', $calories_label);
        update_option('mpprecipe_calories_label_hide', $calories_label_hide);
        update_option('mpprecipe_fat_label', $fat_label);
        update_option('mpprecipe_fat_label_hide', $fat_label_hide);
        update_option('mpprecipe_outer_border_style', $outer_border_style);
        update_option('mpprecipe_tagged_display', $tagged_display);
        update_option('mpprecipe_custom_save_image', $custom_save_image);
        update_option('mpprecipe_custom_print_image', $custom_print_image);
        update_option('mpprecipe_personalizedplugin', $personalizedplugin);
        update_option('mpprecipe_ratings', $ratings);
        update_option('mpprecipe_recipe_to_mpp', $recipe_to_mpp);
        update_option('mpprecipe_nutrition', $nutrition);
        update_option('mpprecipe_swoop_id', $swoop_id);
        update_option('mpprecipe_custom_html', $custom_html);
        update_option('mpprecipe_nutrition_style', $nutrition_style);
        update_option('mpprecipe_primary_color', $primary_color);
        update_option('mpprecipe_secondary_color', $secondary_color);
        update_option('mpprecipe_text_color', $text_color);
        update_option('mpprecipe_link_color', $link_color);
        update_option('mpprecipe_link_hover_color', $link_hover_color);
        update_option('mpprecipe_link_underline', $link_underline);
        update_option('mpprecipe_font', $font);
        update_option('mpprecipe_font_size', $font_size);

    } else {
        $mealplannerpro_recipe_button_hide = get_option('mealplannerpro_recipe_button_hide');
        $mealplannerpro_attribution_hide   = get_option('mealplannerpro_attribution_hide');
        $printed_permalink_hide            = get_option('mpprecipe_printed_permalink_hide');
        $printed_copyright_statement       = get_option('mpprecipe_printed_copyright_statement');
        $stylesheet                        = get_option('mpprecipe_stylesheet');
        $image_hide                        = get_option('mpprecipe_image_hide');
        $image_hide_print                  = get_option('mpprecipe_image_hide_print');
        $print_link_hide                   = get_option('mpprecipe_print_link_hide');
        $ingredient_label                  = get_option('mpprecipe_ingredient_label');
        $ingredient_label_hide             = get_option('mpprecipe_ingredient_label_hide');
        $ingredient_list_type              = get_option('mpprecipe_ingredient_list_type');
        $instruction_label                 = get_option('mpprecipe_instruction_label');
        $instruction_label_hide            = get_option('mpprecipe_instruction_label_hide');
        $instruction_list_type             = get_option('mpprecipe_instruction_list_type');
        $notes_label                       = get_option('mpprecipe_notes_label');
        $notes_label_hide                  = get_option('mpprecipe_notes_label_hide');
        $prep_time_label                   = get_option('mpprecipe_prep_time_label');
        $prep_time_label_hide              = get_option('mpprecipe_prep_time_label_hide');
        $cook_time_label                   = get_option('mpprecipe_cook_time_label');
        $cook_time_label_hide              = get_option('mpprecipe_cook_time_label_hide');
        $total_time_label                  = get_option('mpprecipe_total_time_label');
        $total_time_label_hide             = get_option('mpprecipe_total_time_label_hide');
        $yield_label                       = get_option('mpprecipe_yield_label');
        $yield_label_hide                  = get_option('mpprecipe_yield_label_hide');
        $serving_size_label                = get_option('mpprecipe_serving_size_label');
        $serving_size_label_hide           = get_option('mpprecipe_serving_size_label_hide');
        $calories_label                    = get_option('mpprecipe_calories_label');
        $calories_label_hide               = get_option('mpprecipe_calories_label_hide');
        $fat_label                         = get_option('mpprecipe_fat_label');
        $fat_label_hide                    = get_option('mpprecipe_fat_label_hide');
        $outer_border_style                = get_option('mpprecipe_outer_border_style');
        $tagged_display                    = get_option('mpprecipe_tagged_display');
        $custom_save_image                 = get_option('mpprecipe_custom_save_image');
        $custom_print_image                = get_option('mpprecipe_custom_print_image');
        $personalizedplugin                = get_option('mpprecipe_personalizedplugin');
        $ratings                           = get_option('mpprecipe_ratings');
        $recipe_to_mpp                     = get_option('mpprecipe_recipe_to_mpp');
        $nutrition                         = get_option('mpprecipe_nutrition');
        $swoop_id                          = get_option('mpprecipe_swoop_id');
        $custom_html                       = get_option('mpprecipe_custom_html');
        $nutrition_style                   = get_option('mpprecipe_nutrition_style');
        $primary_color                     = get_option('mpprecipe_primary_color');
        $secondary_color                   = get_option('mpprecipe_secondary_color');
        $text_color                        = get_option('mpprecipe_text_color');
        $link_color                        = get_option('mpprecipe_link_color');
        $link_hover_color                  = get_option('mpprecipe_link_hover_color');
        $link_underline                    = get_option('mpprecipe_link_underline');
        $font                              = get_option('mpprecipe_font');
        $font_size                         = get_option('mpprecipe_font_size');

    }

    $printed_copyright_statement = esc_attr($printed_copyright_statement);
    $ingredient_label            = esc_attr($ingredient_label);
    $instruction_label           = esc_attr($instruction_label);
    $notes_label                 = esc_attr($notes_label);
    $prep_time_label             = esc_attr($prep_time_label);
    $prep_time_label             = esc_attr($prep_time_label);
    $cook_time_label             = esc_attr($cook_time_label);
    $total_time_label            = esc_attr($total_time_label);
    $total_time_label            = esc_attr($total_time_label);
    $yield_label                 = esc_attr($yield_label);
    $serving_size_label          = esc_attr($serving_size_label);
    $calories_label              = esc_attr($calories_label);
    $fat_label                   = esc_attr($fat_label);
	$custom_save_image           = esc_attr($custom_save_image);
	$custom_print_image          = esc_attr($custom_print_image);

    $mealplannerpro_recipe_button_hide = mpprecipe_showhide_func( $mealplannerpro_recipe_button_hide, 'Hide' );
    $mealplannerpro_attribution_hide   = mpprecipe_showhide_func( $mealplannerpro_attribution_hide  , 'Hide' );
    $printed_permalink_hide            = mpprecipe_showhide_func( $printed_permalink_hide           , 'Hide' );
    $image_hide                        = mpprecipe_showhide_func( $image_hide                       , 'Hide' );
    $image_hide_print                  = mpprecipe_showhide_func( $image_hide_print                 , 'Hide' );
    $print_link_hide                   = mpprecipe_showhide_func( $print_link_hide                  , 'Hide' );
    $personalizedplugin                = mpprecipe_showhide_func( $personalizedplugin               , 'Show' );
    $ratings                           = mpprecipe_showhide_func( $ratings                          , 'Show' );
    $recipe_to_mpp                     = mpprecipe_showhide_func( $recipe_to_mpp                    , 'Show' );
    $nutrition                         = mpprecipe_showhide_func( $nutrition                        , 'Show' );
    $link_underline                    = mpprecipe_showhide_func( $link_underline                   , 'Underline' );

    // Stylesheet processing
    $stylesheet = (strcmp($stylesheet, 'mpprecipe-std') == 0 ? 'checked="checked"' : '');

    // Outer (hrecipe) border style
	$obs = '';
	$borders = array('None' => '', 'Solid' => '1px solid', 'Dotted' => '1px dotted', 'Dashed' => '1px dashed', 'Thick Solid' => '2px solid', 'Double' => 'double');
	foreach ($borders as $label => $code) {
		$obs .= '<option value="' . $code . '" ' . (strcmp($outer_border_style, $code) == 0 ? 'selected="true"' : '') . '>' . $label . '</option>';
	}

    $tagged_checked = '';
    $tagged_options = array('Display Tags' => 'Show', 'Don\'t Display Tags' => 'Hide');
    foreach ($tagged_options as $label => $code) {
        $tagged_checked .= '<option value="' . $code . '" ' . (strcmp($tagged_display, $code) == 0 ? 'selected="true"' : '') . '>' . $label . '</option>';
    }

    $ingredient_label_hide   = (strcmp($ingredient_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $ing_ul                  = (strcmp($ingredient_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ing_ol                  = (strcmp($ingredient_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ing_p                   = (strcmp($ingredient_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ing_div                 = (strcmp($ingredient_list_type, 'div') == 0 ? 'checked="checked"' : '');
    $instruction_label_hide  = (strcmp($instruction_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $ins_ul                  = (strcmp($instruction_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ins_ol                  = (strcmp($instruction_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ins_p                   = (strcmp($instruction_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ins_div                 = (strcmp($instruction_list_type, 'div') == 0 ? 'checked="checked"' : '');
    $prep_time_label_hide    = (strcmp($prep_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $cook_time_label_hide    = (strcmp($cook_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $total_time_label_hide   = (strcmp($total_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $yield_label_hide        = (strcmp($yield_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $serving_size_label_hide = (strcmp($serving_size_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $calories_label_hide     = (strcmp($calories_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $fat_label_hide          = (strcmp($fat_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $notes_label_hide        = (strcmp($notes_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $other_options           = '';
    $other_options_array     = array(
        'Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size',
        'Notes');

    $stylesheets_options_array = array(
        'EasyRecipe Style'        => 'mpprecipe-design23',
        'EasyRecipe Style But Nicer 1'        => 'mpprecipe-design24',
        'EasyRecipe Style But Nicer 2'        => 'mpprecipe-design22',
        'Elegant' => 'mpprecipe-std',
        'Elegant 2' => 'mpprecipe-design13',
        'Elegant 3' => 'mpprecipe-design14',
        'Elegant 4' => 'mpprecipe-design15',
        'Elegant 5' => 'mpprecipe-design16',
        'Elegant 6' => 'mpprecipe-design17',
        'Elegant 7' => 'mpprecipe-design18',
        'Elegant 8' => 'mpprecipe-design20',
        //'None'    => '',
        'Traditional 1'    => 'mpprecipe-design2',
        'Traditional 2'    => 'mpprecipe-design3',
        'Traditional 3'    => 'mpprecipe-design4',
        'Traditional 4'    => 'mpprecipe-design5',
        'Traditional 5'    => 'mpprecipe-design7',
        'Traditional 6'    => 'mpprecipe-design11',
        'Stand Out'        => 'mpprecipe-design6',
        'Stand Out 2'      => 'mpprecipe-design9',
        'Stand Out 3'      => 'mpprecipe-design10',
        'Compact'          => 'mpprecipe-design8',
        'Compact 2'        => 'mpprecipe-design19',
        'Compact 3'        => 'mpprecipe-design21',

    );
    $font_options_array = array(
        'Default'         => '',
        'Times New Roman' => 'Times New Roman',
        'Courier'         => 'Courier',
        'Arial'           => 'Arial',
    );
    $font_size_options_array = array(
        'Default'           => '',
        'Tiny'              => '0.5em',
        'Small'             => '0.75em',
        'Regular'           => '1em',
        'Large'             => '1.25em',
        'Extra Large'       => '1.5em',
    );

    $stylesheets_options = mpprecipe_print_options( 'mpprecipe_stylesheet', $stylesheets_options_array);
    $nutrition_options   = mpprecipe_print_options( 'mpprecipe_nutrition_style', array('Minimal' => 'minimal', 'Above' =>'above', 'Below' => 'below', 'Nutrition Panel' => 'nutrition_panel') );
    $font_options        = mpprecipe_print_options( 'mpprecipe_font', $font_options_array );
    $font_size_options   = mpprecipe_print_options( 'mpprecipe_font_size', $font_size_options_array );

    foreach ($other_options_array as $option) {
        $name = strtolower(str_replace(' ', '-', $option));
        $value = strtolower(str_replace(' ', '_', $option)) . '_label';
        $value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
        $other_options .= '<tr valign="top">
            <th scope="row">\'' . $option . '\' Label</th>
            <td><input type="text" name="' . $name . '-label" value="' . ${$value} . '" class="regular-text" /><br />
            <label><input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . ${$value_hide} . ' /> Don\'t show ' . $option . ' label</label></td>
        </tr>';
    }
    $style_img = plugins_url( '/preview-thumbs/' . get_option('mpprecipe_stylesheet').'.jpg', __FILE__ );


    echo '
        <script src="'. MPPRECIPE_PLUGIN_DIRECTORY .'/jscolor.min.js"></script>

        <style>
        .form-table label { line-height: 2.5; }
        hr { border: 1px solid #DDD; border-left: none; border-right: none; border-bottom: none; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form enctype="multipart/form-data" method="post" action="" name="mpprecipe_settings_form">
            <h2><img src="' . $mpprecipe_icon . '" /> MealPlannerPro Recipe Plugin Settings</h2>
			<h3>General</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Stylesheet</th>
                    <td>
                    <label>
						<select id="stylesheet_selector" name="stylesheet" onchange="updatePreview()"> ' . $stylesheets_options  . ' </select>
                    </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Stylesheet Preview</th>
                    <td>
                    <img id="preview_thumb" src="' . $style_img .  '" style="max-width:400px;" />
                    </td>
                </tr>

                <script type="text/javascript">
                    function updatePreview() {
                        var x = document.getElementById("stylesheet_selector").value;
                        var src = "' . plugins_url('/preview-thumbs/', __FILE__) . '"+ x + ".jpg"
                        document.getElementById("preview_thumb").src=src;
                    }
                </script>

                <tr valign="top">
                    <th scope="row">Personalized Plugin</th>
                    <td>
                    	<label><input type="checkbox" name="personalizedplugin" value="Show" ' . $personalizedplugin . ' /> Enable personalized plugin features</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Ratings Feature</th>
                    <td>
                    	<label><input type="checkbox" name="ratings" value="Show" ' . $ratings . ' /> Enable user ratings feature</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Post recipes to MPP</th>
                    <td>
                    	<label><input type="checkbox" name="recipe_to_mpp" value="Show" ' . $recipe_to_mpp . ' /> Automatically post new recipes to MPP</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nutrition Information</th>
                    <td>
                    	<label><input type="checkbox" name="nutrition" value="Show" ' . $nutrition . ' /> Enable display of recipe nutrition information</label>
                    </td>
                </tr>
                <tr valign="top">
                	<th scope="row">Nutrition Style</th>
                	<td>
                    <label>
						<select name="nutrition_style"> ' . $nutrition_options  . ' </select>
                    </label>
					</td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom Styles</th>
                    <td>
                    	<label> Primary Color: <input id="primary_color" type="text" class="jscolor {required:false}" name="primary_color" value="' . $primary_color . '" /> </label>
                        <button onclick="getElementById(\'primary_color\').value = \'\';return false">Clear</button>
                        <br/>
                    	<label> Secondary Color: <input id="secondary_color" type="text" class="jscolor {required:false}" name="secondary_color" value="' . $secondary_color . '" /> </label>
                        <button onclick="getElementById(\'secondary_color\').value = \'\';return false">Clear</button>
                        <br/>
                    	<label> Text Color: <input id="text_color" type="text" class="jscolor {required:false}" name="text_color" value="' . $text_color . '" /> </label>
                        <button onclick="getElementById(\'text_color\').value = \'\';return false">Clear</button>
                        <br/>
                        <label> Link Color: <input id="link_color" type="text" class="jscolor {required:false}" name="link_color" value="' . $link_color . '" /> </label>
                        <button onclick="getElementById(\'link_color\').value = \'\';return false">Clear</button>
                        <br/>
                        <label> Link Hover Color: <input id="link_hover_color" type="text" class="jscolor {required:false}" name="link_hover_color" value="' . $link_hover_color . '" /> </label>
                        <button onclick="getElementById(\'link_hover_color\').value = \'\';return false">Clear</button>
                        <br/>
                        <label><input type="checkbox" name="link-underline" value="Underline" ' . $link_underline . ' /> Underline Links</label>
                        <br/>
                        <label style="display:none">
                            Font: <select name="font"> ' . $font_options  . ' </select>
                        </label>
                        <label style="display:none">
                            Font Size: <select name="font_size"> ' . $font_size_options  . ' </select>
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Print Options</th>
                    <td>
                        <label><input type="checkbox" name="print-link-hide" value="Hide" ' . $print_link_hide . ' /> Don\'t show Print Button</label>
                        <br />
                        <label><input type="checkbox" name="image-hide-print" value="Hide" ' . $image_hide_print . ' />Use minimal print view (compact design, no recipe image or nutrition info)</label>
                    </td>

                </tr>
                <tr valign="top">
                    <th scope="row">Image Display</th>
                    <td>
                    	<label><input type="checkbox" name="image-hide" value="Hide" ' . $image_hide . ' /> Don\'t show Image in post</label>
                    </td>
                </tr>
                <tr valign="top">
                	<th scope="row">Tags Display</th>
                	<td>
						<select name="tagged-display">' . $tagged_checked . '</select>
					</td>
				</tr>
                <tr valign="top">
                	<th scope="row">Border Style</th>
                	<td>
						<select name="outer-border-style">' . $obs . '</select>
					</td>
				</tr>

<!-- Disable swoop option
                <tr valign="top">
                	<th scope="row">Swoop Integration</th>
                	<td>
                        <input type="text" name="swoop_id" value="' . $swoop_id . '" class="regular-text" />
                        <br />
                        If you have a <a href="http://swoop.com/publishers/" target="_blank" title="Swoop Link">Swoop</a> account, enter your Swoop ID to have automatic Swoop integration.
					</td>
				</tr>
-->
                <tr valign="top">
                	<th scope="row">Ad Tag</th>
                	<td>
                        <textarea type="text" name="custom_html" cols="100" rows="10">' . stripslashes_deep($custom_html) . '</textarea>
                        <br />
                        You can place an Ad Tag  into your recipe card by copying and pasting it into this textbox.
					</td>
				</tr>

            </table>
            <hr />
			<h3>Printing</h3>
            <table class="form-table">
                <tr valign="top" style="display:none">
                    <th scope="row">
                    	Custom Print Button
                    	<br />
                    	(Optional)
                    </th>
                    <td>
                        <input placeholder="URL to custom Print button image" type="text" name="custom-print-image" value="' . $custom_print_image . '" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Recipe Permalink</th>
                    <td><label><input type="checkbox" name="printed-permalink-hide" value="Hide" ' . $printed_permalink_hide . ' /> Don\'t show permalink in printed output</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Copyright Holder Name</th>
                    <td><input type="text" name="printed-copyright-statement" value="' . $printed_copyright_statement . '" class="regular-text" /></td>
                </tr>
            </table>
            <hr />
            <h3>Ingredients</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Ingredients\' Label</th>
                    <td><input type="text" name="ingredient-label" value="' . $ingredient_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="ingredient-label-hide" value="Hide" ' . $ingredient_label_hide . ' /> Don\'t show Ingredients label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Ingredients\' List Type</th>
                    <td><input type="radio" name="ingredient-list-type" value="ul" ' . $ing_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="ingredient-list-type" value="ol" ' . $ing_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="ingredient-list-type" value="p" ' . $ing_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="ingredient-list-type" value="div" ' . $ing_div . ' /> <label>Divs</label></td>
                </tr>
            </table>

            <hr />

            <h3>Instructions</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Instructions\' Label</th>
                    <td><input type="text" name="instruction-label" value="' . $instruction_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="instruction-label-hide" value="Hide" ' . $instruction_label_hide . ' /> Don\'t show Instructions label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Instructions\' List Type</th>
                    <td><input type="radio" name="instruction-list-type" value="ol" ' . $ins_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="instruction-list-type" value="ul" ' . $ins_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="instruction-list-type" value="p" ' . $ins_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="instruction-list-type" value="div" ' . $ins_div . ' /> <label>Divs</label></td>
                </tr>
            </table>

            <hr />

            <h3>Other Options</h3>
            <table class="form-table">
                ' . $other_options . '
            </table>

            <p><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
        </form>
    </div>'. mpp_convert_js() . mpp_convert_ziplist_entries_form() . mpp_convert_yummly_entries_form() . mpp_convert_easyrecipe_entries_form() . mpp_revert_yummly_entries_form() . mpp_revert_ziplist_entries_form() .  mpp_revert_easyrecipe_entries_form() . mpp_convert_yumprint_entries_form() . mpp_revert_yumprint_entries_form();
}

function mpp_update_subdomain_call($url)
{
    $h = mpp_gethostname();
    $i = get_option('mpprecipe_subdomain_id');
    $r = mpprequest( 'get', $url, array( 'host' => $h, 'id' => $i ) );
    mpp_update_subdomain( $r );
}
add_action( 'wp_ajax_update_subdomain_by_ip', 'mpp_update_subdomain_by_ip' );
function mpp_update_subdomain_by_ip()
{
    $u = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/api/wordpress/SubdomainByIp";
    mpp_update_subdomain_call( $u );
}
add_action( 'wp_ajax_update_subdomain_by_subdomain', 'mpp_update_subdomain_by_subdomain' );
function mpp_update_subdomain_by_subdomain()
{
    $u = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN. "/api/wordpress/SubdomainBySubdomain";
    mpp_update_subdomain_call( $u );
}
add_action( 'wp_ajax_update_subdomain_by_id', 'mpp_update_subdomain_by_id' );
function mpp_update_subdomain_by_id()
{
    $u = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/api/wordpress/SubdomainById";
    mpp_update_subdomain_call( $u );
}

function mpp_update_subdomain_button()
{
    return "
    <div id='update_subdomain' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
        <h4> Regenerate subdomain</h4>
        <p>
        </p>
        <button onclick='update_subdomain()'>Update Subdomain</button>
    </div>
    <script>
        function update_subdomain()
        {

            var r   = new XMLHttpRequest()
            r.open( 'GET', ajaxurl+data, true )

            var cid = action + '_' + lvendor + '_entries_container'

            document.getElementById(cid).innerHTML = 'Converting recipes. This can take a few minutes, please do not leave the page.'
            window.onbeforeunload = function () { return 'Recipes are still being converted, if you leave this page you will not know if it was successful.' };

            r.onreadystatechange = function()
            {
                if( r.readyState == 4 && r.status == 200 )
                    document.getElementById(cid).innerHTML = r.responseText;

                window.onbeforeunload = null
            }
            r.send()

        }
    </script>
";
}


function mpprecipe_tinymce_plugin($plugin_array) {
	$plugin_array['mpprecipe'] = plugins_url( '/mpprecipe_editor_plugin.js?sver=' . MPPRECIPE_VERSION_NUM, __FILE__ );
	return $plugin_array;
}

function mpprecipe_register_tinymce_button($buttons) {
   array_push($buttons, "mpprecipe");
   return $buttons;
}

function mpprecipe_add_recipe_button() {

    // check user permissions
    if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
   	return;
    }

	// check if WYSIWYG is enabled
	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'mpprecipe_tinymce_plugin');
		add_filter('mce_buttons', 'mpprecipe_register_tinymce_button');
	}
}

function mpp_keyvalue($key, $array)
{
    if( isset($array[$key]) )
        return $array[$key];
    else
        return;
}

// Content for the popup iframe when creating or editing a recipe
function mpprecipe_iframe_content($post_info = null, $get_info = null) {
    // Load pluggable functions.
    require( ABSPATH . WPINC . '/pluggable.php' );
    require( ABSPATH . WPINC . '/pluggable-deprecated.php' );
    require_once plugin_dir_path(__FILE__) . 'classes/mpp-api.php';
    $iframe_domain = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN;
    $recipe_id = 0;
    $server_recipe_id = '';
    $iframe_src = '';
    $prep_time_input = '';
    $cook_time_input = '';
    $total_time_input = '';

    if ($post_info || $get_info) {

        if( isset($get_info["post_id"]) and strpos($get_info["post_id"], '-') !== false ) {
        	$iframe_title = "Update Your Recipe";
        	$submit = "Update Recipe";
        } else {
    		$iframe_title = "Add a Recipe";
    		$submit = "Add Recipe";
        }


        if ($get_info["post_id"] && !isset($get_info["add-recipe-button"]) && strpos($get_info["post_id"], '-') !== false) {
            $recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
            $recipe = mpprecipe_select_recipe_db($recipe_id);
            $recipe_title = $recipe->recipe_title;
            $recipe_image = $recipe->recipe_image;
            $summary = $recipe->summary;
            $author  = $recipe->author;
            $notes = $recipe->notes;
            $server_recipe_id = $recipe->server_recipe_id;
            $iframe_src = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/api/wordpress/recipeAnalyzer/?server_recipe_id=" . $server_recipe_id;
            #$rating = $recipe->rating;
            $ss = array();
            if (class_exists('DateInterval') and MPPRECIPE_AUTO_HANDLE_TOTALTIME ) {
                try {
                    $prep_time = new DateInterval($recipe->prep_time);
                    $prep_time_seconds = $prep_time->s;
                    $prep_time_minutes = $prep_time->i;
                    $prep_time_hours = $prep_time->h;
                    $prep_time_days = $prep_time->d;
                    $prep_time_months = $prep_time->m;
                    $prep_time_years = $prep_time->y;
                } catch (Exception $e) {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                try {
                    $cook_time = new DateInterval($recipe->cook_time);
                    $cook_time_seconds = $cook_time->s;
                    $cook_time_minutes = $cook_time->i;
                    $cook_time_hours = $cook_time->h;
                    $cook_time_days = $cook_time->d;
                    $cook_time_months = $cook_time->m;
                    $cook_time_years = $cook_time->y;
                } catch (Exception $e) {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }

                try {
                    $total_time = new DateInterval($recipe->total_time);
                    $total_time_seconds = $total_time->s;
                    $total_time_minutes = $total_time->i;
                    $total_time_hours = $total_time->h;
                    $total_time_days = $total_time->d;
                    $total_time_months = $total_time->m;
                    $total_time_years = $total_time->y;
                } catch (Exception $e) {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            } else {
                if (preg_match('(^[A-Z0-9]*$)', $recipe->prep_time) == 1) {
                    preg_match('(\d*S)', $recipe->prep_time, $pts);
                    $prep_time_seconds = isset( $pts[0] ) ? str_replace('S', '', $pts[0]) : '';
                    preg_match('(\d*M)', $recipe->prep_time, $ptm, PREG_OFFSET_CAPTURE, strpos($recipe->prep_time, 'T'));
                    $prep_time_minutes = isset( $ptm[0][0] ) ? str_replace('M', '', $ptm[0][0]) : '';
                    preg_match('(\d*H)', $recipe->prep_time, $pth);
                    $prep_time_hours = isset( $pth[0] ) ? str_replace('H', '', $pth[0]) : '';
                    preg_match('(\d*D)', $recipe->prep_time, $ptd);
                    $prep_time_days = isset( $ptd[0] ) ? str_replace('D', '', $ptd[0]) : '';
                    preg_match('(\d*M)', $recipe->prep_time, $ptmm);
                    $prep_time_months = isset( $ptmm[0] ) ? str_replace('M', '', $ptmm[0]) : '';
                    preg_match('(\d*Y)', $recipe->prep_time, $pty);
                    $prep_time_years = isset( $pty[0] ) ? str_replace('Y', '', $pty[0]) : '';
                } else {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                if (preg_match('(^[A-Z0-9]*$)', $recipe->cook_time) == 1) {
                    preg_match('(\d*S)', $recipe->cook_time, $cts);
                    $cook_time_seconds = isset( $cts[0] ) ? str_replace('S', '', $cts[0]) : '';
                    preg_match('(\d*M)', $recipe->cook_time, $ctm, PREG_OFFSET_CAPTURE, strpos($recipe->cook_time, 'T'));
                    $cook_time_minutes = isset( $ctm[0][0] ) ? str_replace('M', '', $ctm[0][0]) : '';
                    preg_match('(\d*H)', $recipe->cook_time, $cth);
                    $cook_time_hours = isset( $cth[0] ) ? str_replace('H', '', $cth[0]) : '';
                    preg_match('(\d*D)', $recipe->cook_time, $ctd);
                    $cook_time_days = isset( $ctd[0] ) ? str_replace('D', '', $ctd[0]) : '';
                    preg_match('(\d*M)', $recipe->cook_time, $ctmm);
                    $cook_time_months = isset( $ctmm[0] ) ? str_replace('M', '', $ctmm[0]) : '';
                    preg_match('(\d*Y)', $recipe->cook_time, $cty);
                    $cook_time_years = isset( $cty[0] ) ? str_replace('Y', '', $cty[0]) : '';
                } else {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }

                if (preg_match('(^[A-Z0-9]*$)', $recipe->total_time) == 1) {
                    preg_match('(\d*S)', $recipe->total_time, $tts);
                    $total_time_seconds = isset( $tts[0] ) ? str_replace('S', '', $tts[0]) : '';
                    preg_match('(\d*M)', $recipe->total_time, $ttm, PREG_OFFSET_CAPTURE, strpos($recipe->total_time, 'T'));
                    $total_time_minutes = isset( $ttm[0][0] ) ? str_replace('M', '', $ttm[0][0]) : '';
                    preg_match('(\d*H)', $recipe->total_time, $tth);
                    $total_time_hours = isset( $tth[0] ) ? str_replace('H', '', $tth[0]) : '';
                    preg_match('(\d*D)', $recipe->total_time, $ttd);
                    $total_time_days = isset( $ttd[0] ) ? str_replace('D', '', $ttd[0]) : '';
                    preg_match('(\d*M)', $recipe->total_time, $ttmm);
                    $total_time_months = isset( $ttmm[0] ) ? str_replace('M', '', $ttmm[0]) : '';
                    preg_match('(\d*Y)', $recipe->total_time, $tty);
                    $total_time_years = isset( $tty[0] ) ? str_replace('Y', '', $tty[0]) : '';
                } else {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            }

            $yield = $recipe->yield;
            $serving_size = $recipe->serving_size;
            $ingredients = $recipe->ingredients;
            $instructions = $recipe->instructions;
        } else {
        	foreach ($post_info as $key=>$val) {
        		$post_info[$key] = stripslashes($val);
        		if ($key == 'tagged') $post_info[$key] = $val;
        	}

            $recipe_id = mpp_keyvalue( "recipe_id", $post_info );
            if( !isset($get_info["add-recipe-button"] ))
                 $recipe_title = get_the_title( mpp_keyvalue( "post_id", $get_info ) );
            else
                 $recipe_title = mpp_keyvalue( "recipe_title", $post_info );
            $recipe_image = mpp_keyvalue( "recipe_image", $post_info );
            $summary = mpp_keyvalue( "summary", $post_info );
            $author  = mpp_keyvalue( "author", $post_info );
            $notes = mpp_keyvalue( "notes", $post_info );
            #$rating = mpp_keyvalue( "rating", $post_info );
            $prep_time_seconds = mpp_keyvalue( "prep_time_seconds", $post_info );
            $prep_time_minutes = mpp_keyvalue( "prep_time_minutes", $post_info );
            $prep_time_hours = mpp_keyvalue( "prep_time_hours", $post_info );
            $prep_time_days = mpp_keyvalue( "prep_time_days", $post_info );
            $prep_time_weeks = mpp_keyvalue( "prep_time_weeks", $post_info );
            $prep_time_months = mpp_keyvalue( "prep_time_months", $post_info );
            $prep_time_years = mpp_keyvalue( "prep_time_years", $post_info );
            $cook_time_seconds = mpp_keyvalue( "cook_time_seconds", $post_info );
            $cook_time_minutes = mpp_keyvalue( "cook_time_minutes", $post_info );
            $cook_time_hours = mpp_keyvalue( "cook_time_hours", $post_info );
            $cook_time_days = mpp_keyvalue( "cook_time_days", $post_info );
            $cook_time_weeks = mpp_keyvalue( "cook_time_weeks", $post_info );
            $cook_time_months = mpp_keyvalue( "cook_time_months", $post_info );
            $cook_time_years = mpp_keyvalue( "cook_time_years", $post_info );
            $total_time_seconds = mpp_keyvalue( "total_time_seconds", $post_info );
            $total_time_minutes = mpp_keyvalue( "total_time_minutes", $post_info );
            $total_time_hours = mpp_keyvalue( "total_time_hours", $post_info );
            $total_time_days = mpp_keyvalue( "total_time_days", $post_info );
            $total_time_weeks = mpp_keyvalue( "total_time_weeks", $post_info );
            $total_time_months = mpp_keyvalue( "total_time_months", $post_info );
            $total_time_years = mpp_keyvalue( "total_time_years", $post_info );
            $yield = mpp_keyvalue( "yield", $post_info );
            $serving_size = mpp_keyvalue( "serving_size", $post_info );
            $ingredients = mpp_keyvalue( "ingredients", $post_info );
            $instructions = mpp_keyvalue( "instructions", $post_info );

            if ($recipe_title != null && $recipe_title != '' && $ingredients != null && $ingredients != '') {
                $recipe_id = mpprecipe_insert_db($post_info);
            }
        }
    }

	$recipe_title       = esc_attr($recipe_title);
	$recipe_image       = esc_attr($recipe_image);
	$prep_time_hours    = esc_attr($prep_time_hours);
	$prep_time_minutes  = esc_attr($prep_time_minutes);
	$cook_time_hours    = esc_attr($cook_time_hours);
	$cook_time_minutes  = esc_attr($cook_time_minutes);
	$total_time_hours   = esc_attr($total_time_hours);
	$total_time_minutes = esc_attr($total_time_minutes);
	$yield              = esc_attr($yield);
	$serving_size       = esc_attr($serving_size);
	$ingredients        = esc_textarea($ingredients);
	$instructions       = esc_textarea($instructions);
	$summary            = esc_textarea($summary);
	$notes              = esc_textarea($notes);

    $id = (int) $_REQUEST["post_id"];
    $plugindir = MPPRECIPE_PLUGIN_DIRECTORY;
    $submitform = '';
    if ($post_info != null) {
        $submitform .= "<script>window.onload = MPPRecipeSubmitForm;</script>";
    }

    if (class_exists('DateInterval') and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
        $total_time_input_container = '';
    else
    {
        $total_time_input_container = <<<HTML
                <p class="cls"><label>Total Time</label>
                    $total_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="240" id='total_time_hours' name='total_time_hours' value='$total_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60"  id='total_time_minutes' name='total_time_minutes' value='$total_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
HTML;
    }

    $link_builder      = mpprecipe_create_link();
    $nutrition_and_tag = mpprecipe_create_nutrition_and_tag($get_info);
    $tag_manager_form  = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/api/wordpress/tagManager";
    echo <<<HTML

<!DOCTYPE html>
<head>
	<link rel="stylesheet" href="$plugindir/mpprecipe-dlog.css?v=20180607" type="text/css" media="all" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css"/>

    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>

    <script src="//code.jquery.com/jquery-1.10.2.js"></script>

    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

    <script type="text/javascript">//<!CDATA[

        function MPPRecipeSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;

            if (title==null || title=='') {
                $('#recipe-title input').addClass('input-error');
                $('#recipe-title').append('<p class="error-message">You must enter a title for your recipe.</p>');

                return false;
            }

			// BDJ: An image is required.
			/*
            var image = document.forms['recipe_form']['recipe_image'].value;
            if (image==null || image=='') {
                $('#recipe-image input').addClass('input-error');
                $('#recipe-image').append('<p class="error-message">You must enter an image for your recipe.</p>');

                return false;
            }
			*/


            var ingredients = $('#mpprecipe_ingredients textarea').val();
            if (ingredients==null || ingredients=='' || ingredients==undefined) {
                $('#mpprecipe_ingredients textarea').addClass('input-error');
                $('#mpprecipe_ingredients').append('<p class="error-message">You must enter at least one ingredient.</p>');

                return false;
            }
            window.parent.MPPRecipeInsertIntoPostEditor('$recipe_id');
            top.tinymce.activeEditor.windowManager.close(window);
        }

        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                return false;
            });
        });

    //]]>

    </script>
    $submitform
</head>
<body id="mpprecipe-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
        <h3 class='mpprecipe-title'>$iframe_title</h3>

        <div id='mpprecipe-form-items'>
            <input type='hidden' name='post_id' value='$id' />
            <input type='hidden' name='recipe_id' value='$recipe_id' />
            <p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
            <p id='recipe-image'><label>Recipe Image </label> <input type='text' name='recipe_image' value='$recipe_image' /></p>

            $link_builder

            <p id='mpprecipe_ingredients'  class='cls'><label>Ingredients <span class='required'>*</span> <small>Put each ingredient on a separate line.  There is no need to use bullets for your ingredients. To add sub-headings put them on a new line beginning with "!". Example will be "!for the dressing:"</small></label><textarea style="resize: vertical;;" id='ingredients_textbox' name='ingredients'>$ingredients</textarea></label></p>
            
            $nutrition_and_tag
            
            <p id='mpprecipe-instructions' class='cls'><label>Instructions <small>Press return after each instruction. There is no need to number your instructions.</small></label><textarea style="resize: vertical;" id="instructions_textbox" name='instructions'>$instructions</textarea></label></p>
            <p><a href='#' id='more-options-toggle'>More options</a></p>
            <div id='more-options'>
                <p class='cls'><label>Author</label> <input type='text' name='author' value='$author'/></p>

                <p class='cls'><label>Summary</label> <textarea style="resize: vertical;" id='summary_textbox' name='summary'>$summary</textarea></p>

                <p class="cls"><label>Prep Time</label>
                    $prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" id='prep_time_hours' name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" id='prep_time_minutes' name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Cook Time</label>
                    $cook_time_input
                    <span class="time">
                    	<span><input type='number' min="0" max="24" id='cook_time_hours' name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" id='cook_time_minutes' name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                $total_time_input_container
                <p><label>Yield</label> <input type='text' name='yield' placeholder='for example 1 pie, 1 loaf of bread etc.' value='$yield' /></p>
                <p><label>Serving Size</label> <input type='text' name='serving_size' id='serving_size' placeholder='for example 8 pieces of pie (used to calculate nutrition per serving)' value='$serving_size' /></p>
                <p class='cls'><label>Notes</label> <textarea style="resize: vertical;" id='notes_textbox' name='notes'>$notes</textarea></label></p>
            </div>
            <input type='submit' value='$submit' name='add-recipe-button' />
        </div>
    </form>
</body>

<script>
        var g = function( id ) { return document.getElementById( id ) }
        var v = function( id ) {
            var v = parseInt( g(id).value )
            return isNaN( v ) ? 0 : v
        }

        function calc()
        {
            var h = v('cook_time_hours')   + v('prep_time_hours')
            var m = v('cook_time_minutes') + v('prep_time_minutes')

            var h_from_m  = Math.floor(m/60)

            // minutes after hour-equivalents removed
            var m = m % (60*Math.max(h_from_m,1))
            var h = h + h_from_m

            g('total_time_hours').value   =  h
            g('total_time_minutes').value =  m
        }

        g('cook_time_hours').onchange   = calc
        g('cook_time_minutes').onchange = calc
        g('prep_time_hours').onchange   = calc
        g('prep_time_minutes').onchange = calc

        function iFrameLoaded() {
            var deferred = jQuery.Deferred(),
                iframe = jQuery("<iframe width='100%' height='100%' frameborder='0' allowtransparency='true'></iframe>").attr({
                    "id": "recipe_$server_recipe_id",
                    "src": "$iframe_src",
                });

            iframe.load(deferred.resolve);
            jQuery("#nutrition-analyzer-load").html(iframe);

            deferred.done(function() {
                jQuery("#nutrition-analyzer-message").hide();
            });

            return deferred.promise();
        }

        jQuery('#nutrition-analyzer-button').on('click', function (e) {
            e.preventDefault();
            // disable tag manager
            jQuery('#tag-manager-content').hide();
            jQuery('#tag-manager-button').removeClass('clicked');
            
            // enable nutrition analyer
            if (jQuery('#nutrition-analyzer-button').hasClass('clicked') == false) {
                jQuery('#nutrition-analyzer-button').addClass('clicked');
                jQuery("#nutrition-analyzer-message").show();
                jQuery('#nutrition-analyzer-iframe').show();
                jQuery.when(iFrameLoaded()).then(function () {
                    // all done
                });
            } else {
                jQuery('#nutrition-analyzer-button').removeClass('clicked');
                jQuery('#nutrition-analyzer-iframe').hide();
            }
        });
        
        jQuery('#tag-manager-button').on('click', function (e) {
            e.preventDefault();
            // disable nutrition analyzer 
            jQuery('#nutrition-analyzer-iframe').hide();
            jQuery('#nutrition-analyzer-button').removeClass('clicked');
            
            // enable tag manager
            if (jQuery('#tag-manager-button').hasClass('clicked') == false) {
                jQuery('#tag-manager-button').addClass('clicked');
                jQuery('#tag-manager-content').show();
                if (jQuery('#tag-manager-load').hasClass('loaded') == false) {
                    jQuery('#tag-manager-message').show();
                    jQuery.ajax({
                        async: true,   // this will solve the problem
                        type: "POST",
                        url: "$tag_manager_form?ModPagespeed=off",
                        data: { ingredients : jQuery('#ingredients_textbox').val(), recipe_name : document.forms['recipe_form']['recipe_title'].value  },
                        dataType: "html",
                    }).done(function(responses) {
                        jQuery('#tag-manager-message').hide();
                        jQuery('#tag-manager-load').html(responses);
                        jQuery('#tag-manager-load').addClass('loaded');
                    });
                }
            } else {
                jQuery('#tag-manager-button').removeClass('clicked');
                jQuery('#tag-manager-content').hide();
            }
        });

        // Create IE + others compatible event handler
        var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
        var eventer = window[eventMethod];
        var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";

        // Listen to message from child window
        eventer(messageEvent,function(e) {
            if (e.data == 'done'){
                jQuery('#nutrition-analyzer-iframe').hide();
            } else {
                var data = JSON.parse(e.data);
                jQuery('#serving_size').val(data.servings);
            }
        },false);
</script>

HTML;
}

/**
 * Deal with the aggregation of input duration-time parts.
 */
function collate_time_input( $type, $post )
{
    $duration_units = array(
        $type . '_time_years'  => 'Y',
        $type . '_time_months' => 'M',
        $type . '_time_days'   => 'D',
    );
    $time_units    = array(
        $type . '_time_hours'   => 'H',
        $type . '_time_minutes' => 'M',
        $type . '_time_seconds' => 'S',
    );

    if (( empty($post[$type . '_time_years'])
        && empty($post[$type . '_time_months'])
        && empty($post[$type . '_time_days'])
        && empty($post[$type . '_time_hours'])
        && empty($post[$type . '_time_minutes'])
        && empty($post[$type . '_time_seconds'])
    ))
    {
        if( isset( $post[$type . '_time'] ) )
            $o = $post[$type . '_time'];
        else
            $o = '';
    }
    else
    {
        $o = 'P';
        foreach($duration_units as $d => $u)
        {
            if( !empty($post[$d]) ) $time .= $post[$d] . $u;
        }
        if (   !empty($post[$type . '_time_hours'] )
            || !empty($post[$type . '_time_minutes'])
            || !empty($post[$type . '_time_seconds'])
        )
            $o .= 'T';
        foreach( $time_units as $t => $u )
        {
            if( !empty($post[$t]) ) $o .= $post[$t] . $u;
        }
    }

    return $o;
}

// Inserts the recipe into the database
function mpprecipe_insert_db($post_info) {
    global $wpdb;

    $recipe      = array ();
    $recipe_keys = array (
        "recipe_title" , "recipe_image", "summary",
        #"rating",  Not collected in form submission
        #"calories",
        #"fat",
        "yield",
        "serving_size",
        "ingredients", "instructions",
        "notes",
        "author", "keywords"
    );
    foreach( $recipe_keys as $k )
        $recipe[ $k ] = $post_info[ $k ];

    $recipe["prep_time"]  = collate_time_input( 'prep',  $post_info );
    $recipe["cook_time"]  = collate_time_input( 'cook',  $post_info );
    $recipe["total_time"] = collate_time_input( 'total', $post_info );

    $recipe_id = $post_info['recipe_id'];
    $post_id   = $post_info["post_id"];
    if (!empty($recipe_id) and mpprecipe_select_recipe_db($recipe_id)) {
        $wpdb->update($wpdb->prefix . "mpprecipe_recipes", $recipe, array('recipe_id' => $recipe_id));
    } else {
        $exits_recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "mpprecipe_recipes where post_id='$post_id' ", ARRAY_A);
        if ($exits_recipe) {
            $recipe_id           = $exits_recipe['recipe_id'];
            $recipe['recipe_id'] = $exits_recipe['recipe_id'];
            $wpdb->update($wpdb->prefix . "mpprecipe_recipes", $recipe, array('post_id' => $post_id));

        } else {
            $recipe["post_id"] = $post_id;
            $wpdb->insert($wpdb->prefix . "mpprecipe_recipes", $recipe);
            $recipe_id = $wpdb->insert_id;

        }
    }

    if ($recipe_id) {
        $exits_tagged        = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "mpprecipe_tags where recipe_id='$recipe_id' ", ARRAY_A);
        $tagged['recipe_id'] = $recipe_id;
        $tagged['tagged']    = serialize($post_info['tagged']);
        if ($exits_tagged) {
            $wpdb->update($wpdb->prefix . "mpprecipe_tags", $tagged, array('recipe_id' => $recipe_id));
        } else {
            $wpdb->insert($wpdb->prefix . "mpprecipe_tags", $tagged);
        }
    }

    return $recipe_id;
}

// Inserts the recipe into the post editor
function mpprecipe_plugin_footer() {
	$url = site_url();
	$plugindir = MPPRECIPE_PLUGIN_DIRECTORY;

    echo <<< HTML
    <style type="text/css" media="screen">
        #wp_editrecipebtns { position:absolute;display:block;z-index:999998; }
        #wp_editrecipebtn { margin-right:20px; }
        #wp_editrecipebtn,#wp_delrecipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_editrecipebtn:hover,#wp_delrecipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
        .mce-window .mce-container-body.mce-abs-layout
        {
            -webkit-overflow-scrolling: touch;
            overflow-y: auto;
        }
    </style>
    <script>//<![CDATA[
    var baseurl = '$url';          // This variable is used by the editor plugin
    var plugindir = '$plugindir';  // This variable is used by the editor plugin

        function MPPRecipeInsertIntoPostEditor(rid) {
            tb_remove();

            var ed;

            var output = '<img id="mpprecipe-recipe-';
            output += rid;
						output += '" class="mpprecipe-recipe" src="' + plugindir + '/mpprecipe-placeholder.png" />';

        	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() && ed.id=='content') {  //path followed when in Visual editor mode
        		ed.focus();
        		if ( tinymce.isIE )
        			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

        		ed.execCommand('mceInsertContent', false, output);

        	} else if (typeof tinyMCE != 'undefined') {
        	    ed = tinyMCE.activeEditor
                output = '[mpprecipe-recipe:';
                output += rid;
                output += ']';
                ed.execCommand('mceInsertContent', false, output);
        	} else if ( typeof edInsertContent == 'function' ) {  // path followed when in HTML editor mode
                output = '[mpprecipe-recipe:';
                output += rid;
                output += ']';
                edInsertContent(edCanvas, output);
        	} else {
                output = '[mpprecipe-recipe:';
                output += rid;
                output += ']';
        		jQuery( edCanvas ).val( jQuery( edCanvas ).val() + output );
        	}
        }
    //]]></script>
HTML;
}

add_action('admin_footer', 'mpprecipe_plugin_footer');

// Converts the image to a recipe for output
function mpprecipe_convert_to_recipe($post_text) {
    $output = $post_text;
    $needle_old = 'id="mpprecipe-recipe-';
    $preg_needle_old = '/(id)=("(mpprecipe-recipe-)[0-9^"]*")/i';
    $needle = '[mpprecipe-recipe:';
    $preg_needle = '/\[mpprecipe-recipe:([0-9]+)\]/i';

    if (strpos($post_text, $needle_old) !== false) {
        // This is for backwards compatability. Please do not delete or alter.
        preg_match_all($preg_needle_old, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('id="mpprecipe-recipe-', '', $match);
            $recipe_id = str_replace('"', '', $recipe_id);
            $recipe = mpprecipe_select_recipe_db($recipe_id);
            $formatted_recipe = mpprecipe_format_recipe($recipe);
            $output = str_replace('<img id="mpprecipe-recipe-' . $recipe_id . '" class="mpprecipe-recipe" src="' . plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/mpprecipe-placeholder.png?ver=1.0" alt="MPP Recipe Placeholder" />', $formatted_recipe, $output);
        }
    }

    if (strpos($post_text, $needle) !== false) {
        preg_match_all($preg_needle, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('[mpprecipe-recipe:', '', $match);
            $recipe_id = str_replace(']', '', $recipe_id);
            $recipe = mpprecipe_select_recipe_db($recipe_id);
            $formatted_recipe = mpprecipe_format_recipe($recipe);
            $output = str_replace('[mpprecipe-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
        }
    }

    return $output;
}


# Allow MPP formatting of ziplist entries without conversion
function mpp_format_ziplist_entries( $output )
{
    $zl_id   = 'amd-zlrecipe-recipe';
    # Match string that
    # - opens with     <img id="      or     [
    # - contains amd-zlrecipe-recipe
    # - followed by a : or -
    # - followed by a string of digits
    # - closed by a    "(anything) /> or     ]
    # FIXME: Restore legacy support.
    $regex   = '/\[amd-zlrecipe-recipe:(\d+)\]/i';
    $matches = array();

    if( strpos( $output, $zl_id ) === False )
        return $output;

    preg_match_all( $regex, $output, $matches );

    foreach( $matches[1] as $match_index => $recipe_id )
    {
        $matched_str      = $matches[0][$match_index];

        $recipe           = mpprecipe_select_recipe_db( $recipe_id, 'amd_zlrecipe_recipes' );
        $formatted_recipe = mpprecipe_format_recipe($recipe);

        $output = str_replace( $matched_str, $formatted_recipe, $output );
    }

    return $output;
}


// BDJ
add_filter('the_content', 'mpprecipe_convert_to_recipe');

// Pulls a recipe from the db
function mpprecipe_select_recipe_db($recipe_id, $table = 'mpprecipe_recipes' ) {
    global $wpdb;
    $sql    = "SELECT r.*, t.tagged FROM $wpdb->prefix$table AS r LEFT JOIN  " . $wpdb->prefix . "mpprecipe_tags AS t ON (r.recipe_id = t.recipe_id) WHERE r.recipe_id='$recipe_id' ";
    //return $wpdb->get_row( "SELECT * FROM $wpdb->prefix$table WHERE recipe_id=$recipe_id" );
    return $wpdb->get_row($sql);
}

// Format an ISO8601 duration for human readibility
function mpprecipe_format_duration($duration)
{
    $date_abbr = array(
        'y' => 'year', 'm' => 'month',
        'd' => 'day', 'h' => 'hr',
        'i' => 'min', 's' => 'second'
    );
	$result = '';

    if (class_exists('DateInterval'))
    {
		try {
            if( !($duration instanceof DateInterval ))
		        $duration = new DateInterval($duration);

            foreach ($date_abbr as $abbr => $name)
            {
                if ($duration->$abbr > 0)
                {
					$result .= $duration->$abbr . ' ' . $name;

					if ($duration->$abbr > 1)
						$result .= '';

					$result .= ', ';
				}
			}

			$result = trim($result, ' \t,');
		} catch (Exception $e) {
			$result = $duration;
		}
	} else { // else we have to do the work ourselves so the output is pretty
		$arr = explode('T', $duration);

        // This mimics the DateInterval property name
        $arr[1]   = str_replace('M', 'I', $arr[1]);

		$duration = implode('T', $arr);

        foreach ($date_abbr as $abbr => $name)
        {
            if (preg_match('/(\d+)' . $abbr . '/i', $duration, $val))
            {
                $result .= $val[1] . ' ' . $name;

                if ($val[1] > 1)
                    $result .= 's';

                $result .= ', ';
            }
		}

		$result = trim($result, ' \t,');
	}


    # if time > h, remove 'min' from display (e.g. 1h 45mn -> 1h 45)
    if( strpos( $result, 'min' ) !== False and strpos( $result, 'h' ) !== False )
        $result = str_replace( 'min', '', $result );

	return $result;
}

// enqueue all scripts/styles
function mpprecipe_enqueue_styles() {

    // Always add the print script
    wp_enqueue_script('mpprecipe-print', MPPRECIPE_PLUGIN_DIRECTORY . 'mpprecipe_print.js' );
    // main plugin js
    wp_enqueue_script('mpprecipe', MPPRECIPE_PLUGIN_DIRECTORY . 'mpprecipe.js' );

    // adding google font
    wp_enqueue_style('lato', MPPRECIPE_PLUGIN_DIRECTORY . 'Lato.css' );
    // Add common stylesheet
    wp_enqueue_style('mpprecipe-common', MPPRECIPE_PLUGIN_DIRECTORY .  'mpprecipe-common.css?v=20180607' );

	// Recipe styling
	$css = get_option('mpprecipe_stylesheet');
	if (strcmp($css, '') != 0)
        wp_enqueue_style('mpprecipe-custom', MPPRECIPE_PLUGIN_DIRECTORY .  $css . '.css' );

	//QUINN'S NUTRITION 'MINIMAL' DESIGN
	if (get_option('mpprecipe_nutrition_style') !== 'nutrition_panel')
        wp_enqueue_style('mpprecipe-minimal-nutrition', MPPRECIPE_PLUGIN_DIRECTORY .  'mpprecipe-minimal-nutrition.css' );
}
add_action('wp_enqueue_scripts', 'mpprecipe_enqueue_styles');

// function to include the javascript for the Add Recipe button
function mpprecipe_process_head() {
    // Add swoop js
    $header_html = mpp_swoopcode();

	// QUINN'S PLUGIN CSS
	$primary_color   = '#' . get_option( "mpprecipe_primary_color" );
	$secondary_color = '#' . get_option( "mpprecipe_secondary_color" );
	$text_color      = '#' . get_option( "mpprecipe_text_color" );
    $link_color      = '#' . get_option( "mpprecipe_link_color" );
    $link_hover_color      = '#' . get_option( "mpprecipe_link_hover_color" );
	$font            = get_option( "mpprecipe_font" );
	$font_size       = get_option( "mpprecipe_font_size" );

    $header_html .= " <style> ";
    if( $primary_color )
    {
        $header_html .= "
            .myrecipe-button, .mylist-button, .mycal-button, .save-button, .nut-circle {
                background-color:" . $primary_color . " !important;
            }
            .butn-link {
                border-color:" . $primary_color . " !important;
            }
            .butn-link {
                color:" . $primary_color . " !important;
            }
        ";
    }
    if( $secondary_color )
    {
        $header_html .= "
            .myrecipe-button:hover, .mylist-button:hover, .mycal-button:hover, .save-button:hover {
                background-color:" . $secondary_color . " !important;
            }
            .butn-link:hover {
                color:" . $secondary_color . " !important;
                border-color:" . $secondary_color . " !important;
            }
        ";
    }
    if( $text_color )
    {
        $header_html .= "
            .mpprecipe .h-4, #mpprecipe-title {
                color: " . $text_color . " !important;
            }
        ";
    }
    if( $link_color )
    {
        $header_html .= "
            .mpprecipe a {
                color: " . $link_color . " !important;
            }
        ";
    }
    if( $link_hover_color )
    {
        $header_html .= "
            .mpprecipe a:hover {
                color: " . $link_hover_color . " !important;
            }
        ";
    }
    if( strcmp(get_option('mpprecipe_link_underline'), 'Underline') == 0 )
    {
        $header_html .= "
            .mpprecipe a, .mpprecipe a:hover {
                text-decoration: underline !important;
            }
        ";
    }
    if( $font )
    {
        $header_html .= "
            .mpprecipe, #mpprecipe-container .sans-serif, #mpprecipe-serving-size {
                font-family: '" . $font . "', sans-serif !important;
            }
        ";
    }
    if( $font_size )
    {
        $header_html .= "
            .mpprecipe, #mpprecipe-container .sans-serif, #mpprecipe-serving-size {
                font-size: $font_size !important;
            }
        ";
    }
    $header_html .= "</style> ";

    echo $header_html;
}

add_filter('wp_head', 'mpprecipe_process_head');

// Replaces the [a|b] pattern with text a that links to b
// Replaces _words_ with an italic span and *words* with a bold span
function mpprecipe_richify_item($item, $class = '' ) {

    $output = $item;
    #$link_ptr = '#\[([^\]\|\[]*?)\|([^\]\|\[ ]*?)( (.*?))?\]#';
    $link_ptr = "#\[(.*?)\| *(.*?)( (.*?))?\]#";
    preg_match_all(
        #[NAME|LINK extra-attrs]
        $link_ptr,
        $item,
        $matches
    );

    if( isset($matches[0]) )
    {
        # Conversion of ER recipes is flawed. Quotes are disordered. Quotes not an absolute requirement by HTML. Will drop for now.
        #[NAME|LINK extra="attrs" ab"=normal"] => [NAME|LINK extra=attrs ab=normal]
        #str_replace('"','',$item)
        $orig = $matches[0];
        $substitution = preg_replace(
             $link_ptr,
             '<a href="\\2" class="' . $class . '-link" target="_blank" \\3 > \\1 </a>',
             str_replace( '"', '', $orig )
         );
        $output = str_replace( $orig, $substitution, $item);
    }

	$output = preg_replace('/(^|\s)\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*(\W|$)/', '\\1<span class="bold">\\2</span>\\3', $output);
	$output = preg_replace('#\[br\]#', '<br/>', $output);
	$output = preg_replace('#\[b\](.*?)\[\/b\]#s', '<strong>\1</strong>', $output);
	$output = preg_replace('#\[i\](.*?)\[\/i\]#s', '<em>\1</em>', $output);
	$output = preg_replace('#\[u\](.*?)\[\/u\]#s', '<u>\1</u>', $output);
	return preg_replace('/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1<span class="italic">\\2</span>\\3', $output);
}

function mpprecipe_break( $otag, $text, $ctag) {
	$output = "";
	$split_string = explode( "\r\n\r\n", $text, 10 );
	foreach ( $split_string as $str )
	{
		$output .= $otag . $str . $ctag;
	}
	return $output;
}

// Processes markup for attributes like labels, images and links
// !Label
// %image
function mpprecipe_format_item($item, $elem, $class, $itemprop, $id, $i) {

	if (preg_match("/^%(\S*)/", $item, $matches)) {	// IMAGE Updated to only pull non-whitespace after some blogs were adding additional returns to the output
		$output = '<img class = "' . $class . '-image" src="' . $matches[1] . '" />';
		return $output; // Images don't also have labels or links so return the line immediately.
	}

	if (preg_match("/^!(.*)/", $item, $matches)) {	// LABEL
		$class .= '-label';
		$elem = 'div';
		$item = $matches[1];
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" >';	// No itemprop for labels
	} else {
	    if (empty($elem)) $elem = 'div';
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" itemprop="' . $itemprop . '">';
	}

	$output .= mpprecipe_richify_item($item, $class);
	$output .= '</' . $elem . '>';

	return $output;
}


function mpprecipe_print_author( $recipe )
{
    return "<span itemprop='author'>
                <h4 itemprop='url' target='_blank' class='mpp-recipe-author'>
                <span itemprop='name'>
                    $recipe->author
                </span>
                </h4>
                </span>";
}

function mpprecipe_create_link()
{
    return <<<HTML
<p><a href="#" onclick="$('#creation_options').toggle()">Toggle Link Builder</a></p>
<div id='creation_options' style='display:none;'>
    <div style='margin: 16px'>
    <h4>Link Builder</h4>
    Build the link and copy and paste it where needed
    </div>
    <form id="link_creation_form">
        <p>
        <label>Text</label>
        <input id="link_name" type="text" placeholder="Link test, e.g. Heinz Keitchup" />
        </p>
        <p>
        <label>URL</label>
        <input id="link_address"  type="text" placeholder="Don't forget the 'http://',  eg. http://google.com" />
        </p>
        <p>
        <label>No Follow</label>
        <input id="link_nofollow" type="checkbox" />
        </p>
    </form>
    <p>
    <label>Link Code</label>
    <input id="link_output" type="text" />
    </p>
    <script>
        function link_create()
        {
            var n = $("#link_name").val()
            var a = $("#link_address").val()
            var f = $("#link_nofollow").is(":checked")

            if( !(a && n ))
                return

            var fs = " "
            if( f )
                fs += "rel='nofollow'"

            return "[" + n + "|" + a + fs + "]"

        }
        $("#link_name,#link_address,#link_nofollow").change(
            function()
            {
                $("#link_output").val( link_create() )
            }
        )
    </script>
</div>
HTML;
}

function mpprecipe_create_nutrition_and_tag($get_info)
{
    $nutrition_and_tag = <<<HTML
        <style>
            .add_tags {
                margin-top    : 3px;
                margin-bottom : 20px;
                width         : 100%;
            }
        
            .add_tags span {
                width   : 60px;
                display : inline-block;
            }
            
            .add_tags select {
                width: 200px !important;
                height: 25px !important;
            }
            
            .add_tags input {
                width: 200px !important;
                height: 16px !important;
                float: none !important;
            }
        
            .add_tags select, .add_tags button {
                display : inline-block;
            }
        
            .add_tags li {
                margin-bottom : 10px;
            }
        
        
            #tagged-info .tags a {
                background: #a1a1a1;
                color: #fff  !important;;
                height: 1.5rem  !important;;
                margin: 0 6px 0.375rem 0  !important;;
                padding: 0 0.625rem  !important;;
                text-transform: uppercase  !important;;
                display: inline-block  !important;;
            }
            #tagged-info .tags .tag {
                display: inline-block !important;
            }
            #tagged-info .tags .tag dt {
                line-height: 24px;
                font-size: 11px;
            }
            #tagged-info .tags .tag a {
                margin-bottom: 10px !important;
            }
            #tagged-info .tags .highlighted {
                background: #01a64f !important;
            }
            #tagged-info .tags .admin-deletion {
                margin-left: -10px !important;
                background: red !important;
                line-height: 24px;
            }
        </style>
HTML;

    if (isset($get_info["post_id"]) and strpos($get_info["post_id"], '-') !== false) {
        $nutrition_btn = <<<HTML
            <input type="button" value="Nutrition Analyzer" id="nutrition-analyzer-button">
HTML;

        $nutrition_content = <<<HTML
             <div id="nutrition-analyzer-iframe" style="margin: 16px; display: none;">
                <div id="nutrition-analyzer-message" style="text-align: center">
                    <div style="margin: auto; " class="loadersmall"></div>
                    <strong>Loading...</strong>
                </div>
                <div id="nutrition-analyzer-load" style="height: 60vh; width: 100%; padding: 0px; margin: 0px;"></div>
            </div>
HTML;

        // Load tagged data
        $tagged_data_html = "";
        $tagged_keywords  = "";
        if ($get_info["post_id"] && !isset($get_info["add-recipe-button"]) && strpos($get_info["post_id"], '-') !== false) {
            $recipe_id       = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
            $recipe          = mpprecipe_select_recipe_db($recipe_id);
            $tagged_keywords = esc_textarea($recipe->keywords);
            $tagged_data     = unserialize($recipe->tagged);
            if (isset($tagged_data['id']) && is_array($tagged_data['id'])){
                $types           = array_keys($tagged_data['id']);
                foreach ($types as $type) {
                    $bg_color = "";
                    if ($type == 'diet') $bg_color = "style='background: #0366d6; !important'";
                    if ($type == 'allergy') $bg_color = "style='background: #01a650; !important'";
                    $tagged_data_id = @array_filter($tagged_data['id'][$type]);
                    $type_name = ucfirst($type);
                    $tagged_data_html .= "<div id='tag_$type'><div class='tag' style='width: 75px'>$type_name :</div>";
                    $tagged_data_html .= "<div class='list_tag' style='width: 80%; display: -webkit-inline-box'>";
                    foreach ($tagged_data_id as $id) {
                        $tagged_data_text = $tagged_data['text'][$type][$id];
                        $tagged_data_html .= <<<HTML
                    <div class="tag">
                        <a $bg_color><dt>$tagged_data_text</dt></a>                        
                        <a href="#" id="$type$id" onclick="javascript: jQuery(this).parent().remove(); return false;" class="admin-deletion deleteCourse-$id">x</a>
                        <input type="hidden" name="tagged[id][$type][]" class="deleteCourse-$id" value="$id">
                        <input type="hidden" name="tagged[text][$type][$id]" class="deleteCourse-$id" value="$tagged_data_text">
                    </div>
HTML;
                    }
                    $tagged_data_html .= "</div>";
                    $tagged_data_html .= "</div>";
                }
            }
        }
    } else {
        $nutrition_btn     = "";
        $nutrition_content = "";
        $tagged_data_html  = "";
        $tagged_keywords   = "";
    }

    $nutrition_and_tag .= <<<HTML
        $nutrition_btn
        <input type="button" value="Tag Manager." id="tag-manager-button">
        $nutrition_content
        <div id="tag-manager-content" style="margin: 16px; display: none;">
            <div id="tag-manager-message" style="text-align: center">
                <div style="margin: auto; " class="loadersmall"></div>
                <strong>Loading...</strong>
            </div>
             <div id="tagged-info">
                <input type="hidden" name="tagged[id][cuisines][]" value="" />
                <input type="hidden" name="tagged[id][occasions][]" value=""  />
                <input type="hidden" name="tagged[id][courses][]" value=""  />
                <input type="hidden" name="tagged[id][cooking][]" value=""  />
                <input type="hidden" name="tagged[id][diet][]" value=""  />
                <input type="hidden" name="tagged[id][allergy][]" value=""  />
                
                <dl class="tags">
                    $tagged_data_html
                </dl>
            </div>
            <div id="tag-manager-load"></div>
            <div id="mpprecipe-keywords" class="cls" style="clear: both; display: block;">
            <label>Keywords: 
            <small>Type keywords and separate by a comma. Eg. - easy chicken recipe, low calorie, etc.</small>
            <small><a href="https://developers.google.com/search/docs/data-types/recipe" target="_blank">Google Keyword Guidelines</a></small>
            </label>
            <textarea  style="float: left; resize: vertical;" id="keywords_textbox" name="keywords">$tagged_keywords</textarea>
            </div>
        </div>
HTML;

    return $nutrition_and_tag;
}

function mpprecipe_print_er_nutrition( $recipe )
{
    $output = '';

	$show_nutrition = get_option('mpprecipe_nutrition');

	// Nutrition Box'
    if( $recipe->server_recipe_id ||
        !$show_nutrition ||
        !( $recipe->calories || $recipe->carbs|| $recipe->fat || $recipe->satfat || $recipe->unsatfat || $recipe->transfat
        || $recipe->sodium || $recipe->cholesterol || $recipe->protein || $recipe->fiber)
    )
        return $output;
    $output .= '<p id="mpprecipe-nutrition" class="h-4 strong"> Nutrition</p>';

    if( $recipe->calories )
        $output .=" <div class=''>Calories: <span class=''> $recipe->calories cal </span> </div> ";
    if( $recipe->carbs )
        $output .=" <div class=''> Carbohydrates: <span class=''> $recipe->carbs g </span> </div> ";
    if( $recipe->fat )
        $output .=" <div class=''> Fat: <span class=''> $recipe->fat g </span> </div> ";
    if( $recipe->satfat )
        $output .=" <div class=''> Saturated Fat: <span class=''> $recipe->satfat g </span> </div> ";
    if( $recipe->unsatfat )
        $output .=" <div class=''> Unsaturated Fat: <span class=''> $recipe->unsatfat g </span> </div> ";
    if( $recipe->transfat )
        $output .=" <div class=''> Trans Fat: <span class=''> $recipe->transfat g </span> </div> ";
    if( $recipe->sodium )
        $output .=" <div class=''> Sodium: <span class=''> $recipe->sodium g </span> </div> ";
    if( $recipe->cholesterol )
        $output .=" <div class=''> Cholesterol: <span class=''> $recipe->cholesterol g </span> </div> ";
    if( $recipe->protein )
        $output .=" <div class=''> Protein: <span class=''> $recipe->protein g </span> </div> ";
    if( $recipe->fiber )
        $output .=" <div class=''> Fiber: <span class=''> $recipe->fiber g </span> </div> ";

    return $output;
}

function mpprecipe_print_nutrition( $recipe )
{
	$plugindir = MPPRECIPE_PLUGIN_DIRECTORY;

	$show_nutrition = get_option('mpprecipe_nutrition');
    $output = '';

	// Nutrition Box'
    if( $show_nutrition && $recipe->server_recipe_id &&
        ($recipe->carbs || $recipe->calories || $recipe->fat || $recipe->protein ) ) {

            $output .= '
                <div id="mpprecipe-panel" class="h-4 strong expand_heading">
                    <input type="checkbox" checked="">
                        <div class="expand-title">
                            <h4>Nutrition</h4>
                            <div class="expand-button tags-default-show"></div>
                        </div>
                    </input>
                    <div class="toggle-container toggle-container-nutrition">';
                        $output .= "
                        <div class='nutbox'>
                            <div class='nut-toprow'>
                            <div class='nutrition-minimal-display'>
                        ";

                        if( $recipe->carbs ) {
                            $output .="
                                
                                    <div class='nut-calories nut-topouter'>
                                        <h4>Calories</h4>
                                        <div class='nut-circle'>
                                            $recipe->calories cal
                                        </div>
                                    </div>
                                    ";
                        }

                        if( $recipe->fat ) {
                            $output .="
                                    <div class='nut-fat nut-topouter'>
                                        <h4>Fat</h4>
                                        <div class='nut-circle'>
                                            $recipe->fat g
                                        </div>
                                    </div>
                                    ";
                        }

                        if( $recipe->carbs ) {
                            $output .="
                                    <div class='nut-carbs nut-topouter'>
                                        <h4>Carbs</h4>
                                        <div class='nut-circle'>
                                            $recipe->carbs g
                                        </div>
                                    </div>
                                    ";
                        }

                        if( $recipe->protein ) {
                            $output .="
                                    <div class='nut-protein nut-topouter'>
                                        <h4>Protein</h4>
                                        <div class='nut-circle'>
                                            $recipe->protein g
                                        </div>
                                    </div>
                                ";
                        }

                        $output .="
                            </div></div>
                
                            <a class='nut-message' target='_blank' href='" . MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/recipe/nutrition/$recipe->server_recipe_id'>Click Here For Full Nutrition, Exchanges, and My Plate Info</a>
                            <div class='esha-logo'>
                                <a href='http://www.esha.com' target='_blank'>
                                <img alt='ESHA Logo' src='$plugindir/eshalogo.jpg' />
                                </a>
                            </div>
                        ";

                        if( $recipe->nutrition_tags )
                        {
                            $output .="
                                <div class='nut-tags'>
                                ";
                            foreach( explode( ',', $recipe->nutrition_tags) as $tag )
                                    $output = "<a class='nut-tag' href='#'>$tag</a>";
                            $output .=" </div> ";
                        }
                            $output .="</div>";
                $output .=  '</div></div>';
                    }

    return $output;
}

function mpprecipe_print_nutrition_box( $recipe )
{
    $plugindir = MPPRECIPE_PLUGIN_DIRECTORY;

    $show_nutrition = get_option('mpprecipe_nutrition');
    $output = '';
    // Nutrition Box'
    if( $show_nutrition && $recipe->server_recipe_id && ($recipe->carbs || $recipe->calories || $recipe->fat || $recipe->protein ) )
    {
        $nutrition_info = "";
        $nutrition_info .= '<li style="margin-bottom:10px;margin-top:0px;" class="calories">
                    <span itemprop="calories" class="big-calories">Calories</span>
                    <span class="percent small-calories">' . round($recipe->calories,2) . '</span>
                </li>';
        $nutrition_info .= '<div style="float:right;font-size:14px;">% Daily Value*</div>
                <br style="clear:both;" />';
        $nutrition_info .= '<li>Total Fat:
                <span itemprop="fat" class="fat">' . $recipe->fat . ' g</span>
                <span class="percent">' . round($recipe->fat / 65 * 100, 2) . '%</span>
                </li>';
		//BDJ
        $nutrition_info .= '<li class="indent">Saturated Fat:
                <span itemprop="saturatedFat" class="saturatedFat">' . $recipe->satfat . ' g</span>
                <span class="percent">' . round($recipe->satfat / 20 * 100, 2) . '%</span>
                </li>';

        $nutrition_info .= '<li>Cholesterol:
                <span itemprop="cholesterol" class="cholesterol">' . $recipe->cholesterol . ' mg</span>
                <span class="percent">' . round($recipe->cholesterol / 300 * 100, 2) . '%</span>
                </li>';
        $nutrition_info .= '<li>Sodium:
                <span itemprop="sodium">' . $recipe->sodium . ' mg</span>
                <span class="percent">' . round($recipe->sodium / 2400 * 100, 2) . '%</span>
                </li>';
        $nutrition_info .= '<li>Potassium:
                <span itemprop="potassium">' . $recipe->potassium . ' mg</span>
                <span class="percent">' . round($recipe->potassium / 3500 * 100, 2) . '%</span>
                </li>';
        $nutrition_info .= '<li>Total Carbohydrate:
                <span itemprop="carbohydrate" class="carbohydrates">' . $recipe->carbs . ' g</span>
                <span class="percent">%</span>
                </li>';
        $nutrition_info .= '<li class="indent">Sugar:
                <span itemprop="sugar" class="sugar">' . $recipe->sugar . ' g</span>
                </li>';
        $nutrition_info .= '<li>Protein:
                <span itemprop="protein" class="protein">' . $recipe->protein . ' g</span>
                </li>';
        $nutrition_info .= '<li>Vitamin A:
                <span class="percent">' . round($recipe->vitamin_a / 5000 * 100, 2) . '%</span>
                </li>';
        $nutrition_info .= '<li>Calcium:
                <span itemprop="calcium">' . $recipe->calcium . ' mg</span>
                <span class="percent">' . round($recipe->calcium / 1000 * 100, 2) . '%</span>
                </li>';
        $nutrition_info .= '<li>Iron:
                <span itemprop="iron">' . $recipe->iron . ' mg</span>
                <span class="percent">' . round($recipe->iron / 18 * 100, 2) . '%</span>
                </li>';

        $server_recipe_id = MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . '/recipe/nutrition/' . $recipe->server_recipe_id;

        $output .= <<<HTML
        <div id="nut_info_out" class="nutrition-calc" style="margin: 0 auto;">
            <div id="nut_info">
                <div class="title">Nutrition Facts</div>
                <ul>
                <div style="font-weight: 800;font-size: 12px;">Amount Per Serving</div>                
                $nutrition_info
                <div style="padding: 10px 20px;text-align: center; font-size:12px;">
                    * Percent Daily Values are based on a 2000 calorie diet.
                    <br>
                    <br>
                    <div class='esha-logo'>
                    <a href='http://www.esha.com' target='_blank'>
                        <img alt='ESHA Logo' src='$plugindir/eshalogo.jpg' />
                        </a>
                    </div>
                    <br/>
                    <a style="font-size:13px !important;" class="nut-message" target="_blank" href="$server_recipe_id">Click Here For Full Nutrition, Exchanges, and MyPlate Info</a>
                </div>
            </div>
        </div>
HTML;


    }
    return $output;
}

/**
 * Find featured image of post
 */
function mpprecipe_get_featured_image($recipe)
{
    global $wpdb;

    $p  = $wpdb->prefix  . 'posts';
    $pm = $wpdb->prefix . 'postmeta';

    return $wpdb->get_var("
        SELECT guid
        FROM   $p p
        JOIN   $pm pm
        ON     p.id=pm.meta_value
        WHERE  pm.meta_key = '_thumbnail_id'
        AND    pm.post_id = '$recipe->post_id'
    ");
}

// Formats the recipe for output
function mpprecipe_format_recipe($recipe) {
    $output = "";
    $permalink = get_permalink();
    //$recipe->serving_size = 10;
	$plugindir = MPPRECIPE_PLUGIN_DIRECTORY;

    $nutrition_style = get_option('mpprecipe_nutrition_style');

    if( !$recipe->recipe_image )
    $recipe->recipe_image = mpprecipe_get_featured_image( $recipe );

    if( $nutrition_style === 'above' )
        $output .= '<div class="outer-nutrition outer-nutrition-above">' . mpprecipe_print_nutrition( $recipe ) . '</div>';


	if("mpprecipe-design2" == get_option('mpprecipe_stylesheet') or "mpprecipe-design22" == get_option('mpprecipe_stylesheet') or "mpprecipe-design24" == get_option('mpprecipe_stylesheet') or "mpprecipe-design3" == get_option('mpprecipe_stylesheet') or "mpprecipe-design23" == get_option('mpprecipe_stylesheet') or "mpprecipe-design7" == get_option('mpprecipe_stylesheet') or "mpprecipe-design11" == get_option('mpprecipe_stylesheet') or "mpprecipe-design8" == get_option('mpprecipe_stylesheet') or "mpprecipe-design19" == get_option('mpprecipe_stylesheet')  or "mpprecipe-design21" == get_option('mpprecipe_stylesheet') or "mpprecipe-design19" == get_option('mpprecipe_stylesheet') or "mpprecipe-design4" == get_option('mpprecipe_stylesheet') or "mpprecipe-design5" == get_option('mpprecipe_stylesheet')) {

			// Output main recipe div with border style
			$style_tag = '';
			$border_style = get_option('mpprecipe_outer_border_style');
			if ($border_style != null)
				$style_tag = 'style="border: ' . $border_style . ';"';
			$output .= '
			<div id="mpprecipe-container-' . $recipe->recipe_id . '" class="mpprecipe-container-border" ' . $style_tag . '>
			<div id="mpprecipe-container" class="serif mpprecipe">
			  <div id="mpprecipe-innerdiv" class="firstkind">
                <div class="item mpp-top">';


			$image_hide = strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0;

			//!! Adjust to full width if no image

			if (!$recipe->recipe_image or $image_hide ) {

                if("mpprecipe-design3" != get_option('mpprecipe_stylesheet') and "mpprecipe-design23" != get_option('mpprecipe_stylesheet') and "mpprecipe-design5" != get_option('mpprecipe_stylesheet') and "mpprecipe-design24" != get_option('mpprecipe_stylesheet')) {
    				$output .= "<style>
    					#mpprecipe-container-$recipe->recipe_id .mpp-topleft {
    						width: 100% !important;
    					}
    					#mpprecipe-container-$recipe->recipe_id .mpp-topright {
    						display:none !important;
    					}
    				</style>
    				";
                }


			}

			// Open mpp-topright panel if image
			if ($recipe->recipe_image != null || $recipe->summary != null)
				$output .= '<div class="mpp-topleft">';

            if ("mpprecipe-design23" != get_option('mpprecipe_stylesheet')) {
    			if ( $recipe->author ) {
    				/* AUTHOR LINK */
    				$output .= mpprecipe_print_author( $recipe );
    				/* END AUTHOR LINK */
    			}

    			 //!! yield and nutrition
    			if ($recipe->yield != null) {
    				$output .= '<p id="mpprecipe-yield">';
    				if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
    					$output .= get_option('mpprecipe_yield_label') . ' ';
    				}
    				$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
    			}
            }

			if ($recipe->serving_size != null && $recipe->serving_size != 1)
			{
				$output .= '<div id="mpprecipe-nutrition">';
					$output .= '<p id="mpprecipe-serving-size">';
					if (strcmp(get_option('mpprecipe_serving_size_label_hide'), 'Hide') != 0) {
						$output .= get_option('mpprecipe_serving_size_label') . ' ';
					}
					$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
					$output .= '<span style="display: none;" itemprop="calories">' . $recipe->calories . '</span></p>';
				$output .= '</div>';
			}

			// add the title and close the item class
			$output .= '<div id="mpprecipe-title" itemprop="name" class="h-1" >' . $recipe->recipe_title . '</div>';




			if ($recipe->summary != null) {
					$output .= '<div id="mpprecipe-summary" itemprop="description">';
					$output .= mpprecipe_break( '<p class="summary">', mpprecipe_richify_item($recipe->summary, 'summary'), '</p>' );
					$output .= '</div>';
			}


			// open the zlmeta and fl-l container divs
			$output .= '<div class="fl-l">';

			// recipe timing
			if ($recipe->prep_time != null) {
				$prep_time = mpprecipe_format_duration($recipe->prep_time);

				$output .= '<p id="mpprecipe-prep-time">';
				$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span>';

				if (strcmp(get_option('mpprecipe_prep_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_prep_time_label') . ' ';
				}
				$output .= '</p>';
			}
			if ($recipe->cook_time != null) {
				$cook_time = mpprecipe_format_duration($recipe->cook_time);

				$output .= '<p id="mpprecipe-cook-time">';
				$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span>';

				if (strcmp(get_option('mpprecipe_cook_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_cook_time_label') . ' ';
				}
				$output .= '</p>';
			}


			$total_time         = null;
			$total_time_content = null;

			if ($recipe->total_time != null)
			{
				$total_time         = mpprecipe_format_duration($recipe->total_time);
				$total_time_content = $recipe->total_time;
			}
			elseif( ($recipe->prep_time || $recipe->cook_time ) and class_exists( 'DateInterval' ) and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
			{
				$t1 = new DateTime();
				$t2 = new DateTime();

				if( $recipe->prep_time ) $t1->add( new DateInterval($recipe->prep_time));
				if( $recipe->cook_time ) $t1->add( new DateInterval($recipe->cook_time));

				$ti = $t2->diff($t1);
				$total_time_content = $ti->format('P%yY%mM%dDT%hH%iM%sS');
			}

			if( $total_time_content )
			{
				$total_time = mpprecipe_format_duration($total_time_content);
				$output .= '<p id="mpprecipe-total-time">';
				$output .= '<span itemprop="totalTime" content="' . $total_time_content . '">' . $total_time . '</span>';

				if (strcmp(get_option('mpprecipe_total_time_label_hide'), 'Hide') != 0)
					$output .= get_option('mpprecipe_total_time_label') . ' ';

				$output .= '</p>';
			}

            if ("mpprecipe-design23" == get_option('mpprecipe_stylesheet')) {

                 //!! yield and nutrition
                if ($recipe->yield != null) {
                    $output .= '<p id="mpprecipe-yield">';
                    if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
                        $output .= get_option('mpprecipe_yield_label') . ' ';
                    }
                    $output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
                }

                if ( $recipe->author ) {
                    /* AUTHOR LINK */
                    $output .= '<p class="mpp-author">Author: ' . mpprecipe_print_author( $recipe ) . '</p>';
                    /* END AUTHOR LINK */
                }
            }

			if("mpprecipe-design2" == get_option('mpprecipe_stylesheet') or "mpprecipe-design22" == get_option('mpprecipe_stylesheet') or "mpprecipe-design4" == get_option('mpprecipe_stylesheet') or "mpprecipe-design7" == get_option('mpprecipe_stylesheet') or "mpprecipe-design11" == get_option('mpprecipe_stylesheet') or "mpprecipe-design8" == get_option('mpprecipe_stylesheet') or "mpprecipe-design19" == get_option('mpprecipe_stylesheet') or "mpprecipe-design21" == get_option('mpprecipe_stylesheet')) {
				// Add Print and Save Button
				$output .= mpp_buttons( $recipe->recipe_id );
                $output .= mpprecipe_ratings_html( $recipe );

				// add the MealPlannerPro recipe button
				if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
					$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

				}
			}



			//!! close mpp-topright if there is an image
			if ($recipe->recipe_image != null || $recipe->summary != null) {
				$output .= '</div>';
			}
					$output .= '<div class="zlclear"></div></div>';


			//!! create image container
			$output .= '<div class="mpp-topright">';
            $class = null;

            /* No Crop and No Buttons */
            if("mpprecipe-design2" == get_option('mpprecipe_stylesheet') or "mpprecipe-design4" == get_option('mpprecipe_stylesheet') or "mpprecipe-design7" == get_option('mpprecipe_stylesheet') or "mpprecipe-design8" == get_option('mpprecipe_stylesheet') or "mpprecipe-design11" == get_option('mpprecipe_stylesheet') or "mpprecipe-design19" == get_option('mpprecipe_stylesheet') or "mpprecipe-design21" == get_option('mpprecipe_stylesheet') or "mpprecipe-design22" == get_option('mpprecipe_stylesheet')) {
                if ($recipe->recipe_image != null )
                {

                    if (strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0)
                        $class .= ' hide-card';

                    if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
                        $class .= ' hide-print';

                    $output .= "<img alt='Recipe Image' class='$class' src='" . $recipe->recipe_image . "' />";
                }
            }


            /* No Crop and Yes Buttons */
            if("mpprecipe-design3" == get_option('mpprecipe_stylesheet') or "mpprecipe-design5" == get_option('mpprecipe_stylesheet') or "mpprecipe-design23" == get_option('mpprecipe_stylesheet') or "mpprecipe-design24" == get_option('mpprecipe_stylesheet')) {
                if ($recipe->recipe_image != null )
                {

                    if (strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0)
                        $class .= ' hide-card';

                    if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
                        $class .= ' hide-print';

                    $output .= "<img alt='Recipe Image' class='$class' src='" . $recipe->recipe_image . "' />";
                }

                // Add Print and Save Button
                $output .= mpp_buttons( $recipe->recipe_id );
                $output .= mpprecipe_ratings_html( $recipe );

                // add the MealPlannerPro recipe button
                if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
                    $output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

                }
            }

			// close topright
			$output .= '</div>';

			//!! close the containers
			$output .= '<div class="zlclear"></div></div>';





	} elseif("mpprecipe-design6" == get_option('mpprecipe_stylesheet') or "mpprecipe-design9" == get_option('mpprecipe_stylesheet') or "mpprecipe-design10" == get_option('mpprecipe_stylesheet')) {

			// Output main recipe div with border style
			$style_tag = '';
			$border_style = get_option('mpprecipe_outer_border_style');
			if ($border_style != null)
				$style_tag = 'style="border: ' . $border_style . ';"';
			$output .= '
			<div id="mpprecipe-container-' . $recipe->recipe_id . '" class="mpprecipe-container-border" ' . $style_tag . '>
			<div id="mpprecipe-container" class="serif mpprecipe">
			  <div id="mpprecipe-innerdiv" class="secondkind">
				<div class="item mpp-top">';

			$image_hide = strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0;

			//!! Adjust to full width if no image

			if (!$recipe->recipe_image or $image_hide ) {


				$output .= "<style>
					#mpprecipe-container-$recipe->recipe_id .mpp-topleft {
						width: 100% !important;
					}
					#mpprecipe-container-$recipe->recipe_id .mpp-topright {
						display:none !important;
					}
				</style>
				";


			}

			// Open mpp-topright panel if image
			if ($recipe->recipe_image != null || $recipe->summary != null)
				$output .= '<div class="mpp-topleft">';



			if ($recipe->serving_size != null && $recipe->serving_size != 1)
			{
				$output .= '<div id="mpprecipe-nutrition">';
					$output .= '<p id="mpprecipe-serving-size">';
					if (strcmp(get_option('mpprecipe_serving_size_label_hide'), 'Hide') != 0) {
						$output .= get_option('mpprecipe_serving_size_label') . ' ';
					}
					$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
				$output .= '</div>';
			}

			// add the title and close the item class
			$output .= '<div id="mpprecipe-title" itemprop="name" class="h-1" >' . $recipe->recipe_title . '</div>';

			$output .= "<hr class='specialhr' />";

 			if ( $recipe->author ) {
				/* AUTHOR LINK */
				$output .= mpprecipe_print_author( $recipe );
				/* END AUTHOR LINK */
			}

 			//!! yield and nutrition
			if ($recipe->yield != null) {
				$output .= '<p id="mpprecipe-yield">';
				if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_yield_label') . ' ';
				}
				$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
			}



			if ($recipe->summary != null) {
					$output .= '<div id="mpprecipe-summary" itemprop="description">';
					$output .= mpprecipe_break( '<p class="summary">', mpprecipe_richify_item($recipe->summary, 'summary'), '</p>' );
					$output .= '</div>';
			}


			// open the zlmeta and fl-l container divs
			$output .= '<div class="fl-l">';

			// recipe timing
			if ($recipe->prep_time != null) {
				$prep_time = mpprecipe_format_duration($recipe->prep_time);

				$output .= '<p id="mpprecipe-prep-time">';
				$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span>';

				if (strcmp(get_option('mpprecipe_prep_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_prep_time_label') . ' ';
				}
				$output .= '</p>';
			}
			if ($recipe->cook_time != null) {
				$cook_time = mpprecipe_format_duration($recipe->cook_time);

				$output .= '<p id="mpprecipe-cook-time">';
				$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span>';

				if (strcmp(get_option('mpprecipe_cook_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_cook_time_label') . ' ';
				}
				$output .= '</p>';
			}


			$total_time         = null;
			$total_time_content = null;

			if ($recipe->total_time != null)
			{
				$total_time         = mpprecipe_format_duration($recipe->total_time);
				$total_time_content = $recipe->total_time;
			}
			elseif( ($recipe->prep_time || $recipe->cook_time ) and class_exists( 'DateInterval' ) and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
			{
				$t1 = new DateTime();
				$t2 = new DateTime();

				if( $recipe->prep_time ) $t1->add( new DateInterval($recipe->prep_time));
				if( $recipe->cook_time ) $t1->add( new DateInterval($recipe->cook_time));

				$ti = $t2->diff($t1);
				$total_time_content = $ti->format('P%yY%mM%dDT%hH%iM%sS');
			}

			if( $total_time_content )
			{
				$total_time = mpprecipe_format_duration($total_time_content);
				$output .= '<p id="mpprecipe-total-time">';
				$output .= '<span itemprop="totalTime" content="' . $total_time_content . '">' . $total_time . '</span>';

				if (strcmp(get_option('mpprecipe_total_time_label_hide'), 'Hide') != 0)
					$output .= get_option('mpprecipe_total_time_label') . ' ';

				$output .= '</p>';
			}




			//!! close mpp-topright if there is an image
			if ($recipe->recipe_image != null || $recipe->summary != null) {
				$output .= '</div>';
			}
					$output .= '<div class="zlclear"></div></div>';


			//!! create image container
			$output .= '<div class="mpp-topright">';



			if ($recipe->recipe_image != null )
			{
				$class  = 'mpp-toprightimage';
				$style  = "background:url($recipe->recipe_image);background-size:cover; border-radius: 100%;";

				if (strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0)
					$class .= ' hide-card';

				if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
					$class .= ' hide-print';

				$output .= "<div class='$class' style='$style' ></div>";
			}


			// close topright
			$output .= '</div>';

			//!! close the containers
			$output .= '<div class="zlclear"></div></div>';


			$output .= "<div class='bottombar'>";

				// Add Print and Save Button
				$output .= mpp_buttons( $recipe->recipe_id );
                $output .= mpprecipe_ratings_html( $recipe );

				// add the MealPlannerPro recipe button
				if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
					$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

				}


			$output .= "</div>";

	}

	elseif ("mpprecipe-std" == get_option('mpprecipe_stylesheet') or "mpprecipe-design13" == get_option('mpprecipe_stylesheet') or "mpprecipe-design14" == get_option('mpprecipe_stylesheet') or "mpprecipe-design15" == get_option('mpprecipe_stylesheet') or "mpprecipe-design16" == get_option('mpprecipe_stylesheet') or "mpprecipe-design17" == get_option('mpprecipe_stylesheet') or "mpprecipe-design18" == get_option('mpprecipe_stylesheet') or "mpprecipe-design20" == get_option('mpprecipe_stylesheet')) {

			// Output main recipe div with border style
			$style_tag = '';
			$border_style = get_option('mpprecipe_outer_border_style');
			if ($border_style != null)
				$style_tag = 'style="border: ' . $border_style . ';"';
			$output .= '
			<div id="mpprecipe-container-' . $recipe->recipe_id . '" class="mpprecipe-container-border" ' . $style_tag . '>
			<div id="mpprecipe-container" class="serif mpprecipe">
			  <div id="mpprecipe-innerdiv" class="thirdkind">
				<div class="item mpp-top">';

			$image_hide = strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0;

			//!! Adjust to full width if no image
			if (!$recipe->recipe_image or $image_hide ) {


				$output .= "<style>
					#mpprecipe-container-$recipe->recipe_id .mpp-topright {
						width: 100% !important;
						border-left: solid #cccccc 1px !important;
					}
					#mpprecipe-container-$recipe->recipe_id .mpp-topleft {
						display:none !important;
					}
					#mpprecipe-container-$recipe->recipe_id .mpp-topright .fl-l {
						float:none !important;
					}
					#mpprecipe-container-$recipe->recipe_id div#mpp-buttons {
						float: none !important;
						margin: 0 auto !important;
						max-width: 284px !important;
					}
				</style>
				";
			}

			//!! create image container

			if ($recipe->recipe_image != null )
			{
				$class  = 'mpp-topleft';
				$style  = "background:url($recipe->recipe_image);background-size:cover;";

				if ($image_hide)
					$class .= ' hide-card';

				if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
					$class .= ' hide-print';

				$output .= "<div class='$class' style='$style' ></div>";
			}

			// Open mpp-topright panel if image
			if ($recipe->recipe_image != null || $recipe->summary != null)
				$output .= '<div class="mpp-topright">';

			if ( $recipe->author ) {
				/* AUTHOR LINK */
				$output .= mpprecipe_print_author( $recipe );
				/* END AUTHOR LINK */
			}

			 //!! yield and nutrition
			if ($recipe->yield != null) {
				$output .= '<p id="mpprecipe-yield">';
				if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_yield_label') . ' ';
				}
				$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
			}



			if ($recipe->serving_size != null && $recipe->serving_size != 1)
			{
				$output .= '<div id="mpprecipe-nutrition">';
					$output .= '<p id="mpprecipe-serving-size">';
					if (strcmp(get_option('mpprecipe_serving_size_label_hide'), 'Hide') != 0) {
						$output .= get_option('mpprecipe_serving_size_label') . ' ';
					}
					$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
				$output .= '</div>';
			}

			// add the title and close the item class
			$output .= '<div id="mpprecipe-title" itemprop="name" class="h-1" >' . $recipe->recipe_title . '</div>';

			if ($recipe->summary != null) {
				$output .= '<div id="mpprecipe-summary" itemprop="description">';
				$output .= mpprecipe_break( '<p class="summary">', mpprecipe_richify_item($recipe->summary, 'summary'), '</p>' );
				$output .= '</div>';
			}


			// open the zlmeta and fl-l container divs
			$output .= '<div class="fl-l">';

			// recipe timing
			if ($recipe->prep_time != null) {
				$prep_time = mpprecipe_format_duration($recipe->prep_time);

				$output .= '<p id="mpprecipe-prep-time">';
				$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span>';

				if (strcmp(get_option('mpprecipe_prep_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_prep_time_label') . ' ';
				}
				$output .= '</p>';
			}
			if ($recipe->cook_time != null) {
				$cook_time = mpprecipe_format_duration($recipe->cook_time);

				$output .= '<p id="mpprecipe-cook-time">';
				$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span>';

				if (strcmp(get_option('mpprecipe_cook_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_cook_time_label') . ' ';
				}
				$output .= '</p>';
			}


			$total_time         = null;
			$total_time_content = null;

			if ($recipe->total_time != null)
			{
				$total_time         = mpprecipe_format_duration($recipe->total_time);
				$total_time_content = $recipe->total_time;
			}
			elseif( ($recipe->prep_time || $recipe->cook_time ) and class_exists( 'DateInterval' ) and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
			{
				$t1 = new DateTime();
				$t2 = new DateTime();

				if( $recipe->prep_time ) $t1->add( new DateInterval($recipe->prep_time));
				if( $recipe->cook_time ) $t1->add( new DateInterval($recipe->cook_time));

				$ti = $t2->diff($t1);
				$total_time_content = $ti->format('P%yY%mM%dDT%hH%iM%sS');
			}

			if( $total_time_content )
			{
				$total_time = mpprecipe_format_duration($total_time_content);
				$output .= '<p id="mpprecipe-total-time">';
				$output .= '<span itemprop="totalTime" content="' . $total_time_content . '">' . $total_time . '</span>';

				if (strcmp(get_option('mpprecipe_total_time_label_hide'), 'Hide') != 0)
					$output .= get_option('mpprecipe_total_time_label') . ' ';

				$output .= '</p>';
			}

		   // Add Print and Save Button
			    $output .= mpp_buttons( $recipe->recipe_id );
                $output .= mpprecipe_ratings_html( $recipe );

			// add the MealPlannerPro recipe button
			if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
				$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

			}

			//!! close mpp-topright if there is an image
			if ($recipe->recipe_image != null || $recipe->summary != null) {
				$output .= '</div>';
			}
					$output .= '<div class="zlclear"></div>';

			//!! close the containers
			$output .= '</div><div class="zlclear"></div></div>';

	}


    $ingredient_type= '';
    $ingredient_tag = '';
    $ingredient_class = '';
    $ingredient_list_type_option = get_option('mpprecipe_ingredient_list_type');
    if (strcmp($ingredient_list_type_option, 'ul') == 0 || strcmp($ingredient_list_type_option, 'ol') == 0) {
        $ingredient_type = $ingredient_list_type_option;
        $ingredient_tag = 'li';
    } else if (strcmp($ingredient_list_type_option, 'p') == 0 || strcmp($ingredient_list_type_option, 'div') == 0) {
        $ingredient_type = 'span';
        $ingredient_tag = $ingredient_list_type_option;
    } else {
        $ingredient_type = 'span';
        $ingredient_tag = 'li';
    }

    if (strcmp(get_option('mpprecipe_ingredient_label_hide'), 'Hide') != 0) {

          /* START BUTTON CHANGES */
          $subdomain = get_option('mpprecipe_subdomain');

          if( $subdomain and get_option('mpprecipe_personalizedplugin') )
          {
              $output .= "
                <center><div id='mpp-buttons-2'>
                  <a target='_blank' href='" . MPPRECIPE_PROTOCOL . $subdomain . "." . MPPRECIPE_DOMAIN . "/recipe/recipeBox' class='myrecipe-button mpp-button'>My Recipes</a>
                  <a target='_blank' href='" . MPPRECIPE_PROTOCOL . $subdomain . "." . MPPRECIPE_DOMAIN . "/grocery-list' class='mylist-button mpp-button'>My Lists</a>
                  <a target='_blank' href='" . MPPRECIPE_PROTOCOL . $subdomain . "." . MPPRECIPE_DOMAIN . "/meal-planning-calendar' class='mycal-button mpp-button'>My Calendar</a>
              </div></center>";
          }
          /* END BUTTON CHANGES */

        $output .= '<p id="mpprecipe-ingredients" class="h-4 strong">' . get_option('mpprecipe_ingredient_label') . '</p>';
    }

    $output .= '<' . $ingredient_type . ' id="mpprecipe-ingredients-list">';
    $i = 0;
    $ingredients = explode("\n", $recipe->ingredients);
    foreach ($ingredients as $ingredient) {
        $ingredient = trim(strip_tags($ingredient));
        if(!empty($ingredient))
        {
            $output .= mpprecipe_format_item($ingredient, $ingredient_tag, 'ingredient', 'recipeIngredient', 'mpprecipe-ingredient-', $i);
            $i++;
        }
    }

    $output .= '</' . $ingredient_type . '>';

    if ( $custom_html = get_option('mpprecipe_custom_html') )
        $output .= stripslashes_deep($custom_html);

	// add the instructions
    if ($recipe->instructions != null) {

        $instruction_type= '';
        $instruction_tag = '';
        $instruction_list_type_option = get_option('mpprecipe_instruction_list_type');
        if (strcmp($instruction_list_type_option, 'ul') == 0 || strcmp($instruction_list_type_option, 'ol') == 0) {
            $instruction_type = $instruction_list_type_option;
            $instruction_tag = 'li';
        } else if (strcmp($instruction_list_type_option, 'p') == 0 || strcmp($instruction_list_type_option, 'div') == 0) {
            $instruction_type = 'span';
            $instruction_tag = $instruction_list_type_option;
        } else {
            $instruction_type = 'span';
            $instruction_tag  = 'li';
        }

        $instructions = explode("\n", $recipe->instructions);
        if (strcmp(get_option('mpprecipe_instruction_label_hide'), 'Hide') != 0) {
            $output .= '<p id="mpprecipe-instructions" class="h-4 strong">' . get_option('mpprecipe_instruction_label') . '</p>';
        }
        $output .= '<' . $instruction_type . ' id="mpprecipe-instructions-list" class="instructions">';
        $j = 0;
        foreach ($instructions as $instruction) {
            $instruction = trim($instruction);
            if (!empty($instruction)) {
            	$output .= mpprecipe_format_item($instruction, $instruction_tag, 'instruction', 'recipeInstructions', 'mpprecipe-instruction-', $j);
                $j++;
            }
        }
        $output .= '</' . $instruction_type . '>';
    }


    //if ( $recipe->cuisine || $recipe->type  ) {
        /* TAGS */
        //$output .= "<div class='recipe-bottomtags'>";

            /*
            if ( $recipe->course )
                $output .= "<strong>Course:</strong> $recipe->course <span>|</span>";
            */

            //if ( $recipe->cuisine )
            //    $output .= "<strong>Cuisine:</strong> $recipe->cuisine <span>|</span> ";

            //if ( $recipe->type )
            //    $output .= "<strong>Recipe Type:</strong> $recipe->type";

        //$output .= "</div>";
        /* END TAGS */
    //}

    //!! add notes section
    if ($recipe->notes != null) {
        if (strcmp(get_option('mpprecipe_notes_label_hide'), 'Hide') != 0) {
            $output .= '<p id="mpprecipe-notes" class="h-4 strong">' . get_option('mpprecipe_notes_label') . '</p>';
        }

        $output .= '<div id="mpprecipe-notes-list">';
        $output .= mpprecipe_break( '<p class="notes">', mpprecipe_richify_item($recipe->notes, 'notes'), '</p>' );
        $output .= '</div>';

    }

    /* TAGS */
    $text_tags = unserialize($recipe->tagged);
    if (isset($text_tags['text']) && is_array($text_tags['text'])) {
        $html_tags = "";

        foreach ($text_tags['text'] as $field => $tags) {
            $tags = @array_filter($tags);
            if (empty($tags)) continue;

            $bg_color = "";
            if ($field == 'diet') $bg_color = "style='background: #0366d6;'";
            if ($field == 'allergy') $bg_color = "style='background: #01a650;'";

            $itemprop = "";
            if ($field == 'occasions') $itemprop = 'itemprop="keywords"';
            if ($field == 'cuisines') $itemprop = 'itemprop="recipeCuisine"';
            if ($field == 'courses') $itemprop = 'itemprop="recipeCategory"';

            $type      = ucfirst($field);
            $html_tag  = '<dl class="tags">';
            $html_tag .= "<dt class='tag-title'>$type</dt>";
            foreach ($tags as $tag) {
                $html_tag .= "<dd $bg_color $itemprop>$tag</dd>";
            }
            $html_tag .= '</dl>';

            $html_tags .= "$html_tag";
        }

        $output .= '<div class="expand_wrapper">
                        <div id="mpprecipe-tags" class="h-4 strong expand_heading">
                            <input type="checkbox" checked="">
                            <div class="expand-title">
                                <h4>Tags</h4>';
                                if (strcmp(get_option('mpprecipe_tagged_display'), 'Hide') !== 0) {
                                    $output .= '<div class="expand-button tags-default-show"></div>';
                                } else {
                                    $output .= '<div class="expand-button tags-default-hide"></div>';
                                }
                                $output .= '
                            </div>';

                            if (strcmp(get_option('mpprecipe_tagged_display'), 'Hide') !== 0) {
                                $output .= '<div class="tags-show toggle-container toggle-container-tags">';
                            } else {
                                $output .= '<div class="tags-hide toggle-container toggle-container-tags">';
                            }

                            $output .= '
                                <div class="box">
                                    <div id="search_tags" class="recipe-bottomtags">';
                                        $output .= $html_tags;
                                        $output .= "";
        $output .=                  '</div>
                                </div>
                            </div>
                        </div>
                    </div>'; //toggle
    }
    /* END TAGS */

    if( $nutrition_style === 'nutrition_panel') {
        $output .= '
            <div class="expand_wrapper">
                <div id="mpprecipe-panel" class="h-4 strong expand_heading">
                    <input type="checkbox" checked="">
                        <div class="expand-title">
                            <h4>Nutrition</h4>
                            <div class="expand-button tags-default-show"></div>
                        </div>
                    </input>
                    <div class="toggle-container toggle-container-nutrition">
                        <div class="box">';
        $output .=          mpprecipe_print_nutrition_box( $recipe );
        $output .=      '</div>
                    </div>
                </div>
            </div>';
    } elseif ( $nutrition_style === 'minimal' ) {
        // minimal style
        $output .= '<div class="expand_wrapper">' . mpprecipe_print_nutrition( $recipe ) . '</div>';
    }

	// MealPlannerPro version
    $output .= '<div id="mealplannerpro-recipe-plugin" class="hide">' . MPPRECIPE_VERSION_NUM . '</div>';
    $output .= '<div id="mealplannerpro-recipe-id" class="hide">' . $recipe->recipe_id. '</div>';

    // Add permalink for printed output before closing the innerdiv
    if (strcmp(get_option('mpprecipe_printed_permalink_hide'), 'Hide') != 0) {
		$output .= '<a id="mpp-printed-permalink" href="' . $permalink . '"title="Permalink to Recipe">' . $permalink . '</a>';
	}

    $output .= '</div>';

    // Marked up and hidden image for Schema/Microformat compliance.
    $output .= "<link class='photo' itemprop='image' href='$recipe->recipe_image' />";

    // Add copyright statement for printed output (outside the dotted print line)
    $printed_copyright_statement = get_option('mpprecipe_printed_copyright_statement');
    if (strlen($printed_copyright_statement) > 0) {
		$output .= '
            <div id="mpp-printed-copyright-statement">
                <span itemprop="name">'
                . $printed_copyright_statement .
                '</span>
            </div>';
	}

    $output .= mpprecipe_print_er_nutrition( $recipe );
    $output .= mpprecipe_jsonld( $recipe );
    $output .= '</div>';

    // nutrition panel === below
    if ( $nutrition_style === 'below') {
        $output .= '</div><div class="outer-nutrition outer-nutrition-below">' . mpprecipe_print_nutrition( $recipe ) . '</div>';
    } else {
        $output .= '</div>';
    }

     $output_js = <<<HTML
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery(".toggle_container").show();
	jQuery("p.expand_heading").toggle(function(){
		jQuery(this).addClass("active"); 
		}, function () {
		jQuery(this).removeClass("active");
	});
	jQuery("p.expand_heading").click(function(){
		jQuery(this).next(".toggle_container").slideToggle("slow");
	});
});
</script>
HTML;

	//$output .= $output_js;

    mpprecipe_update_ratings($recipe->recipe_id);
    return $output;
}

function mpp_save_recipe_js( $subdomain = null )
{
    if( $subdomain and get_option('mpprecipe_personalizedplugin') )
        return "window.open('" . MPPRECIPE_PROTOCOL . $subdomain . MPPRECIPE_DOMAIN . "/clipper/direct?url=' + window.location.href); return false;";
    else
        return "var host='" . MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/';var s=document.createElement('script');s.type= 'text/javascript';try{if (!document.body) throw (0);s.src=host + '/javascripts/savebutton.js?date='+(new Date().getTime());document.body.appendChild(s);}catch (e){alert('Please try again after the page has finished loading.');}";
}

/*
 * Add Mealplannerpro.com buttons.
 */
function mpp_buttons( $recipe_id )
{
    $subdomain = get_option('mpprecipe_subdomain');
    if( $subdomain )
        $subdomain .= '.';

    $dir = MPPRECIPE_PLUGIN_DIRECTORY;

    $custom_print_image = get_option('mpprecipe_custom_print_image');
    $button_type  = 'butn-link';
    $button_image = "";
    if (strlen($custom_print_image) > 0) {
        $button_type  = 'print-link';
        $button_image = '<img alt="Print" src="' . $custom_print_image . '">';
    }

    $hide = "hide";
    if (strcmp(get_option('mpprecipe_print_link_hide'), 'Hide') != 0)
        $hide = '';

    $stylesheet = get_option('mpprecipe_stylesheet');
    $noimage    = strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0;
    $text_color = '#' . get_option( "mpprecipe_text_color" );
    $js         = mpp_save_recipe_js( $subdomain );


   #href  = '" . MPPRECIPE_PROTOCOL . $subdomain . "." . MPPRECIPE_DOMAIN ."/clipper/direct'
   #target= '_blank'

    return "
        <div id='mpp-buttons'>

            <div
               id    = 'mpp_saverecipe_button'
               class = 'save-button mpp-button'
               title = 'Save Recipe to Mealplannerpro.com'

            ><img alt = 'Save Recipe' src='" . $dir . "plus.png' style='margin-top:-1px;' />Save Recipe</div>

            <div
                id      = 'mpp_print_button'
                class   = '$button_type mpp-button $hide'
                title   = 'Print this recipe'
            > Print Recipe $button_image </div>
        </div>
        <script>
            var print_b = document.getElementById('mpp_print_button');
            var save_b  = document.getElementById('mpp_saverecipe_button');

            print_b.onclick = function(){ zlrPrint( \"mpprecipe-container-$recipe_id\", \"$dir\", \"$stylesheet\", \"$noimage\", \"$text_color\" ) };
            save_b.onclick  = function(){ $js };
        </script>
        ";
}



/**
 *************************************************************
 * Conversion functionality
 *************************************************************
 */

/**
 * Iterates through the Ziplist recipe table, copying every Ziplist recipe to
 * the Mealplanner pro recipe table, then updates the Wordpress posts to use Mealplanner pro
 * placemarkers in-place of Ziplist's.
 */
function mpp_convert_js()
{
    return "<script type='text/javascript'>

            convert_entries = function( vendor, revert )
            {
                revert = revert || false

                var lvendor = vendor.toLowerCase()
                var c = confirm( 'Click OK to begin converting your recipes. Please ensure you have a backup of your database or posts before continuing.' )

                if (!c)
                    return

                var action = 'convert'

                if (revert)
                    action = 'revert'

                var data = '?action=' + action + '_' + lvendor + '_entries'

                var r   = new XMLHttpRequest()
                r.open( 'GET', ajaxurl+data, true )

                var cid = action + '_' + lvendor + '_entries_container'

                document.getElementById(cid).innerHTML = 'Converting recipes. This can take a few minutes, please do not leave the page.'
                window.onbeforeunload = function () { return 'Recipes are still being converted, if you leave this page you will not know if it was successful.' };

                r.onreadystatechange = function()
                {
                    if( r.readyState == 4 && r.status == 200 )
                        document.getElementById(cid).innerHTML = r.responseText;

                    window.onbeforeunload = null
                }
                r.send()
            }
        </script>";
}

function mpp_convert_ziplist_like_entries( $table, $name )
{
    global $wpdb;

    $lname     = strtolower( $name );
    $zl_table  = $wpdb->prefix.$table;
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix.'posts';

    # Assume placemarker = tablename wth hyphens for underscores and non-plural.
    # e.g. amd_zlrecipe_recipes -> amd-zlrecipe-recipe:
    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s");
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";

    $zlrecipes = $wpdb->get_results("
        SELECT *
        FROM $wp_table p
        LEFT JOIN $zl_table z
            ON  p.ID    = z.post_id
        WHERE
            p.post_content REGEXP '$zl_placemarker_regex_general'
            AND p.post_status = 'publish'
    ");


    if( empty($zlrecipes) )
    {
        print "No $name recipes to convert.";
        die();
    }

    $count  = 0;
    $errors = array();
    foreach( $zlrecipes as $zlrecipe )
    {
        $zl_placemarker = "[$placemarker_name:$zlrecipe->recipe_id]";
        $data = array(
            'post_id'       => $zlrecipe->post_id,      'recipe_title'  => $zlrecipe->recipe_title,
            'recipe_image'  => $zlrecipe->recipe_image, 'summary'       => $zlrecipe->summary,
            'rating'        => $zlrecipe->rating,       'prep_time'     => $zlrecipe->prep_time,
            'cook_time'     => $zlrecipe->cook_time,    'total_time'    => $zlrecipe->total_time,
            'serving_size'  => $zlrecipe->serving_size, 'ingredients'   => $zlrecipe->ingredients,
            'instructions'  => $zlrecipe->instructions, 'notes'         => $zlrecipe->notes,
            'created_at'    => $zlrecipe->created_at,   'yield'         => $zlrecipe->yield,
            'calories'      => $zlrecipe->calories,     'fat'           => $zlrecipe->fat,
            'original'      => $zlrecipe->post_content, 'original_type' => $lname,
            'original_excerpt' => $zl_placemarker
        );

        $success = $wpdb->insert( $mpp_table, $data );

        if (!$success )
        {
            $errors[] = array( $zlrecipe->post_id, $zlrecipe->recipe_id );
            continue;
        }

        $mpp_recipe_id = $wpdb->insert_id;
        $mpp_placemarker = "[mpprecipe-recipe:$mpp_recipe_id]";

        $mpp_post = str_replace( $zl_placemarker, $mpp_placemarker, $zlrecipe->post_content );

        $wpdb->update(
            $wp_table,
            array( 'post_content' => $mpp_post ),
            array( 'ID' => $zlrecipe->post_id )
        );
        $count += 1;
    }

    if( !empty( $errors ) )
    {
        print "Converted with some errors. <br/>";
        print "Could not convert ";
        print "<ul>";
        foreach( $errors as $pair )
            print "<li>recipe with title '$pair[1]' from Post titlted '$pair[0]'</li>";
        print "</ul>";
    }
    else
    {
        print "Converted $count $name recipe(s) into Mealplanner Pro recipes!";
    }

    die();
}
function mpp_convert_ziplist_entries()
{
    mpp_convert_ziplist_like_entries( 'amd_zlrecipe_recipes', 'Ziplist' );
}
function mpp_convert_yummly_entries()
{
    mpp_convert_ziplist_like_entries( 'amd_yrecipe_recipes', 'Yummly' );
}
function mpp_revert_yummly_entries()
{
    mpp_revert_ziplist_like_entries( 'amd_yrecipe_recipes', 'Yummly' );
}
function mpp_revert_ziplist_entries()
{
    mpp_revert_ziplist_like_entries( 'amd_zlrecipe_recipes', 'Ziplist' );
}

function mpp_restore()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';

    $count = 0;
    $mpps         = $wpdb->get_results(
        "SELECT *
        FROM $wp_table p
        JOIN $mpp_table m ON p.ID = m.post_id
        WHERE m.original IS NOT NULL
        ORDER BY m.recipe_id DESC
        "
    );
    $processed_ids = array();

    foreach( $mpps as $mpp )
    {
        if( in_array( $mpp->ID, $processed_ids ) )
            continue;

        $wpdb->update(
            $wp_table,
            array( 'post_content' => $mpp->original ),
            array( 'ID' => $mpp->ID )
        );
        $processed_ids[] = $mpp->ID;
        $count += 1;
    }
    print "Restored $count posts to pre-conversion state.";
    die();
}
function mpp_revert_easyrecipe_entries()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';

    $count = 0;
    # If original no longer present but stored in mpp record, assumed deleted.
    $mpps         = $wpdb->get_results(
        "SELECT *
        FROM $wp_table p
        JOIN $mpp_table m ON p.ID = m.post_id
        WHERE
            p.post_content NOT REGEXP '<div class=\"easyrecipe[ \"]'
            AND
            m.original IS NOT NULL
            AND
            m.original_type = 'easyrecipe'
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");

    $processed_ids = array();
    foreach( $mpps as $mpp )
    {
        # Match easyrecipe content within post.
        $matches = array();
        $pattern = mpp_get_pattern('mpp');

        preg_match( $pattern, $mpp->post_content, $matches );

        if( empty( $matches ) )
            continue;

        # Support for anomalous version
        if( !$mpp->original_excerpt )
        {
            # if consists of excerpt only, if entire post
            if( strpos( $mpp->original, '<div class="easyrecipe' ) == 0 )
                $original_excerpt = $mpp->original;
            else
                $original_excerpt = mpp_extract_easyrecipe( 'easyrecipe' );
        }
        else
            $original_excerpt = $mpp->original_excerpt;

        $old_post = preg_replace( "$pattern", "$original_excerpt", $mpp->post_content );

        if( in_array( $mpp->ID, $processed_ids ) )
            continue;

        $wpdb->update(
            $wp_table,
            array( 'post_content' => $old_post ),
            array( 'ID' => $mpp->ID )
        );
        $processed_ids[] = $mpp->ID;
        $count += 1;
    }
    print "Converted $count Meal Planner Pro recipe(s) into EasyRecipe recipes!";
    die();
}
//Will only return single match
function mpp_get_pattern( $type )
{
    if ($type == 'easyrecipe')
       return '#<div class="easyrecipe ?.*?".*<div class="endeasyrecipe".*?</div>\s*</div>#s';
    elseif ($type == 'mpp')
        return '#\[mpprecipe-recipe:\d+\]#s';
}

function mpp_extract_easyrecipe( $post )
{
    $pattern = mpp_get_pattern('easyrecipe');
    preg_match( "$pattern", $post, $matches );

    if( empty($matches) )
        return False;
    else
        return $matches[0];
}
function mpp_convert_easyrecipe_entries()
{
    global $wpdb;

    $wp_table  = $wpdb->prefix.'posts';
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";
    $count     = 0;

    $easyrecipes = $wpdb->get_results("
        SELECT * FROM $wp_table p
        WHERE
            p.post_content REGEXP '<div class=\"easyrecipe[ \"]'
            AND p.post_status = 'publish'
    ");

    $originals = array();
    $excerpts  = array();
    foreach( $easyrecipes as $easyrecipe )
    {
        # Match easyrecipe content within post.
        $easyrecipe_excerpt = mpp_extract_easyrecipe( $easyrecipe->post_content );

        if( !$easyrecipe_excerpt )
            continue;

        $originals[ $easyrecipe->ID ] = $easyrecipe->post_content;
        $excerpts[  $easyrecipe->ID ] = $easyrecipe_excerpt;
    }

    if( !empty($excerpts) )
    {
        $html_batch_json  = json_encode( $excerpts );
        $easyrecipes_conv = mpp_convert_easyrecipe_call( $excerpts );

        foreach( $easyrecipes_conv as $id => $er_recipe )
        {
            $recipe = array(
                'post_id'       => $id,                   'recipe_title'  => $er_recipe->name,
                'recipe_image'  => $er_recipe->image,        'summary'       => $er_recipe->summary,
                'rating'        => $er_recipe->rating,       'prep_time'     => $er_recipe->prepTime,
                'cook_time'     => $er_recipe->cookTime,     'total_time'    => $er_recipe->totalTime,
                'serving_size'  => $er_recipe->servingSize,  'ingredients'   => $er_recipe->ingredients,
                'instructions'  => $er_recipe->instructions, 'notes'         => $er_recipe->note,
                'yield'         => $er_recipe->recipeYield,
                'calories'      => $er_recipe->calories,
                'author'        => $er_recipe->author,       'cuisine'       => $er_recipe->cuisine,
                'type'          => $er_recipe->type,

                'fiber'          => $er_recipe->fiber,
                'protein'        => $er_recipe->protein,
                'cholesterol'    => $er_recipe->cholesterol,
                'fat'            => $er_recipe->fat,
                'satfat'         => $er_recipe->satfat,
                'unsatfat'       => $er_recipe->unsatfat,
                'transfat'       => $er_recipe->transfat,
                'sodium'         => $er_recipe->sodium,
                'sugar'          => $er_recipe->sugar,

                'original'          => $originals[ $id ],
                'original_excerpt'  => $excerpts[ $id ],
                'original_type'     => 'easyrecipe'
            );

            $mpp_recipe = $wpdb->get_row("SELECT * FROM $mpp_table WHERE post_id = '$id'");

            if (!empty($mpp_recipe)) {
                $success   = true;
                $recipe_id = $mpp_recipe->recipe_id;
            } else {
                if ($success = $wpdb->insert($mpp_table, $recipe)) {
                    $recipe_id = $wpdb->insert_id;
                }
            }

            if( $success )
            {
                $pattern        = mpp_get_pattern('easyrecipe');
                $converted_post = preg_replace( "$pattern", "[mpprecipe-recipe:$recipe_id]", $originals[$id] );
                $wpdb->update(
                    $wp_table,
                    array( 'post_content' => $converted_post ),
                    array( 'ID' => $id )
                );
                $count += 1;

                mpprecipe_extract_easyrecipe_ratings( $recipe,$recipe_id );
            }
        }
    }
    //mpp_getnutrition(); // Batch calculate all recipes without nutrition.
    mpp_background_process(); // Background process calculate all recipes without nutrition.
    print "Converted $count EasyRecipe recipe(s) into Mealplanner Pro recipes!";
    die();
}
function mpprecipe_extract_easyrecipe_ratings( $recipe,$recipe_id )
{
    global $wpdb;

    $wp_table         = $wpdb->prefix.'posts';
    $comments_table   = $wpdb->prefix.'comments';
    $comm_meta_table  = $wpdb->prefix.'commentmeta';
    $mpp_table        = $wpdb->prefix.'mpprecipe_recipes';
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";

    $post_id = $recipe['post_id'];

    $qry="SELECT meta_value as rating,c.comment_id as comment_id
        FROM   $comm_meta_table cm
        JOIN   $comments_table  c on cm.comment_id=c.comment_ID
        WHERE  c.comment_post_ID=$post_id
        AND    meta_key='ERRating'
    ";
    $er_ratings = $wpdb->get_results($qry);

    foreach( $er_ratings as $er_rating )
    {
        $rating = (array) $er_rating;
        $rating['recipe_id'] = $recipe_id;
        $rating_success = $wpdb->insert( $recipe_ratings_table, $rating );
    }

    mpprecipe_update_ratings( $recipe_id );
}

// Serialize and batch convert
function mpp_convert_easyrecipe_call( $excerpts )
{
    $data = array('html' => json_encode( $excerpts ) );
    $response = mpprequest( "post", MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . '/api/clipper/erparsebatch', $data );
    return json_decode( $response );
}

add_action( 'wp_ajax_convert_easyrecipe_entries', 'mpp_convert_easyrecipe_entries' );
function mpp_convert_easyrecipe_entries_form()
{
    global $wpdb;

    $wp_table    = $wpdb->prefix.'posts';
    $easyrecipes = $wpdb->get_var("
        SELECT count(*) as count FROM $wp_table p
        WHERE
        p.post_content REGEXP '<div class=\"easyrecipe[ \"]'
        AND p.post_status = 'publish'
    ");

    if ( $easyrecipes == 0 )
        return;

    return ("
        <div id='convert_easyrecipe_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> EasyRecipe Data Detected </h4>
            <p>
                Found $easyrecipes recipe(s).
                Press this button if you wish to convert all your existing EasyRecipe recipes to Mealplanner Pro recipes.
            </p>
            <button onclick='convert_entries(\"EasyRecipe\")'>Convert EasyRecipe Recipes</button>
            <p>
                The content of all your posts will be the same except Mealplanner Pro will
                be used instead of EasyRecipe for both display and editing of existing recipes created through the EasyRecipe plugin.
            </p>
        </div>
    ");
}

// Convert ziplist derivatives to mpp
function mpp_convert_ziplist_like_entries_form( $table, $name )
{
    global $wpdb;

    $lname = strtolower( $name );

    $zl_table  = $wpdb->prefix.$table;
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix.'posts';

    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s");
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";

    # Select all recipes with placemarker match, but only published recipes.
    $zlrecipes         = $wpdb->get_var(
        "SELECT count( distinct p.ID)
        FROM $wp_table p
        WHERE
            p.post_content REGEXP '$zl_placemarker_regex_general'
            AND p.post_status = 'publish'
    ");

    if ( $zlrecipes == 0 )
        return;

    return ("
        <div id='convert_${lname}_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> $name Data Detected </h4>
            <p>
                Found $zlrecipes $name recipes.
                Press this button if you wish to convert all your existing $name recipes to Mealplanner Pro recipes.
            </p>
            <button onclick='convert_entries(\"$name\")'>Convert $name Recipes</button>
            <p>
                The content of all your posts will be the same except Mealplanner Pro will
                be used instead of $name for both display and editing of existing recipes created through the $name plugin.
            </p>
        </div>
    ");
}

function mpp_convert_yumprint_entries_form()
{
    global $wpdb;

    $name      = 'Recipecard';
    $table     = 'yumprint_recipe_recipe';
    $lname     = 'yumprint';

    $yp_table  = $wpdb->prefix.$table;
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix.'posts';

    $placemarker_name     = 'yumprint-recipe';
    $yp_placemarker_regex_general = "$placemarker_name id=\'[0-9]+\'";

    # Select all recipes with placemarker match, but only published recipes.
    $yprecipes         = $wpdb->get_var(
        "SELECT count( distinct p.ID)
        FROM $wp_table p
        WHERE
            p.post_content REGEXP '$yp_placemarker_regex_general'
            AND p.post_status = 'publish'
    ");

    if ( $yprecipes == 0 )
        return;

    return ("
        <div id='convert_${lname}_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> $name Data Detected </h4>
            <p>
                Found $yprecipes $name recipes.
                Press this button if you wish to convert all your existing $name recipes to Mealplanner Pro recipes.
            </p>
            <button onclick='convert_entries(\"$lname\")'>Convert $name Recipes</button>
            <p>
                The content of all your posts will be the same except Mealplanner Pro will
                be used instead of $name for both display and editing of existing recipes created through the $name plugin.
            </p>
        </div>
    ");
}
function mpp_collapse_recipecard_obj ( $obj_arr )
{
    $output = '';

    foreach( $obj_arr as $obj )
    {
        if( $obj->title and !isset($obj->lines) )
            continue;

        $title = trim($obj->title);
        if( !empty( $title ) )
            $output .= "\n!".$title ;

        $output .= implode( "\n", $obj->lines );
    }
    return $output;
}
// Convert yumprint to mpp
add_action( 'wp_ajax_convert_yumprint_entries', 'mpp_convert_yumprint_entries' );
function mpp_convert_yumprint_entries()
{
    global $wpdb;

    $name      = 'yumprint';
    $table     = 'yumprint_recipe_recipe';
    $lname     = strtolower( $name );
    $yp_table  = $wpdb->prefix.'yumprint_recipe_recipe';
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix.'posts';

    $placemarker_name     = 'yumprint-recipe';
    $yp_placemarker_regex_general = "$placemarker_name id=\'[0-9]+\'";

    $yprecipes = $wpdb->get_results("
        SELECT *
        FROM $wp_table p
        LEFT JOIN $yp_table z
            ON  p.ID    = z.post_id
        WHERE
            p.post_content REGEXP '$yp_placemarker_regex_general'
            AND p.post_status = 'publish'
    ");
            #AND z.post_id is not NULL


    if( empty($yprecipes) )
    {
        print "No Recipecard recipes to convert.";
        die();
    }

    $count  = 0;
    $errors = array();
    foreach( $yprecipes as $yprecipe )
    {
        $yp_placemarker = "[$placemarker_name id='$yprecipe->id']";
        $yprecipe_recipe = json_decode($yprecipe->recipe);
        $yprecipe_nutrition = json_decode($yprecipe->nutrition);


        $servings = ($yprecipe_recipe->servings) ? $yprecipe_recipe->servings : 1;
        $calories = round($yprecipe_nutrition->calories/$servings);
        $totalFat      = round($yprecipe_nutrition->totalFat/$servings);
        $totalCarbohydrates      = round($yprecipe_nutrition->totalCarbohydrates/$servings);
        $dietaryFiber      = round($yprecipe_nutrition->dietaryFiber/$servings);
        $protein      = round($yprecipe_nutrition->protein/$servings);
        $cholesterol      = round($yprecipe_nutrition->cholesterol/$servings);
        $sodium      = round($yprecipe_nutrition->sodium/$servings);
        $sugars      = round($yprecipe_nutrition->sugars/$servings);

        # Consider adding yumprint 'adated' + 'adaptedLink' properties as note

        $data = array(
            'post_id'       => $yprecipe->post_id,             'recipe_title'  => $yprecipe_recipe->title,
            'recipe_image'  => $yprecipe_recipe->image,        'summary'       => $yprecipe_recipe->summary,
            'prep_time'     => $yprecipe_recipe->prepTime,
            'cook_time'     => $yprecipe_recipe->cookTime,     'total_time'    => $yprecipe_recipe->totalTime,
            'serving_size'  => $yprecipe_recipe->servings,

            'ingredients'   => mpp_collapse_recipecard_obj( $yprecipe_recipe->ingredients ),
            'instructions'  => mpp_collapse_recipecard_obj( $yprecipe_recipe->directions ),
            'notes'         => mpp_collapse_recipecard_obj( $yprecipe_recipe->notes ),

            'created_at'    => $yprecipe_recipe->created_at,   'yield'         => $yprecipe_recipe->yields,

            'calories'      => $calories,
            'fat'           => $totalFat,
            'fiber'         => $dietaryFiber,
            'protein'       => $protein,
            'cholesterol'   => $cholesterol,
            'sodium'        => $sodium,
            'sugar'         => $sugars,
            'carbs'         => $totalCarbohydrates,

            'original'      => $yprecipe->post_content,        'original_type' => $lname,
            'original_excerpt' => $yp_placemarker
        );

        $success = $wpdb->insert( $mpp_table, $data );

        if (!$success )
        {
            $errors[] = array( $yprecipe->post_id, $yprecipe->id );
            continue;
        }

        $mpp_recipe_id = $wpdb->insert_id;
        $mpp_placemarker = "[mpprecipe-recipe:$mpp_recipe_id]";

        $mpp_post = str_replace( $yp_placemarker, $mpp_placemarker, $yprecipe->post_content );

        $wpdb->update(
            $wp_table,
            array( 'post_content' => $mpp_post ),
            array( 'ID' => $yprecipe->post_id )
        );
        $count += 1;
    }

    if( !empty( $errors ) )
    {
        print "Converted with some errors. <br/>";
        print "Could not convert ";
        print "<ul>";
        foreach( $errors as $pair )
            print "<li>recipe with title '$pair[1]' from Post titlted '$pair[0]'</li>";
        print "</ul>";
    }
    else
    {
        print "Converted $count Recipecard recipe(s) into Mealplanner Pro recipes!";
    }

    die();
}
// reversion generic
function mpp_revert_generic_entries_form( $name, $placemarker_regex, $alias = null )
{
    if( !$alias ) $alias = $name;
    $lname       = strtolower($name);
    $mpps        = mpp_get_converted( $name, $placemarker_regex, True );

    if ( $mpps == 0 )
        return;

    return ("
        <div id='revert_${lname}_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> Convert back from Meal Planner Pro to $alias </h4>
            <p>
                Found $mpps recipe(s).
                Press this button if you wish to reverse Meal Planner Pro recipes converted from $alias back to $alias recipes.
            </p>
            <button onclick='convert_entries(\"$lname\", true)'>Convert Meal Planner Pro Recipes</button>
        </div>
    ");
}

// Convert ziplist to mpp
add_action( 'wp_ajax_convert_ziplist_entries', 'mpp_convert_ziplist_entries' );
function mpp_convert_ziplist_entries_form()
{
    return mpp_convert_ziplist_like_entries_form( 'amd_zlrecipe_recipes', 'Ziplist' );
}
// Convert yummly to mpp
add_action( 'wp_ajax_convert_yummly_entries', 'mpp_convert_yummly_entries' );
function mpp_convert_yummly_entries_form()
{
    return mpp_convert_ziplist_like_entries_form( 'amd_yrecipe_recipes', 'Yummly' );
}

// revert yummly form
add_action( 'wp_ajax_revert_yummly_entries', 'mpp_revert_yummly_entries' );
function mpp_revert_yummly_entries_form()
{
    return mpp_revert_ziplist_like_form( 'amd_yrecipe_recipes', 'Yummly' );
}
// revert ziplist form
add_action( 'wp_ajax_revert_ziplist_entries', 'mpp_revert_ziplist_entries' );
function mpp_revert_ziplist_entries_form()
{
    return mpp_revert_ziplist_like_form( 'amd_zlrecipe_recipes', 'Ziplist' );
}
// revert yumprint form
add_action( 'wp_ajax_revert_yumprint_entries', 'mpp_revert_yumprint_entries' );
function mpp_revert_yumprint_entries_form()
{
    $placemarker = "yumprint-recipe id=\'[0-9]+\'";
    return mpp_revert_generic_entries_form( 'yumprint', $placemarker, 'Recipecard' );
}

# Find mpp recipes converted from named third party (original !null), where original post no longer contains third party placemarker (! placemarker)
function mpp_get_converted( $name, $placemarker, $count = False )
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';
    $lname       = strtolower($name);

    if( $count )
    {
        $select = 'count(distinct p.ID)';
        $func   = 'get_var';
    }
    else
    {
        $select = '*';
        $func   = 'get_results';
    }

    return $wpdb->$func(
        "SELECT $select
        FROM $wp_table p
        JOIN $mpp_table m ON p.ID = m.post_id
        WHERE
            p.post_content NOT REGEXP '$placemarker'
            AND
            m.original IS NOT NULL
            AND
            m.original_type = '$lname'
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");
}
// revert yumprint
function mpp_revert_yumprint_entries()
{
    $name        = 'yumprint';
    $placemarker = "yumprint-recipe id=\'[0-9]+\'";
    mpp_revert_generic_entries(  $name, $placemarker, "Recipecard" );
}

// revert easyrecipe
add_action( 'wp_ajax_revert_easyrecipe_entries', 'mpp_revert_easyrecipe_entries' );
function mpp_revert_easyrecipe_entries_form()
{
    global $wpdb;
    $placemarker_regex = '<div class=\"easyrecipe[ \"]';
    # If original no longer present but stored in mpp record, assumed deleted.
    $mpps        = mpp_get_converted( "easyrecipe", $placemarker_regex, True );

    if ( $mpps == 0 )
        return;

    return ("
        <div id='revert_easyrecipe_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> Convert back from Meal Planner Pro to EasyRecipe </h4>
            <p>
                Found $mpps recipe(s).
                Press this button if you wish to reverse Meal Planner Pro recipes converted from EasyRecipe back to EasyRecipe recipes.
            </p>
            <button onclick='convert_entries(\"EasyRecipe\", true)'>Convert Meal Planner Pro Recipes</button>
        </div>
    ");
}
function mpp_revert_generic_entries( $name, $placemarker, $alias = null )
{
    global $wpdb;
    if( !$alias ) $alias = $name;

    $wp_table    = $wpdb->prefix.'posts';
    $lname       = strtolower($name);
    $mpps        = mpp_get_converted( $lname, $placemarker );

    $count = 0;
    $processed_ids = array();
    foreach( $mpps as $mpp )
    {
        $matches = array();
        $pattern = mpp_get_pattern('mpp');

        preg_match( $pattern, $mpp->post_content, $matches );

        if( empty( $matches ) )
            continue;

        $generic_original  = $mpp->original_excerpt;
        $mpp_placemarker = "[mpprecipe-recipe:$mpp->recipe_id]";

        $old_post        = str_replace( $mpp_placemarker, $generic_original, $mpp->post_content );

        if( in_array( $mpp->ID, $processed_ids ) )
            continue;

        $wpdb->update(
            $wp_table,
            array( 'post_content' => $old_post ),
            array( 'ID' => $mpp->ID )
        );
        $processed_ids[] = $mpp->ID;
        $count += 1;
    }
    print "Converted $count Meal Planner Pro recipe(s) into $alias recipes!";
    die();
}

function mpp_revert_ziplist_like_entries( $table, $name )
{
    global $wpdb;
    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s");
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";
    mpp_revert_generic_entries( $name, $zl_placemarker_regex_general );
}

function mpp_revert_ziplist_like_form( $table, $name )
{
    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s");
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";
    return mpp_revert_generic_entries_form( $name, $zl_placemarker_regex_general );
}

// Restore all converted posts
add_action( 'wp_ajax_mpp_restore', 'mpp_restore' );
function mpp_restore_form()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';

    $wp_table    = $wpdb->prefix.'posts';
    # If original no longer present but stored in mpp record, assumed deleted.
    $mpp         = $wpdb->get_results(
        "SELECT *
        FROM $wp_table p
        JOIN $mpp_table m ON p.ID = m.post_id
        WHERE
            m.original IS NOT NULL
            LIMIT 1"
    );

    if ( !$mpp )
        return;

    return ("
        <div id='mpp_restore_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> Revert Conversions </h4>
            <p>
                Press this button to undo changes that occured after converting to Meal Planner Pro.
            </p>
            <button onclick='mpp_restore()'>Restore Posts</button>
        </div>
        <script>
            mpp_restore = function()
            {
                var c = confirm( 'Click OK to restore converted posts to their pre-conversion state. Please ensure you have a backup of your database or posts before continuing.' )

                if (!c)
                    return

                var data = '?action=mpp_restore'

                var r   = new XMLHttpRequest()
                var cid = 'mpp_restore_container'
                r.open( 'GET', ajaxurl+data, true )

                document.getElementById(cid).innerHTML = 'Restoring posts. This can take a few minutes, please do not leave the page.'
                window.onbeforeunload = function () { return 'Posts are still being restored, if you leave this page you will not receive a success message.' };

                r.onreadystatechange = function()
                {
                    if( r.readyState == 4 && r.status == 200 )
                        document.getElementById(cid).innerHTML = r.responseText;

                    window.onbeforeunload = null
                }
                r.send()
            }
        </script>
    ");
}
function mpp_getnutrition()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';

    $mpps         = $wpdb->get_results(
        "SELECT *
        FROM $mpp_table m
        JOIN $wp_table p ON p.ID = m.post_id
        WHERE
            m.server_recipe_id IS NULL
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");


    $recipes_json = json_encode($mpps);

    $data = array(
        'host'    => mpp_gethostname(),
        'recipes' => $recipes_json
    );

    $response     = mpprequest( "post", MPPRECIPE_PROTOCOL . MPPRECIPE_DOMAIN . "/api/wordpress/saverecipebatch", $data);
    $nutritions   = json_decode( $response, true );

    foreach( $nutritions as $recipe_id => $nutrition )
        $wpdb->update( $wpdb->prefix . "mpprecipe_recipes", $nutrition, array( 'recipe_id' => $recipe_id ));
}

function mpp_background_process()
{
    global $wpdb;
    $mpp_table = $wpdb->prefix . 'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix . 'posts';

    $mpps        = $wpdb->get_results(
        "SELECT *
        FROM $mpp_table m
        JOIN $wp_table p ON p.ID = m.post_id
        WHERE
            m.server_recipe_id IS NULL
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");
    $mpp_process = new MPP_Api_Process();
    foreach ($mpps as $recipe) {
        $mpp_process->push_to_queue($recipe->recipe_id);
    }
    $mpp_process->save()->dispatch();
}

add_action( 'plugins_loaded', 'mpp_init' );
function mpp_init()
{
    new MPP_Api_Request();
    new MPP_Api_Process();
}

/**
 * Partner Integrations
 */
function mpp_swoopcode()
{
    $swoop_id = get_option('mpprecipe_swoop_id');

    if( !empty( $swoop_id ) )
    {
        return "
            <!-- +SWOOP -->
            <script type='text/javascript'>
              (function addSwoopOnce(domain) {
                var win = window;
                try {
                  while (!(win.parent == win || !win.parent.document)) {
                    win = win.parent;
                  }
                } catch (e) {
                  /* noop */
                }
                var doc = win.document;
                if (!doc.getElementById('swoop_sdk')) {
                  var serverbase = doc.location.protocol + '//ardrone.swoop.com/';
                  var s = doc.createElement('script');
                  s.type = 'text/javascript';
                  s.src = serverbase + 'js/spxw.js';
                  s.id = 'swoop_sdk';
                  s.setAttribute('data-domain', domain);
                  s.setAttribute('data-serverbase', serverbase);
                  doc.head.appendChild(s);
                }
              })('$swoop_id');
            </script>
            <!-- -SWOOP -->";
    }
    else
        return "";
}

/**
 * Compatibility hacks for autooptimize
add_filter('autoptimize_filter_js_exclude','mpprecipe_ao_override_jsexclude',10,1);
function mpprecipe_ao_override_jsexclude($exclude) {
    $js = array(
        'jscolor.min.js',
        'mpprecipe_editor_plugin.js',
        'mpprecipe_print.js',
    );
    return $exclude . "," . implode(',', $js);
}
 */
/*
* autoptimize_filter_css_exclude: CSS optimization exclude strings, as configured in admin page
add_filter('autoptimize_filter_css_exclude','mpprecipe_ao_override_cssexclude',10,1);
function mpprecipe_ao_override_cssexclude($exclude) {
    $css = array(
        'mpprecipe-common.css', 'mpprecipe-design10.css', 'mpprecipe-design11.css',
        'mpprecipe-design13.css', 'mpprecipe-design14.css',
        'mpprecipe-design15.css', 'mpprecipe-design16.css', 'mpprecipe-design17.css',
        'mpprecipe-design18.css', 'mpprecipe-design19.css', 'mpprecipe-design20.css',
        'mpprecipe-design21.css', 'mpprecipe-design2.css', 'mpprecipe-design22.css',
         'mpprecipe-design23.css', 'mpprecipe-design24.css', 'mpprecipe-design3.css',
        'mpprecipe-design4.css', 'mpprecipe-design5.css', 'mpprecipe-design6.css',
        'mpprecipe-design7.css', 'mpprecipe-design8.css', 'mpprecipe-design9.css',
        'mpprecipe-dlog.css', 'mpprecipe-minimal-nutrition.css', 'mpprecipe-print-bare.css',
        'mpprecipe-print.css', 'mpprecipe-std.css',
    );
    return $exclude . "," . implode(',', $css);
}
*/

/**
 * Ratings
 */
function mpprecipe_ratings_fractional_stars_html( $rating )
{
    if( !get_option('mpprecipe_ratings') )
        return;

    $html = "<div class='mpp-stars-wrapper'><div class='mpp-fractional-stars'></div>";
    $html .= "<div class='mpp-fractional-stars-highlight' style='width:" . $rating / 5 * 100 . "px;'></div></div>";
    return $html;
}
function mpprecipe_ratings_stars_html( $rating, $clickable = True, $all_empty = False )
{
    if( !get_option('mpprecipe_ratings') )
        return;

    $html    = '';
    $onclick = '';
    for( $i=1; $i<=5; $i++ )
    {
        if( $i <= $rating and !$all_empty )
        {
            $class = "rating-full";
            $star_html = "&#9733";
        }
        else
        {
            $class = "rating-empty";
            $star_html = "&#9734";
        }

        if($clickable)
            $onclick = "rating_click($i)";

        $html .= "<span title='$i' class='rating-$i rating $class' onclick='$onclick'>$star_html</span>";
    }
    return $html;
}
function mpprecipe_ratings_html( $recipe, $comment = False )
{
    if( !get_option('mpprecipe_ratings') )
        return;

    $r         = $recipe->rating;
    $rc        = $recipe->rating_count;
    $r_rnd     = round( $r, 1);
    $regurl    = wp_registration_url();
    $recipe_id = $recipe->recipe_id;
    $html      = '';

    if($comment)
        $html .= "Rate this recipe: " . mpprecipe_ratings_stars_html( $r, True, $comment );
    else
        $html .= mpprecipe_ratings_fractional_stars_html( $r );

    if($r and !$comment )
        $html .= "<p id='mpprecipe-rating' itemprop='aggregateRating'>
                  <span itemprop='ratingValue'>$r_rnd</span> based on <span itemprop='reviewCount'>$rc</span> review(s)</p>";

    $comment_field_hack =  "<input id='mpprecipe_comment_rating' name='mpprecipe_comment_rating' type='hidden' value=''></input>";
    $comment_field_hack .= "<input id='mpprecipe_comment_data' name='mpprecipe_comment_data'  type='hidden' value='$recipe_id'></input>";

    if( $comment )
        $html .= $comment_field_hack;

    return $html;
}

add_action( 'comment_form', 'mpprecipe_ratings_comment_form',0 );
function mpprecipe_ratings_comment_form($post_id)
{
    if( !get_option('mpprecipe_ratings'))
        return;

    $recipe       = mpprecipe_get_post_mpprecipe($post_id);
    $ratings_html = '';

    if( $recipe )
        $ratings_html = mpprecipe_ratings_html( $recipe, True );

    echo <<<HTML
    <div id='comment_rating'>
    $ratings_html
    </div>
    <script>
        var rating=document.getElementById('comment_rating')
        var comment=document.getElementsByClassName('form-submit')[0]

        var par = comment.parentNode;
        par.insertBefore(rating,comment)
    </script>
HTML;
}

add_action( 'comment_post', 'mpprecipe_ratings_comment_save' );
function mpprecipe_ratings_comment_save($comment_id,$comment_approved = null)
{
    if( !get_option('mpprecipe_ratings') )
        return;

    if( empty($_POST['mpprecipe_comment_data']) or empty($_POST['mpprecipe_comment_rating']) )
        return;

    global $wpdb;
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";
    $recipe_id = $_POST['mpprecipe_comment_data'];
    $rating = $_POST['mpprecipe_comment_rating'];

    $wpdb->insert($recipe_ratings_table, array(
        'rating'      => $rating,
        'recipe_id'   => $recipe_id,
        'comment_id'  => $comment_id,
    ));

    mpprecipe_update_ratings( $recipe_id );
}

add_action('delete_comment', 'mpprecipe_ratings_comment_delete');
add_action('trash_comment', 'mpprecipe_ratings_comment_delete');
add_action('spam_comment', 'mpprecipe_ratings_comment_delete');
function mpprecipe_ratings_comment_delete($comment_id)
{
    global $wpdb;
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";
    $sql                  = "SELECT * FROM " . $recipe_ratings_table . " WHERE comment_id = " . $comment_id;
    $recipe               = $wpdb->get_row($sql, ARRAY_A);
    if (isset($recipe['recipe_id'])) {
        $recipe_id = $recipe['recipe_id'];
        $wpdb->delete($recipe_ratings_table, array('comment_id' => $comment_id));
        mpprecipe_update_ratings($recipe_id);
    }
}

add_filter( 'get_comment_text', 'mpprecipe_ratings_comment_display' );
function mpprecipe_ratings_comment_display($comment)
{
    if( !get_option('mpprecipe_ratings'))
    {
        echo $comment;
        return;
    }

    global $wpdb;
    $comment_id = get_comment_ID();
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";
    $rating_html = null;

    $user_rating_qry = "SELECT rating FROM  $recipe_ratings_table WHERE  comment_id=$comment_id";

    $rating = $wpdb->get_row( $user_rating_qry );

    if($rating)
        $rating_html = "<div id='comment-display-rating'>". mpprecipe_ratings_stars_html( $rating->rating, False ) ."</div>";

    echo $comment . "<br/>" . $rating_html;
}

function mpprecipe_get_post_mpprecipe($post_id)
{
    global $wpdb;
    $recipes_table = $wpdb->prefix."mpprecipe_recipes";

    return $wpdb->get_row("SELECT * FROM $recipes_table WHERE post_id=$post_id order by recipe_id desc");
}
function mpprecipe_update_ratings( $recipe_id )
{
    global $wpdb;
    $recipes_table        = $wpdb->prefix . "mpprecipe_recipes";
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";

    $recipe_rating_qry = $wpdb->prepare(
        "SELECT AVG(rating) as rating,
         COUNT(rating)      as rating_count
         FROM $recipe_ratings_table
         WHERE recipe_id=%d
         AND   rating>0
         ",
         $recipe_id
    );

    $rt =  $wpdb->get_row( $recipe_rating_qry );
    $wpdb->update( $recipes_table, (array) $rt, array(
        'recipe_id' => $recipe_id,
    ));
    return $rt;
}

function mpp_extract_convert_ratings()
{
    global $wpdb;
    $recipes_table        = $wpdb->prefix . "mpprecipe_recipes";
    $recipe_ratings_table = $wpdb->prefix . "mpprecipe_ratings";

    $converted_recipes = $wpdb->get_results(
        "SELECT recipe_id,original_type
         FROM   $recipes_table
         WHERE  original IS NOT NULL"
    );
    foreach( $converted_recipes as $converted )
    {

    }
}

/**
 * Post recipes to MPP
 */
add_action('save_post', 'mpprecipe_post_status_to_mpp');
function mpprecipe_post_status_to_mpp($post_ID)
{
    global $wpdb;
    $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "mpprecipe_recipes where post_id='$post_ID' ", ARRAY_A);
    if ($recipe) {
        $recipe_id   = $recipe['recipe_id'];
        $mpp_process = new MPP_Api_Process();
        $mpp_process->push_to_queue($recipe_id);
        $mpp_process->save()->dispatch();
    }
}
