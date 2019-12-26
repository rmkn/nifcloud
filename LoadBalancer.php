<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_LoadBalancer extends Nifcloud_API
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
        return isset($this->res['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']['LoadBalancerName']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']['State'])
            ? $this->res['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member']['State']
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'                     => 'DescribeLoadBalancers',
            'LoadBalancerNames.member.1' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($params)
    {
        $url   = '';
        $param = array(
            'Action'           => 'CreateLoadBalancer',
            'LoadBalancerName' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    } 

    public function regist($params)
    {
        $url   = '';
        $param = array(
            'Action'           => 'RegisterInstancesWithLoadBalancer',
            'LoadBalancerName' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    }

    public function setFilter($params)
    {
        $url   = '';
        $param = array(
            'Action'           => 'SetFilterForLoadBalancer',
            'LoadBalancerName' => $this->name,
        ) + $params;

        $this->call($url, $param);
        return !$this->isError();
    }
}

