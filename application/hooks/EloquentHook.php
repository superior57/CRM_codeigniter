<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;

class EloquentHook
{
    public function bootEloquent()
    {
        $this->configurePagination();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => defined('ELOQUENT_DRIVER') ? ELOQUENT_DRIVER : 'mysql',
            'host'      => APP_DB_HOSTNAME,
            'database'  => APP_DB_NAME,
            'username'  => APP_DB_USERNAME,
            'password'  => APP_DB_PASSWORD,
            'charset'   => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
            'collation' => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
            'prefix'    => db_prefix(),
        ]);

        $events = new Dispatcher(new Container);

        if (ENVIRONMENT != 'production') {
            $events->listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
                $bindings = $query->bindings;
                // Format binding data for sql insertion
                foreach ($bindings as $i => $binding) {
                    if ($binding instanceof \DateTime) {
                        $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } elseif (is_string($binding)) {
                        $bindings[$i] = "'$binding'";
                    }
                }

                // Insert bindings into query
                $q = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
                $q = vsprintf($q, $bindings);
                // Add it into CodeIgniter
                $db = & get_instance()->db;

                $db->query_times[] = $query->time;
                $db->queries[] = $q;
            });
        }

        $capsule->setEventDispatcher($events);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();
    }

    private function configurePagination()
    {
        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = get_instance()->input->get_post($pageName);

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return $page;
            }

            return 1;
        });
    }
}
