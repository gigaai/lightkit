<?php

namespace GigaAI\LightKit;

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
}
