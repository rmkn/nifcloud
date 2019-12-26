<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_SshKey extends Nifcloud_API
{
    protected $name = null;

    public function __construct($accessKey, $secretKey, $zone, $name)
    {
        parent::__construct($accessKey, $secretKey, $zone);
        $this->name = $name;
        $this->getInfo();
    }

    public function exists()
    {
        return isset($this->res['keySet']['item']['keyName']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['keySet']['item']['keyName'])
            ? 'available'
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'    => 'DescribeKeyPairs',
            'KeyName.1' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($params)
    {
        $url   = '';
        $param = array(
            'Action'  => 'CreateKeyPair',
            'KeyName' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    } 

    public function getKey()
    {
        return isset($this->res['keyMaterial'])
            ? base64_decode($this->res['keyMaterial'])
            : null;
    }

    public function saveKey($filename)
    {
        $key = $this->getKey();
        return $key !== null
            ? file_put_contents($filename, $key)
            : null;
    }
}
