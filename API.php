<?php
// vim: set et ts=4 sw=4 sts=4:

// curl -o awsStreamWrapper.php https://raw.githubusercontent.com/rmkn/awsStreamWrapper/master/awsStreamWrapper.php
require_once 'awsStreamWrapper.php';
stream_wrapper_register("aws", "AwsStreamWrapper");

class Nifcloud_API
{
    protected $accessKey = null;
    protected $secretKey = null;
    protected $region    = null;
    protected $zone      = null;
    protected $version   = 2;
    protected $res       = null;

    public function __construct($accessKey, $secretKey, $zone)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region    = $this->getRegion($zone);
        $this->zone      = $zone;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    protected function isError()
    {
        return $this->res === null || isset($this->res['Errors']['Error']);
    }

    public function getLastError()
    {
        return isset($this->res['Errors']['Error']['Code']) ? $this->res['Errors']['Error']['Code'] : 'unknown code';
    }

    public function getLastErrorMsg()
    {
        return isset($this->res['Errors']['Error']['Message']) ? $this->res['Errors']['Error']['Message'] : 'unknown error';
    }

    protected function array2param($d, $prefix = '')
    {
        $res = '';
        foreach ($d as $k => $v) {
            if (is_array($v)) {
                $idx = 0;
                foreach ($v as $kk => $vv) {
                    $idx++;
                    $res .= $this->array2param($vv, "{$prefix}{$k}.{$idx}.");
                }
            } else {
                $res .= sprintf('&%s%s=%s', $prefix, $k, rawurlencode($v));
            }
        }
        return $res;
    }

    protected function getRegion($zone)
    {
        return preg_match('/^(?<region>(?:east|west|us-east)-\d)/', $zone, $m) ? $m['region'] : null;
    }

    protected function getEndpoint()
    {
        $endpoints = array(
            'east-1' => 'aws://jp-east-1.computing.api.nifcloud.com/api/',
            'east-2' => 'aws://jp-east-2.computing.api.nifcloud.com/api/',
            'east-3' => 'aws://jp-east-3.computing.api.nifcloud.com/api/',
            'east-4' => 'aws://jp-east-4.computing.api.nifcloud.com/api/',
            'west-1' => 'aws://jp-west-1.computing.api.nifcloud.com/api/',
        );
        return isset($endpoints[$this->region]) ? $endpoints[$this->region] : null;
    }

    protected function call($url, $param = null, $option = array(), $post = false)
    {
        $opts = array(
            'aws' => array(
                'accesskey' => $this->accessKey,
                'secretkey' => $this->secretKey,
                'version'   => $this->version,
                'region'    => $this->zone,
                'format'    => 'json',
            ),
            'http' => array(
                'timeout' => 30,
            ),
        );
        if (is_array($option)) {
            foreach ($option as $k => $v) {
                if (isset($opts[$k])) {
                    if (is_array($v)) {
                        $opts[$k] = array_merge($opts[$k], $v);
                    }
                } else {
                    $opts[$k] = $v;
                }
            }
        }
        $res      = null;
        $endpoint = $this->getEndpoint();
        if ($endpoint !== null) {
            $prm = ltrim(is_array($param) ? $this->array2param($param) : $param, '&');
            if ($post) {
                $opts['http']['method']  = 'POST';
                $opts['http']['content'] = $prm;
                $prm = '';
            }
            if (!empty($prm)) {
                $prm = "?{$prm}";        
            }
            $res = file_get_contents("{$endpoint}{$url}{$prm}", false, stream_context_create($opts));
            //var_dump($endpoint,$url,$prm,$opts,$res);
            //var_dump(json_decode($res, true));
        }
        $this->res = json_decode($res, true);
        return $this->res;
    }
}
