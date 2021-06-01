<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_Server extends Nifcloud_API
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
        return isset($this->res['reservationSet']['item']['instancesSet']['item']['instanceId']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['reservationSet']['item']['instancesSet']['item']['instanceState']['name'])
            ? $this->res['reservationSet']['item']['instancesSet']['item']['instanceState']['name']
            : null;
    }

    public function getType()
    {
        return isset($this->res['reservationSet']['item']['instancesSet']['item']['instanceType'])
            ? $this->res['reservationSet']['item']['instancesSet']['item']['instanceType']
            : null;
    }

    public function getAccountingType()
    {
        return isset($this->res['reservationSet']['item']['instancesSet']['item']['accountingType'])
            ? $this->res['reservationSet']['item']['instancesSet']['item']['accountingType']
            : null;
    }

    public function getNextAccountingType()
    {
        return isset($this->res['reservationSet']['item']['instancesSet']['item']['nextMonthAccountingType'])
            ? $this->res['reservationSet']['item']['instancesSet']['item']['nextMonthAccountingType']
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'       => 'DescribeInstances',
            'InstanceId.1' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($params)
    {
        $url   = '';
        $param = array(
            'Action'     => 'RunInstances',
            'InstanceId' => $this->name,
        ) + $params;

        $this->call($url, $param, null, true);
        return !$this->isError();
    } 

    public function stop($params)
    {
        $url   = '';
        $param = array(
            'Action'     => 'StopInstances',
            'InstanceId.1' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    } 

    public function delete($params)
    {
        $url   = '';
        $param = array(
            'Action'     => 'TerminateInstances',
            'InstanceId.1' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    } 

    public function change($params)
    {
        $url   = '';
        $param = array(
            'Action'     => 'ModifyInstanceAttribute',
            'InstanceId' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    }

}
