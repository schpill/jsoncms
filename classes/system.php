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

    class SystemProject
    {
        public static function __callStatic($method, $args)
        {
            $table = Inflector::uncamelize($method);

            $database = 'system';

            if (empty($args)) {
                return core('fast')->instanciate($database, $table);
            } elseif (count($args) == 1) {
                $id = current($args);

                if (is_numeric($id)) {
                    return core('fast')->instanciate($database, $table)->find((int) $id);
                }
            }
        }
    }
