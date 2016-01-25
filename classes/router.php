<?php
    /**
     * Thin is a swift Framework for PHP 5.4+
     *
     * @package    Thin
     * @version    1.0
     * @author     Gerald Plusquellec
     * @license    BSD License
     * @copyright  1996 - 2016 Gerald Plusquellec
     * @link       http://github.com/schpill/thin
     */

    namespace Thin;

    class RouterProject
    {
        private $method, $request, $page, $controller, $action, $render;

        public function __construct()
        {
            $this->page     = isAke($_REQUEST, 'page', 'home');
            $this->request  = core('request');
            $this->method   = $this->request->method();

            $routes         = include path('config') . DS . 'routes.php';

            $routes         = isAke($routes, $this->method, []);

            $this->handling($routes);

            if (is_callable($this->route)) {
                $cb     = $this->route;
                $args   = call_user_func_array($cb, $this->params);

                if (!is_array($args)) {
                    $args = ['main', $args, true];
                }

                if (count($args) == 2) {
                    $args[] = true;
                }

                $this->controller   = current($args);
                $this->action       = $args[1];
                $this->render       = end($args);
            } else {
                $this->controller   = 'main';
                $this->action       = 'is404';
                $this->render       = true;
            }

            $this->boot();
        }

        public function getUri()
        {
            $before = str_replace('/index.php', '', isAke($_SERVER, 'SCRIPT_NAME', ''));
            $uri    = substr($_SERVER['REQUEST_URI'], strlen($before));

            if (strstr($uri, '?')) {
                $uri = substr($uri, 0, strpos($uri, '?'));
            }

            $uri = '/' . trim($uri, '/');

            return $uri;
        }

        public function handling($routes, $quit = true)
        {
            $this->route = null;

            $uri = $this->getUri();

            foreach ($routes as $pattern => $cb) {
                if ($pattern != '/') {
                    $pattern = '/' . $pattern;
                }

                if (preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {
                    $matches = array_slice($matches, 1);

                    $params = array_map(function ($match, $index) use ($matches) {
                        if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                            return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                        } else {
                            return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                        }
                    }, $matches, array_keys($matches));

                    $this->route = $cb;
                    $this->params = $params;

                    return true;
                }
            }
        }

        private function boot($first = true)
        {
            $controllerFile = path('module') . DS . 'controllers' . DS . $this->controller . '.php';

            if (!is_file($controllerFile) && $first) {
                $this->controller   = 'main';
                $this->action       = 'is404';
                $this->render       = true;
                $this->boot(false);
            }

            if (!is_file($controllerFile) && !$first) {
                throw new Exception('You must define a valid route to process.');
            }

            loaderCore('controller');

            require_once $controllerFile;

            $class = '\\Thin\\' . ucfirst(Inflector::lower($this->controller)) . 'Controller';

            $actions    = get_class_methods($class);
            $father     = get_parent_class($class);

            if ($father == 'Thin\ControllerCore') {
                $a      = $this->action;
                $method = $this->method;

                $action = Inflector::lower($method) . ucfirst(
                    Inflector::camelize(
                        strtolower($a)
                    )
                );

                $controller         = new $class;
                $controller->_name  = $this->controller;
                $controller->action = $a;

                if (in_array('boot', $actions)) {
                    $controller->boot();
                }

                if (in_array($action, $actions)) {
                    $controller->$action();
                } else {
                    $this->controller   = 'main';
                    $this->action       = 'is404';
                    $this->render       = true;
                    $this->boot(false);
                }

                if (in_array('unboot', $actions)) {
                    $controller->unboot();
                }
            }

            if (true === $this->render) {
                $this->render($controller);
            }
        }

        private function render($controller)
        {
            $tpl = path('module') . DS . 'views' . DS . $controller->_name . DS . $controller->action . '.phtml';

            if (File::exists($tpl)) {
                $content = File::read($tpl);

                $content = str_replace(
                    '$this->partial(\'',
                    '\\Thin\\RouterProject::partial($controller, \'' . path('module') . DS . 'views' . DS . 'partials' . DS,
                    $content
                );

                $content = str_replace(
                    '$this->',
                    '$controller->',
                    $content
                );

                $content = str_replace(['%%=', '%%'], ['<?php echo ', '; ?>'], $content);

                $file = path('cache') . DS . sha1($content) . '.display';

                File::put($file, $content);

                ob_start();

                include $file;

                $html = ob_get_contents();

                ob_end_clean();

                File::delete($file);

                echo $html;
            } else {
                echo '<h1>Error 404</h1>';
            }
        }

        public static function partial($controller, $partial)
        {
            if (File::exists($partial)) {
                $content = File::read($partial);

                $content = str_replace(
                    '$this->partial(\'',
                    '\\Thin\\RouterProject::partial($controller, \'' . path('module') . DS . 'views' . DS . 'partials' . DS,
                    $content
                );

                $content = str_replace(
                    '$this->',
                    '$controller->',
                    $content
                );

                $content = str_replace(['%%=', '%%'], ['<?php echo ', '; ?>'], $content);

                $tab        = explode(DS, $partial);
                $last       = str_replace('.phtml', '', array_pop($tab));
                $beforeLast = array_pop($tab);
                $partialKey = "$beforeLast.$last";

                $file = path('cache') . DS . sha1($content) . '.display';

                File::put($file, $content);

                ob_start();

                include $file;

                $html = ob_get_contents();

                ob_end_clean();

                File::delete($file);

                echo $html;
            } else {
                echo '';
            }
        }
    }
