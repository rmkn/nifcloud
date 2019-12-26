<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_Disk extends Nifcloud_API
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
        return isset($this->res['volumeSet']['item']['volumeId']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['volumeSet']['item']['status'])
            ? $this->res['volumeSet']['item']['status']
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'       => 'DescribeVolumes',
            'InstanceId.1' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($params)
    {
        $url   = '';
        $param = array(
            'Action'   => 'CreateVolume',
            'VolumeId' => $this->name,
        ) + $params;

        $this->call($url, $param, null, true);
        return !$this->isError();
    } 

    public function attach($params)
    {
        $url   = '';
        $param = array(
            'Action'     => 'AttachVolume',
            'VolumeId' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    } 

}
