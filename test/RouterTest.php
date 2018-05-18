<?php

namespace EasyRouter;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testStatic()
    {
        $r = easyRouter();

        $r->delete('/delete', 'delete');
        $r->get('/get', 'get');
        $r->head('/head', 'head');
        $r->patch('/patch', 'patch');
        $r->post('/post', 'post');
        $r->put('/put', 'put');

        $r->addGroup('/admin', function (Router $r) {
            $r->get('/get', 'get');
            $r->post('/post', 'post');
        });

//        var_export($r->getRouteData());
        $expected = array (
          0 =>
          array (
            'DELETE' =>
            array (
              '/delete' => 'delete',
            ),
            'GET' =>
            array (
              '/get' => 'get',
              '/admin/get' => 'get',
            ),
            'HEAD' =>
            array (
              '/head' => 'head',
            ),
            'PATCH' =>
            array (
              '/patch' => 'patch',
            ),
            'POST' =>
            array (
              '/post' => 'post',
              '/admin/post' => 'post',
            ),
            'PUT' =>
            array (
              '/put' => 'put',
            ),
          ),
          1 =>
          array (
          ),
          2 =>
          array (
          ),
        );

        $this->assertSame($expected, $r->getRouteData());
    }
}