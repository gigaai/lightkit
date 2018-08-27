<?php

namespace GigaAI\LightKit;

use GigaAI\LightKit\Request;
use GigaAI\Resolver\Resolver;

/**
 * Controller lets developers have a start point to create admin page and rest api with Laravel like structure.
 *
 * @since 3.0
 */
abstract class Controller extends \WP_REST_Controller
{
    /**
     * @var array
     */
    public $resource = [];

    /**
     * @var mixed
     */
    public $request;

    /**
     * @var string
     */
    public $views_path = '';

    /**
     * @var mixed
     */
    private $handled = false;

    public function __construct()
    {
        $this->init();

        add_action('admin_menu', [$this, 'menu'], $this->getResourcePriority());

        add_action('admin_enqueue_scripts', [$this, 'media']);

        add_action('rest_api_init', [$this, 'register_routes']);

        add_action('admin_init', [$this, 'handle_submit'], 99);
    }

    /**
     * @return mixed
     */
    public function handle_submit()
    {
        $request = $this->request;

        if ($request->filled('page') && $request->is('page', $this->getResourceName())) {

            if ($request->filled('action') && $request->action != false && $request->action != -1 && ! $request->isMethod('get')) {
                $action = $request->get('action');
                $action = str_replace(['-', ' '], '_', $action);

                return $this->handleCallback($action);
            }

            if ($request->filled('action2') && $request->action2 != false && $request->action2 != -1 && ! $request->isMethod('get')) {
                $action = $request->get('action2');
                $action = str_replace(['-', ' '], '_', $action);

                return $this->handleCallback($action);
            }

            if ($request->isMethod('delete')) {
                return $this->handleCallback('destroy');
            }

            if ($request->isMethod(['put', 'patch'])) {
                return $this->handleCallback('update');
            }

            if ($request->isMethod('post')) {
                return $this->handleCallback('store');
            }
        }
    }

    /**
     * @return mixed
     */
    public function adminRoutes()
    {
        $request = $this->request;

        if ($request->filled('page') && $request->is('page', $this->getResourceName())) {

            if ($request->filled('action') && $request->action && $request->action != -1) {
                $action = $request->get('action');

                $action = str_replace(['-', ' '], '_', $action);

                return $this->handleCallback($action);
            }

            if ($request->filled('action2') && $request->action && $request->action != -1) {
                $action = $request->get('action2');

                $action = str_replace(['-', ' '], '_', $action);

                return $this->handleCallback($action);
            }

            if ($request->isMethod('get')) {
                if ($request->filled('id')) {
                    return $this->handleCallback('edit');
                }

                return $this->handleCallback('index');
            }
        }
    }

    protected function init()
    {
        $this->request = Request::getInstance();
        $this->request->set($_REQUEST);

        if (isset($_FILES) && ! empty($_FILES)) {
            $this->request->set($_FILES);
        }

        do_action('giga_controller_pre_load');

        $module = $this->resource;

        $moduleName = $this->getResourceName();

        do_action("giga_controller_{$moduleName}_pre_load");
    }

    private function getResourceName()
    {
        return isset($this->resource['name']) ? $this->resource['name'] : rtrim(strtolower(class_basename($this)),
            'controller');
    }

    private function getResourceParent()
    {
        return isset($this->resource['parent']) ? $this->resource['parent'] : $this->namespace;
    }

    public function getResourceNamespace()
    {
        return isset($this->resource['namespace']) ? $this->resource['namespace'] : $this->getResourceName();
    }
    /**
     * @param $parent
     */
    private function setResourceParent($parent)
    {
        $this->resource['parent'] = $parent;
    }

    private function getResourcePermission()
    {
        return isset($this->resource['permission']) ? $this->resource['permission'] : 'manage_options';
    }

    /**
     * @param $name
     */
    private function setResourceName($name)
    {
        $this->resource['name'] = $name;
    }

    /**
     * @return mixed
     */
    private function getResourceTitle()
    {
        if (isset($this->resource['title'])) {
            return $this->resource['title'];
        }

        return title_case($this->getResourceName());
    }

    /**
     * @param $title
     */
    private function setResourceTitle($title)
    {
        $this->resource['title'] = $title;
    }

    public function getResourcePriority()
    {
        return isset($this->resource['priority']) ? $this->resource['priority'] : 11;
    }

    public function getResourceIcon()
    {
        return isset($this->resource['icon']) ? $this->resource['icon'] : null;
    }

    /**
     * Register API Routes for API & AJAX request
     *
     * @return void
     */
    public function register_routes()
    {
        // We mimic AJAX request by filtering into wp_doing_ajax
        add_filter('wp_doing_ajax', '__return_true');

        // Then register routes for the AJAX. Basically
        register_rest_route($this->getResourceNamespace(), '/' . $this->getResourceName(), [
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => $this->handleCallback('index'),
            ],
            [
                'methods'  => \WP_REST_Server::CREATABLE,
                'callback' => $this->handleCallback('store'),
            ],
        ]);

        register_rest_route($this->getResourceNamespace(), '/' . $this->getResourceName() . '/(?P<id>[\d]+)', [
            [
                'methods'  => \WP_REST_Server::READABLE,
                'callback' => $this->handleCallback('edit'),
            ],
            [
                'methods'  => \WP_REST_Server::EDITABLE,
                'callback' => $this->handleCallback('update'),
            ],
            [
                'methods'  => \WP_REST_Server::DELETABLE,
                'callback' => $this->handleCallback('destroy'),
            ],
        ]);
    }

    /**
     * Bind parameter to the method and let the method freely to use params
     *
     * @param String $method method to call
     *
     * @return mixed
     */
    private function handleCallback($method)
    {
        $this->handled = true;

        $resolver = new Resolver();

        if (wp_doing_ajax()) {
            return function ($request) use ($resolver, $method) {
                $id = $request->get_param('id');

                $params = $request->get_params();
                $this->request->set($params);
                $params['request'] = $this->request;

                if ($method === 'index' && $this->request->filled('action') && $this->request->action != false && $this->request->action != -1) {
                    $method = $this->request->action;
                }

                if ($method === 'index' && $this->request->filled('action2') && $this->request->action2 != false && $this->request->action2 != -1) {
                    $method = $this->request->action2;
                }

                $method = str_replace(['-', ' '], '_', $method);
                $method = $method === 'delete' ? 'destroy' : $method;

                return $resolver->bind($params)->resolve([$this, $method]);
            };
        }
        
        $params = $_REQUEST;
        $this->request->set($params);
        $params['request'] = $this->request;

        if ($method === 'index' && $this->request->filled('action') && $this->request->action != false && $this->request->action != -1) {
            $method = $this->request->action;
        }

        if ($method === 'index' && $this->request->filled('action2') && $this->request->action2 != false && $this->request->action2 != -1) {
            $method = $this->request->action2;
        }

        $method = str_replace(['-', ' '], '_', $method);
        $method = $method === 'delete' ? 'destroy' : $method;

        return $resolver->bind($params)->resolve([$this, $method]);
    }

    /**
     * Register menu
     */
    public function menu()
    {
        if (isset($this->resource['is_parent']) && $this->resource['is_parent']) {
            add_menu_page(
                $this->getResourceTitle(),
                $this->getResourceTitle(),
                $this->getResourcePermission(),
                $this->getResourceName(),
                [$this, 'adminRoutes'],
                $this->getResourceIcon()
            );
        }

        add_submenu_page(
            $this->getResourceParent(),
            $this->getResourceTitle(),
            $this->getResourceTitle(),
            $this->getResourcePermission(),
            $this->getResourceName(),
            [$this, 'adminRoutes']
        );
    }

    public function media()
    {
        //
    }

    /**
     * @return mixed
     */
    public function get_views_path()
    {
        return $this->views_path;
    }

    /**
     * @param $path
     */
    public function set_views_path($path)
    {
        $this->views_path = $path;
    }

    /**
     * Render the view
     */
    protected function view($viewName, $data = [])
    {
        $view_path = $this->get_views_path();

        // Replace the dot path if people using this
        $viewName = str_replace('.', '/', $viewName);

        $viewNamePath = $view_path . '/' . $viewName . '.php';

        // Let the plugin to add global variable
        $shared = apply_filters('view_share', []);
        $data   = array_merge($data, $shared);

        $this->addJsVar($data);

        // define a closure with a scope for the variable extraction
        $result = function ($view, array $data = []) {
            ob_start();
            extract($data, EXTR_SKIP);
            try {
                include $view;
            } catch (\Exception $e) {
                ob_end_clean();
                throw $e;
            }

            return ob_get_clean();
        };

        // call the closure
        echo $result($viewNamePath, $data);

        return $this;
    }

    /**
     * @param $variable
     * @param $value
     * @return mixed
     */
    public function addJsVar($variable, $value = null)
    {
        if (isset($value)) {
            $variable = [$variable => $value];
        }

        add_filter('lightkit_js_vars', function ($vars) use ($variable) {
            return array_merge($vars, $variable);
        });

        return $this;
    }

    public function back()
    {
        $referer = wp_get_referer();

        wp_safe_redirect($referer);

        exit;
    }

    /**
     * @param $url
     */
    public function redirect($url)
    {
        wp_safe_redirect($url);

        exit;
    }

    public function url()
    {
        $url = 'admin.php?page=' . $this->getResourceName();

        if ($this->request->filled('id')) {
            $url = $url . '&id=' . $this->request->get('id');
        }

        return admin_url($url);
    }

    /**
     * @param array $params
     */
    public function route($params = [])
    {
        if ( ! isset($params['page'])) {
            $params['page'] = $this->getResourceName();
        }

        return admin_url('admin.php?' . http_build_query($params));
    }
}
