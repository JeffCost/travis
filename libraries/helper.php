<?php

/**
 * Simulates a request to the router re-setting
 * 
 */
public static function http_request($method, $route, $post_data = array())
{
    $request = \Router::route($method, $route);
    
    $post_data[\Session::csrf_token] = \Session::token();

    \Request::setMethod($method);
    
    \Request::foundation()->request->add($post_data);

    return $request->call();
}