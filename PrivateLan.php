<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_PrivateLan extends Nifcloud_API
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
        return isset($this->res['privateLanSet']['item']['privateLanName']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['privateLanSet']['item']['state'])
            ? $this->res['privateLanSet']['item']['state']
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'           => 'NiftyDescribePrivateLans',
            'PrivateLanName.1' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($params)
    {
        $url   = '';
        $param = array(
            'Action'         => 'NiftyCreatePrivateLan',
            'PrivateLanName' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    } 

}
