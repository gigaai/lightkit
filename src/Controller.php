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
     * @var Request
     */
    public $request;

    /**
     * @var Setting
     */
    public $setting;

    /**
     * @var string
     */
    public $viewsPath = null;

    /**
     * @var mixed
     */
    private $handled = false;

    public $pluginUrl = '';

    public $redirecting = null;

    public $isWithCalled = false;

    public function __construct()
    {
        $this->init();

        add_action('admin_menu', [$this, 'menu'], $this->getResourcePriority());

        add_action('admin_enqueue_scripts', [$this, 'media']);

        add_action('rest_api_init', [$this, 'register_routes']);

        add_action('admin_init', [$this, 'handle_submit'], 99);

        add_action('admin_print_footer_scripts', [$this, 'jsVars'], 9);

        add_action('admin_notices', [$this, 'adminNotices']);
    }

    public function adminNotices()
    {
        // Todo: resolve cookie also
        $status  = Cookie::get( 'status' );
        $message = Cookie::get( 'message' );

        if ( ! empty( $status ) && ! empty( $message ) ) :
            ?>
            <div id="message" class="notice notice-<?php echo esc_attr( $status ); ?> is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'kelpee' ); ?></span>
                </button>
            </div>
            <div class="clearfix"></div>
        <?php endif;
    }

    public function jsVars()
    {
        $vars = apply_filters('lightkit_js_vars', [
            'url'       => get_site_url(),
            'restUrl'   => get_rest_url(),
            'nonce'     => wp_create_nonce('wp_rest'),
            'pluginUrl' => $this->getPluginUrl(),
        ]);

        echo "<script type='text/javascript'>\n";
        echo 'var LightKit = ' . wp_json_encode($vars) . ';';
        echo "\n</script>";
    }

    public function getPluginUrl()
    {
        $urlToLightKitDir = plugin_dir_url(__DIR__);
        $urlToPluginDir = substr($urlToLightKitDir, 0, 0 - strlen('vendor/gigaai/lightkit/'));

        return $urlToPluginDir;
    }

    public function setPluginUrl($url)
    {
        $this->pluginUrl = $url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function handle_submit()
    {
        $request = $this->request;

        // Remove doubled _wp_http_referer
        if ($request->has('_wp_http_referer') && $request->isMethod('get')) {
            wp_redirect(remove_query_arg(['_wp_http_referer']));
            exit;
        }

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

            // These methods below needs to verify nonce
            if (
                $request->has('lightkit_admin_submit') &&
                ! wp_verify_nonce($request->get('lightkit_admin_submit'), 'lightkit_admin_submit') &&
                ! $request->isMethod('get')
            ) {
                wp_die('Hacked huh?');
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

        $this->setting = new Setting($this->getResourceNamespace());

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
        if (isset($this->resource['namespace'])) {
            return $this->resource['namespace'];
        }

        if (isset($this->resource['parent'])) {
            return $this->resource['parent'];
        }

        return $this->getResourceName();
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

        if (wp_doing_ajax()) {
            return function ($request) use ($method) {
                $params = $request->get_params();
                $this->request->set($params);

                return $this->resolveAndHandle($method, $params);
            };
        }

        $params = $_REQUEST;
        $this->request->set($params);

        return $this->resolveAndHandle($method, $params);
    }

    private function resolveAndHandle($method, $params = [])
    {
        $resolver = new Resolver;

        $params['request'] = $this->request;
        $params['setting'] = $this->setting;

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
        if (isset($this->resource['menu']) && $this->resource['menu'] === false) {
            return;
        }

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
    public function getViewsPath()
    {
        $pathToLightKit = dirname(__FILE__);
        $pathToPlugin = substr($pathToLightKit, 0, 0 - strlen('vendor/gigaai/lightkit/src'));
        $viewsPath = $pathToPlugin . 'views';

        return $viewsPath;
    }

    /**
     * @param $path
     */
    public function setViewsPath($path)
    {
        $this->viewsPath = $path;

        return $this;
    }

    /**
     * Render the view
     */
    protected function view($viewName, $data = [])
    {
        $viewsPath = $this->getViewsPath();

        // Replace the dot path if people using this
        $viewName = str_replace('.', '/', $viewName);

        $viewNamePath = $viewsPath . '/' . $viewName . '.php';

        // Let the plugin to add global variable
        $shared = apply_filters('view_share', []);
        $data   = array_merge($data, $shared);

        $this->addJsVar($data);

        // define a closure with a scope for the variable extraction
        $result = function ($view, array $data = []) {
            ob_start();
            extract($data, EXTR_SKIP);
            try {
                if (file_exists($view)) {
                    include $view;
                }
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

    public function back($flashes = [])
    {
        return $this->redirect(wp_get_referer(), $flashes);
    }

    /**
     * @param $url
     */
    public function redirect($url, $flashes = [])
    {
        Cookie::flash($flashes);

        return $this->setRedirecting($url);
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

    public function getRedirecting()
    {
        return $this->redirecting;
    }

    public function setRedirecting($redirecting)
    {
        $this->redirecting = $redirecting;

        if ($this->isWithCalled) {
            $this->doRedirect();
        }

        return $this;
    }

    public function with($key, $value = null)
    {
        $this->isWithCalled = true;

        Cookie::flash($key, $value);

        $this->doRedirect();

        return $this;
    }

    public function doRedirect()
    {
        $redirecting = $this->getRedirecting();

        if ($redirecting !== null) {
            wp_redirect($redirecting);
            exit;
        }
    }
}