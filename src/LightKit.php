<?php

namespace GigaAI\Lightkit;

class LightKit
{
    /**
     * @var array
     */
    public $config = [
        'namespace' => 'giga-ai',
    ];

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;

        add_action('admin_print_footer_scripts', [$this, 'js_vars'], 9);

        $this->load_controllers();
    }

    public function js_vars()
    {
        $vars = apply_filters('lightkit_js_vars', [
            'url'     => get_site_url(),
            'restUrl' => get_rest_url(),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);

        echo "<script type='text/javascript'>\n";
        echo 'var LightKit = ' . wp_json_encode($vars) . ';';
        echo "\n</script>";
    }

    public function load_controllers()
    {
        $plugins = get_option('active_plugins');

        foreach ($plugins as $plugin) {
            $plugin_path = plugin_dir_path(WP_PLUGIN_DIR . '/' . $plugin);

            $controllers_path = $plugin_path . 'controllers';
            $views_path       = $plugin_path . 'views';
            $controllers      = glob($controllers_path . '/*.php');

            foreach ($controllers as $controller) {
                if ( ! file_exists($controller)) {
                    continue;
                }

                require_once $controller;

                $className = str_replace('.php', '', class_basename($controller));
                $class     = "LightKit\\Controller\\{$className}";

                if (class_exists($class)) {
                    $instance = new $class();
                    $instance->set_views_path($views_path);
                }
            }
        }
    }
}
