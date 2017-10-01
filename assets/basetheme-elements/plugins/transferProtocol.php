<?php
/**
 * Плагин для передресации по нужному протоколу http или https исходя из настроек
 */
 
$eventName = $modx->event->name;

switch($eventName) {
    case 'OnHandleRequest':
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $isSecure = true;
        }

        $REQUEST_PROTOCOL = $isSecure ? 'https' : 'http';
        $SYSTEM_PROTOCOL = $modx->getOption('server_protocol');
        if($SYSTEM_PROTOCOL != $REQUEST_PROTOCOL) {
            $url =  str_replace(array("http://","https://"), "", $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            if($SYSTEM_PROTOCOL == "https")
                $modx->sendRedirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
            else
                $modx->sendRedirect('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
            header("Location:$redirect");
        }
        break;
}
