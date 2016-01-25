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

    defined('SITE_NAME')        || define('SITE_NAME', getenv('SITE_NAME')              ? getenv('SITE_NAME')       : 'site');
    defined('APPLICATION_ENV')  || define('APPLICATION_ENV', getenv('APPLICATION_ENV')  ? getenv('APPLICATION_ENV') : 'development');

    define('STORAGE_PATH', session_save_path());
    define('APPLICATION_PATH', realpath(__DIR__));
    define('CLI', false);
    define('VENDORS_PATH', realpath(__DIR__ . '/vendor'));
    define('VENDOR_PATH', realpath(__DIR__ . '/vendor'));
    define('CACHE_PATH', session_save_path() . '/cache');

    require_once VENDOR_PATH    . '/autoload.php';
    require_once VENDOR_PATH    . '/schpill/standalone/init.php';
    require_once __DIR__        . '/helpers.php';

    class Bootstrap
    {
        public static function init($cli)
        {
            register_shutdown_function(['\\Thin\Bootstrap', 'finish']);

            Timer::start();

            lib('app');

            forever();

            $storage_dir = STORAGE_PATH;

            if (!is_writable(STORAGE_PATH)) {
                die('Please give 0777 right to ' . STORAGE_PATH);
            }

            if (!is_dir(CACHE_PATH)) {
                File::mkdir(CACHE_PATH);
            }

            $dir = __DIR__;

            Config::set('app.module.dir',           __DIR__);
            Config::set('mvc.dir',                  __DIR__);
            Config::set('app.module.dirstorage',    $dir . DS . 'storage');
            Config::set('app.module.assets',        $dir . DS . 'assets');
            Config::set('app.module.config',        $dir . DS . 'config');
            Config::set('dir.raw.store',            $storage_dir . DS . 'db');
            Config::set('dir.ardb.store',           $storage_dir . DS . 'db');
            Config::set('dir.ephemere',             $storage_dir . DS . 'ephemere');
            Config::set('dir.flight.store',         $storage_dir . DS . 'flight');
            Config::set('dir.flat.store',           $storage_dir . DS . 'flat');
            Config::set('dir.cache.store',          $storage_dir . DS . 'cache');
            Config::set('dir.nosql.store',          $storage_dir . DS . 'nosql');
            Config::set('dir.module.logs',          $storage_dir . DS . 'logs');

            path('module',  $dir);
            path('store',   $storage_dir);
            path('config',  Config::get('app.module.config'));
            path('cache',   CACHE_PATH);

            loaderProject('entity');
            loaderProject('system');

            Alias::facade('DB',     'EntityProject', 'Thin');
            Alias::facade('System', 'SystemProject', 'Thin');

            System::Db()->firstOrCreate(['name' => SITE_NAME]);

            require_once path('config') . DS . 'application.php';

            if (!$cli) {
                if (fnmatch('*/mytests', $_SERVER['REQUEST_URI'])) {
                    self::tests();
                } else {
                    self::router();
                }
            }
        }

        private static function tests()
        {
            $books = Db::Book();
            $authors = Db::Author();

            $cb = $authors->firstOrCreate([
                'firstname' => 'Charles',
                'name'      => 'Baudelaire',
                'century'   => '19',
            ]);

            $book = $books->firstOrCreate(['name' => 'Fleurs du Mal', 'year' => 1865, 'author_id' => $cb->id]);
            $book2 = $books->firstOrCreate(['name' => 'livre 2', 'year' => 1891, 'author_id' => $cb->id]);

            wdd($cb->books());
        }

        public static function assetAged($asset)
        {
            $file = __DIR__ . $asset;

            if (file_exists($file)) {
                return sha1(filemtime($file) . $file);
            }

            return 1;
        }

        public static function router()
        {
            libProject('router');
        }

        public static function finish()
        {
            $time = Timer::get();
        }
    }

    Bootstrap::init(isset($cli));
