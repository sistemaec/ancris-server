<?php

namespace Pointerp\Library;

use Phalcon\Events\Event;
use Phalcon\Di\Injectable;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\Dispatcher;

/**
 * Class Prevuelo
 * @package Pointerp\Listener
 * @property Request $request
 * @property Response $response
 */
class Prevuelo extends Injectable
{
    /**
     * @param Event $event
     * @param Dispatcher $dispatcher
     */
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher) {
        $di = $dispatcher->getDI();
        $request = $di->get('request');
        $response = $di->get('response');
        // $this->isCorsRequest($request)
        /*if (!empty($request->getHeader('Origin'))) {
            $response
                ->setHeader('Access-Control-Allow-Origin', '*') // $this->getOrigin($request)
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE')
                ->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization Accept-Language, X-Access-Token, X-Client-Id, X-Secret-Id, X-GR-Token') 
                ->setHeader('Access-Control-Expose-Headers', 'X-Access-Token, X-Refresh-Token,X-Access-Token-Expire, X-Pagination-Current-Page, X-Pagination-Page-Count,X-Pagination-Per-Page, X-Pagination-Total-Count, X-Payload')
                ->setHeader('Access-Control-Allow-Credentials', 'true');
        }*/

        $response
            ->setHeader('Access-Control-Allow-Origin', '*') // $this->getOrigin($request)
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE')
            ->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization') 
            ->setHeader('Access-Control-Allow-Credentials', 'true');
    
        // $this->isPreflightRequest($request)
        if ($request->getMethod() === "OPTIONS") {
            $response->setStatusCode(200, 'OK');
            $response->setContent(json_encode("Prevuelo efectivo"));
            $response->send();
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isCorsRequest(Request $request)
    {
        return !empty($request->getHeader('Origin')) /*&& !$this->isSameHost($request)*/;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isPreflightRequest(Request $request)
    {
        return $this->isCorsRequest($request)
            && $request->getMethod() == 'OPTIONS' /*
            && !empty($request->getHeader('Access-Control-Request-Method'))*/;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isSameHost(Request $request)
    {
        return $request->getHeader('Origin') === $this->getSchemeAndHttpHost($request);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getSchemeAndHttpHost(Request $request)
    {
        return $request->getScheme() . '://' . $request->getHttpHost();
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getOrigin(Request $request)
    {
        return $request->getHeader('Origin') ? $request->getHeader('Origin') : '*';
    }
}