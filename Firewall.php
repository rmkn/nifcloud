<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/API.php';

class Nifcloud_Firewall extends Nifcloud_API
{
    protected $name = null;

    public function __construct($accessKey, $secretKey, $zone, $name)
    {
        parent::__construct($accessKey, $secretKey, $zone);
        $this->name = $name;
        $this->getInfo();
    }

    public function getName()
    {
        return $this->name;
    }

    public function exists()
    {
        return isset($this->res['securityGroupInfo']['item']['groupName']);
    }

    public function getStatus($reload = false)
    {
        if ($reload) {
            $this->getInfo();
        }
        return isset($this->res['securityGroupInfo']['item']['groupStatus'])
            ? $this->res['securityGroupInfo']['item']['groupStatus']
            : null;
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'      => 'DescribeSecurityGroups',
            'GroupName.1' => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create()
    {
        $url   = '';
        $param = array(
            'Action'    => 'CreateSecurityGroup',
            'GroupName' => $this->name,
            'Placement.AvailabilityZone' => $this->zone,
        );

        $this->call($url, $param);
        return !$this->isError();
    } 

    private function ruleExists($rule)
    {
        if (!isset($this->res['securityGroupInfo']['item']['ipPermissions']['item'])) {
            return false;
        }
        foreach ($this->res['securityGroupInfo']['item']['ipPermissions']['item'] as $item) {
            if ($item['ipProtocol'] === $rule['IpProtocol']
                && $item['inOut'] === $rule['InOut']
                && $item['description'] === $rule['Description']
            ) {
                if (isset($item['fromPort']) && isset($item['toPort'])) {
                    if ($item['fromPort'] === strval($rule['FromPort'])
                        && $item['toPort'] === strval($rule['ToPort'])
                    ) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
                //&& $item['ipRanges'] === $rule['IpRanges']
                //$diff = array_diff_assoc($item['ipRanges']['item'] as $) {
                //&& $item[    array('CidrIp' => '172.0.0.0/8'),
            }
        }
        return false;
    }

    public function setRules($rules)
    {
        $buf = array();
        foreach ($rules as $rule) {
            if (!$this->ruleExists($rule)) {
                $buf[] = $rule;
            }
        }
        if (empty($buf)) {
            return true;
        }
        $url   = '';
        $param = array(
            'Action'        => 'AuthorizeSecurityGroupIngress',
            'GroupName'     => $this->name,
            'IpPermissions' => $buf,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function getRes()
    {
        return $this->res;
    }

    public function getLog($date = null, $start = null, $end =null)
    {
        $url   = '';
        $param = array(
            'Action'      => 'DescribeSecurityActivities',
            'GroupName' => $this->name,
        );
        if ($date !== null) {
            $param['ActivityDate'] = $date;
        }
        if ($start !== null && $end !== null) {
            $param['Range.All'] = 'false';
            $param['Range.StartNumber'] = $start;
            $param['Range.EndNumber'] = $end;
        }

        $this->call($url, $param);
        return !$this->isError();
    }

}
