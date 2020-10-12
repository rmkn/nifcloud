<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_RdbAPI extends Nifcloud_API
{
    protected $name = null;

    public function __construct($accessKey, $secretKey, $zone, $name)
    {
        parent::__construct($accessKey, $secretKey, $zone);
        $this->name = $name;
        //$this->setVersion(4);
        $this->getInfo();
    }

    protected function isError()
    {
        return $this->res === null || isset($this->res['Error']);
    }

    public function getLastError()
    {
        return isset($this->res['Error']['Code']) ? $this->res['Error']['Code'] : 'unknown code';
    }

    public function getLastErrorMsg()
    {
        return isset($this->res['Error']['Message']) ? $this->res['Error']['Message'] : 'unknown error';
    }

    protected function getEndpoint()
    {
        $endpoints = array(
            'east-1' => 'aws://jp-east-1.rdb.api.nifcloud.com/',
            'east-2' => 'aws://jp-east-2.rdb.api.nifcloud.com/',
            'east-3' => 'aws://jp-east-3.rdb.api.nifcloud.com/',
            'east-4' => 'aws://jp-east-4.rdb.api.nifcloud.com/',
            'west-1' => 'aws://jp-west-1.rdb.api.nifcloud.com/',
        );
        return isset($endpoints[$this->region]) ? $endpoints[$this->region] : null;
    }

    public function getRes()
    {
        return $this->res;
    }

}
