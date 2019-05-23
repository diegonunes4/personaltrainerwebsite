<?php

class MPP_Api_Process extends WP_Background_Process
{

    /**
     * @var string
     */
    protected $action = 'mpp_api_process';

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $recipe_id Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($recipe_id)
    {
        global $wpdb;
        $sql    = "SELECT r.*, t.tagged FROM " . $wpdb->prefix . "mpprecipe_recipes AS r LEFT JOIN  " . $wpdb->prefix . "mpprecipe_tags AS t ON (r.recipe_id = t.recipe_id) WHERE r.recipe_id='$recipe_id' ";
        $recipe = $wpdb->get_row($sql, ARRAY_A);
        if ($recipe) {
            mpprecipe_register_recipe($recipe);
        }

        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete()
    {
        parent::complete();

        // Show notice to user or perform some other arbitrary task...
    }

}