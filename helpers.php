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

    function session($name = 'core', $adapter = 'session', $ttl = 3600)
    {
        switch ($adapter) {
            case 'session': return lib('session', [$name]);
            case 'redis':   return RedisSession::instance($name, $ttl);
        }
    }

    function libProject($lib, $args = null)
    {
        $lib    = strtolower(Inflector::uncamelize($lib));
        $script = str_replace('_', DS, $lib) . '.php';

        if (fnmatch('*_*', $lib)) {
            $class  = 'Thin\\' . str_replace('_', '\\', $lib);
            $tab    = explode('\\', $class);
            $first  = $tab[1];
            $class  = str_replace('Thin\\' . $first, 'Thin\\' . ucfirst($first) . 'Project', $class);

            if (count($tab) > 2) {
                for ($i = 2; $i < count($tab); $i++) {
                    $seg    = trim($tab[$i]);
                    $class  = str_replace('\\' . $seg, '\\' . ucfirst($seg), $class);
                }
            }
        } else {
            $class = 'Thin\\' . ucfirst($lib) . 'Project';
        }

        $file = path('module') . DS . 'classes' . DS . $script;

        if (file_exists($file)) {
            require_once $file;

            if (empty($args)) {
                return new $class;
            } else {
                if (!is_array($args)) {
                    if (is_string($args)) {
                        if (fnmatch('*,*', $args)) {
                            $args = explode(',', str_replace(', ', ',', $args));
                        } else {
                            $args = [$args];
                        }
                    } else {
                        $args = [$args];
                    }
                }

                $methods = get_class_methods($class);

                if (in_array('instance', $methods)) {
                    $check = new \ReflectionMethod($class, 'instance');

                    if ($check->isStatic()) {
                        return call_user_func_array([$class, 'instance'], $args);
                    }
                } else {
                    return construct($class, $args);
                }
            }
        }

        if (class_exists('Thin\\' . $lib)) {
            $c = 'Thin\\' . $lib;

            return new $c;
        }

        if (class_exists($lib)) {
            return new $lib;
        }

        throw new Exception("The Project $class does not exist.");
    }

    function loaderProject($lib)
    {
        $class = 'Thin\\' . ucfirst(strtolower($lib)) . 'Lib';

        if (!class_exists($class)) {
            $file = path('module') . DS . 'classes' . DS . $lib . '.php';

            if (file_exists($file)) {
                require_once $file;

                return true;
            }

            return false;
        }

        return true;
    }

    function wdd()
    {
        array_map(
            function($str) {
                echo '<pre style="background: #ffffdd; padding: 5px; color: #aa4400; font-family: Ubuntu; font-weight: bold; font-size: 22px; border: solid 2px #444400">';
                print_r($str);
                echo '</pre>';
                hr();
            },
            func_get_args()
        );
        die;
    }

    function wvd()
    {
        array_map(
            function($str) {
                echo '<pre style="background: #ffffdd; padding: 5px; color: #aa4400; font-family: Ubuntu; font-weight: bold; font-size: 22px; border: solid 2px #444400">';
                print_r($str);
                echo '</pre>';
                hr();
            },
            func_get_args()
        );
    }

    $debug = 'production' != APPLICATION_ENV;

    if (true === $debug) {
        error_reporting(-1);

        set_exception_handler(function($exception) {
            wvd('EXCEPTION', $exception, debug_backtrace());
        });

        set_error_handler(function($type, $message, $file, $line) {
            $exception      = new \ErrorException($message, $type, 0, $file, $line);

            $typeError      = Arrays::in(
                $type,
                [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]
            ) ? 'FATAL ERROR' : 'ERROR';

            if (!fnmatch('*gzinflate*', $message) && !fnmatch('*call_user_func_array*', $message) && !fnmatch('*undefined constant*', $message) && !fnmatch('Undefined offset:*', $message) && !fnmatch('*StreamConnection.php*', $file) && !fnmatch('*connected*', $message)) {
                $start      = $line > 5 ? $line - 5 : $line;
                $code       = File::readLines($file, $start, $line + 5);

                $lines      = explode("\n", $code);

                $codeLines  = [];

                $i          = $start;

                foreach ($lines as $codeLine) {
                    if ($i == $line) {
                        array_push($codeLines, $i . '. <span style="background-color: gold; color: black;">' . $codeLine . '</span>');
                    } else {
                        array_push($codeLines, $i . '. ' . $codeLine);
                    }

                    $i++;
                }

                wdd(
                    '<div style="text-align: center; padding: 5px; color: black; border: solid 1px black; background: #f2f2f2;">' . $typeError . '</div>',
                    '<div style="padding: 5px; color: red; border: solid 1px red; background: #f2f2f2;">' . $message . '</div>',
                    '<div style="padding: 5px; color: navy; border: solid 1px navy; background: #f2f2f2;">' . $file . ' [<em>line: <u>' . $line . '</u></em>]</div>',
                    '<div style="font-family: Consolas; font-weight: 400; padding: 5px; color: green; border: solid 1px green; background: #f2f2f2;">' . implode("\n", $codeLines) . '</div>',
                    '<div style="text-align: center; padding: 5px; color: black; border: solid 1px black; background: #f2f2f2;">BACKTRACE</div>',
                    '<div style="padding: 5px; color: purple; border: solid 1px purple; background: #f2f2f2;">' . displayCodeLines() . '</div>'
                );
            }
        });

        register_shutdown_function(function() {
            $exception = error_get_last();

            if ($exception) {
                $message    = isAke($exception, 'message', 'NA');
                $type       = isAke($exception, 'type', 1);
                $line       = isAke($exception, 'line', 1);
                $file       = isAke($exception, 'file');
                $exception  = new \ErrorException($message, $type, 0, $file, $line);

                $typeError  = Arrays::in(
                    $type,
                    [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]
                ) ? 'FATAL ERROR' : 'ERROR';

                if (fnmatch('*Allowed memory size*', $message)) {
                    dd($file . '['.$message.']', 'Ligne:' . $line);
                } elseif (!fnmatch('*undefinedVariable*', $message) && !fnmatch('*connected*', $message) && file_exists($file)) {
                    $start      = $line > 5 ? $line - 5 : $line;
                    $code       = File::readLines($file, $start, $line + 5);

                    $lines      = explode("\n", $code);

                    $codeLines  = [];

                    $i          = $start;

                    foreach ($lines as $codeLine) {
                        if ($i == $line) {
                            array_push($codeLines, $i . '. <span style="background-color: gold; color: black;">' . $codeLine . '</span>');
                        } else {
                            array_push($codeLines, $i . '. ' . $codeLine);
                        }

                        $i++;
                    }

                    wdd(
                        '<div style="text-align: center; padding: 5px; color: black; border: solid 1px black; background: #f2f2f2;">' . $typeError . '</div>',
                        '<div style="padding: 5px; color: red; border: solid 1px red; background: #f2f2f2;">' . $message . '</div>',
                        '<div style="padding: 5px; color: navy; border: solid 1px navy; background: #f2f2f2;">' . $file . ' [<em>line: <u>' . $line . '</u></em>]</div>',
                        '<div style="font-family: Consolas; font-weight: 400; padding: 5px; color: green; border: solid 1px green; background: #f2f2f2;">' . implode("\n", $codeLines) . '</div>',
                        '<div style="text-align: center; padding: 5px; color: black; border: solid 1px black; background: #f2f2f2;">BACKTRACE</div>',
                        '<div style="padding: 5px; color: purple; border: solid 1px purple; background: #f2f2f2;">' . displayCodeLines() . '</div>'
                    );
                }
            }
        });
    }
