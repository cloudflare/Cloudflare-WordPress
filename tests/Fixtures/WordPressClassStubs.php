<?php

/**
 * Minimal stubs for WordPress core classes referenced by the plugin.
 *
 * The plugin's PHPUnit suite runs without a WordPress runtime, so core
 * classes like WP_Post and WP_Taxonomy don't exist. Code under test uses
 * these only as type guards (is_a($x, 'WP_Post'), $tax instanceof WP_Taxonomy),
 * so empty user-defined classes in the global namespace are sufficient.
 *
 * This is the same pattern used by 10up/wp_mock and brain/monkey at
 * bootstrap time. Once the suite migrates to one of those libraries, this
 * file can be removed.
 */

if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public $ID;
        public $post_name = '';
        public $post_status = 'publish';
        public $post_type = 'post';

        /**
         * @param object $post Post data, copied onto the instance as WordPress does.
         */
        public function __construct($post)
        {
            foreach (get_object_vars($post) as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

if (!class_exists('WP_Taxonomy')) {
    class WP_Taxonomy
    {
        public $name;
        public $public = true;

        /**
         * @param string       $taxonomy    Taxonomy key.
         * @param array|string $object_type Object types the taxonomy is registered for.
         * @param array        $args        Taxonomy arguments.
         */
        public function __construct($taxonomy, $object_type, $args = array())
        {
            $this->name = $taxonomy;
        }
    }
}
