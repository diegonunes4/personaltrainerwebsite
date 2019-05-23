<?php

class MPP_Api_Request extends WP_Async_Request
{
    /**
     * @var string
     */
    protected $action = 'mpp_api_request';

    /**
     * Handle
     *
     * Override this method to perform any actions required
     * during the async request.
     */
    protected function handle()
    {
        global $wpdb;
        $recipe_id = $_POST['recipe_id'];
        $sql    = "SELECT r.*, t.tagged FROM " . $wpdb->prefix . "mpprecipe_recipes AS r LEFT JOIN  " . $wpdb->prefix . "mpprecipe_tags AS t ON (r.recipe_id = t.recipe_id) WHERE r.recipe_id='$recipe_id' ";
        $recipe = $wpdb->get_row($sql, ARRAY_A);
        if ($recipe) {
            mpprecipe_register_recipe($recipe);
        }

        return false;
    }

}