<?php
class Config
{
    public function getConfig()
    {
        $config = array(
            // your api key
            'consumer_key'          => '',
            'consumer_secret'       => '',
            'access_token'          => '',
            'access_token_secret'   => '',
        );
        return $config;
    }
}
