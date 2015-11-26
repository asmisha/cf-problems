<?php

const CacheDir = '/tmp';

function get($method, $params = null, $auth = null){
    $cacheKey = $method;
    if($params) {
        $cacheKey .= json_encode($params);
    }
    if($auth) {
        $cacheKey .= $auth['key'];
    }

    $cacheFile = sprintf('%s/%s.json', CacheDir, $cacheKey);

    if(file_exists($cacheFile) && filemtime($cacheFile) > time() - 24*60*60){
        $data = file_get_contents($cacheFile);
    }else{
        if($auth){
            if(!$params){
                $params = array();
            }

            $params['apiKey'] = $auth['key'];
            $params['time'] = time();
            $rand = mt_rand(1e5, 1e6-1);
            ksort($params);
            $params['apiSig'] = $rand.'/'.hash('sha512', $rand.'/'.$method.'?'.http_build_query($params).'#'.$auth['secret']);
        }

        $url = 'http://codeforces.com/api/'.$method.($params ? '?'.http_build_query($params) : '');
        $data = file_get_contents($url);
        file_put_contents($cacheFile, $data);
    }

    return $data;
}