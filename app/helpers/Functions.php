<?php

use Monolog\Logger;
use Monolog\handler\StreamHandler;

function check_session()
{
    return isset($_SESSION['user']);
}
function get_user_active()
{
   return $_SESSION['user']->name;
}

function printData($data, $die = true)
{
    echo "<pre>";
    if (is_object($data) || is_array($data)) {
        print_r($data);
     echo "</pre>";    
    } else {
        if (empty($data)) {
            echo 'vazio';
        }else{
        echo $data;}
    }
    if ($die) {
         echo "<pre>";
        die("<br>FIM<br>");    
    }
}

function logger($message = '', $level = 'info')
{
    $log = new Logger('app_logs');
    $log->pushHandler(new StreamHandler(LOGS_PATH));

    switch ($level) {
        case 'info':
            $log->info($message);
            break;
        case 'notice':
            break;
        case 'warning':
            $log->info($message);
            break;
        case 'error':
            $log->info($message);
            break;
        case 'info':
            $log->info($message);
            break;
        case 'info':
            $log->info($message);
            break;
        case 'info':
            $log->info($message);
            break;
        default:
        $log->info($message);
            break;
    }
}
function aes_decrypt($value){
    if(strlen($value)%2!=0){
        return false;
    }
    return openssl_decrypt(hex2bin($value),'aes-256-cbc',OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV);

}
function aes_encrypt($value){
    return bin2hex(openssl_encrypt($value,'aes-256-cbc',OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV));
}
