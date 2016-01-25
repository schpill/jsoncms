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

    class CmsrouterProject
    {
        private $method, $request, $controller, $action, $render, $app;

        public function __construct()
        {
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
            $config = path('pages') . DS . $this->controller . DS . 'configs' . DS . $this->action . '.json';
            $layout = path('layouts') . DS . $this->controller . DS . $this->action . '.php';

            if ((!is_file($config) || !is_file($layout)) && $first) {
                $this->controller   = 'main';
                $this->action       = 'is404';
                $this->render       = true;
                $this->boot(false);
            }

            if ((!is_file($config) || !is_file($layout)) && !$first) {
                throw new Exception('You must define a valid route to process.');
            }

            $this->app = coll(
                json_decode(
                    File::read($config),
                    true
                )
            );

            if (true === $this->render) {
                if ($this->action == 'is404') {
                    // http_response_code(404);
                    header("HTTP/1.0 404 Not Found");
                }

                $this->render($layout);
            }
        }

        private function render($layout)
        {
            echo $this->layout($layout, null);
        }

        private function layout($layout, $content)
        {
            $this->app['content'] = $content;

            $cms = $this->app;

            ob_start();
            require_once $layout;
            $content = ob_get_clean();

            if (isset($cmsLayout)) {
                return $this->layout(
                    str_replace(
                        $this->action . '.php',
                        $cmsLayout . '.php',
                        $layout
                    ),
                    $content
                );
            } else {
                return $content;
            }
        }
    }
