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
            'pluginUrl' => $this->config['plugin_url'],
        ]);

        echo "<script type='text/javascript'>\n";
        echo 'var LightKit = ' . wp_json_encode($vars) . ';';
        echo "\n</script>";
    }

    /**
     * Load Controllers with Priorities
     */
    public function load_controllers()
    {
        $controllers = glob($this->config['controllers_path'] . '/*.php');
    
        $controllersPriorities = [];
        $classes = [];

        foreach ($controllers as $controller) {
            if ( ! file_exists($controller)) {
                continue;
            }

            $className = str_replace('.php', '', class_basename($controller));
            $className = "GigaAI\\Controller\\{$className}";

            if ($className === "GigaAI\\Controller\\Controller") {
                array_unshift($controllersPriorities, $controller);
            } else {
                $classes[] = $className;
                $controllersPriorities[] = $controller;
            }
        }
        
        array_map(function ($controller) {
            require_once $controller;
        }, $controllersPriorities);
       
        array_map(function ($class) {
            new $class();
        }, $classes);
    }
}
