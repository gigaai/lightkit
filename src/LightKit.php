<?php

namespace GigaAI\Lightkit;

class LightKit
{
    /**
     * @var array
     */
    public $config = [];

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($config, $this->config);

        add_action('admin_print_footer_scripts', [$this, 'js_vars'], 9);

        $this->load_controllers();
    }

    public function js_vars()
    {
        $vars = apply_filters('lightkit_js_vars', [
            'url'       => get_site_url(),
            'restUrl'   => get_rest_url(),
            'nonce'     => wp_create_nonce('wp_rest'),
            'pluginUrl' => $this->config['pluginUrl'],
        ]);

        echo "<script type='text/javascript'>\n";
        echo 'var LightKit = ' . wp_json_encode($vars) . ';';
        echo "\n</script>";
    }

    public function load_controllers()
    {
        $controllers = glob($this->config['controllers_path'] . '/*.php');

        foreach ($controllers as $controller) {
            if ( ! file_exists($controller)) {
                continue;
            }

            require_once $controller;

            $className = str_replace('.php', '', class_basename($controller));
            $class     = "GigaAI\\Controller\\{$className}";

            if (class_exists($class)) {
                $instance = new $class();
                $instance->set_views_path($this->config['views_path']);
            }
        }
    }
}
