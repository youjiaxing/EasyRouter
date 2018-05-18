<?php
namespace EasyRouter;

if (!function_exists('EasyRouter\easyRouter')) {

    /**
     * @param array $sugars
     * @return Router
     */
    function easyRouter($sugars = [])
    {
        $r = new \EasyRouter\Router(
            new \EasyRouter\Parser\EasyRouteParser($sugars),
            new \EasyRouter\DataGenerator\EasyGenerator(),
            new \EasyRouter\Dispatcher\EasyDispatcher()
        );

        /*
        $r->get('/', 'home@index');
        $r->get('/article/{id:\d+}', 'article@show');
        $r->post('/comment', 'comment@store');

        $r->addGroup('/admin', function (\EasyRouter\Router $r) {
            $r->get('', 'admin/home@index');
            $r->get('/article/list', 'admin/article@index');
            $r->get('/article/create', 'admin/article@create');
            $r->post('/article/create', 'admin/article@store');
            $r->get('/article/{id:\d+}/edit', 'admin/article@edit');
            $r->post('/article/{id:\d+}/update', 'admin/article@update');
            $r->post('/article/{id:\d+}/delete', 'admin/article@delete');
        });

        $r->error('error@code404');
        */

        return $r;
    }
}