<?php
// vim: set et ts=4 sw=4 sts=4:

require_once 'Nifcloud/RdbAPI.php';

class Nifcloud_RdbFirewall extends Nifcloud_RdbAPI
{
    public function exists()
    {
        return isset($this->res['DescribeDBSecurityGroupsResult']['DBSecurityGroups']['DBSecurityGroup']['DBSecurityGroupName']);
    }

    public function getInfo()
    {
        $url   = '';
        $param = array(
            'Action'                     => 'DescribeDBSecurityGroups',
            'DBSecurityGroupName'        => $this->name,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    public function create($desc = '')
    {
        $url   = '';
        $param = array(
            'Action'                     => 'CreateDBSecurityGroup',
            'DBSecurityGroupDescription' => $desc,
            'DBSecurityGroupName'        => $this->name,
            'NiftyAvailabilityZone'      => $this->zone,
        );

        $this->call($url, $param);
        return !$this->isError();
    }

    protected function isProcessing()
    {
        $res = $this->getInfo($this->name);

        $cnt = 0;
        if (isset($this->res['DescribeDBSecurityGroupsResult']['DBSecurityGroups']['DBSecurityGroup']['EC2SecurityGroups'])) {
            foreach ($this->res['DescribeDBSecurityGroupsResult']['DBSecurityGroups']['DBSecurityGroup']['EC2SecurityGroups'] as $r) {
                if (isset($r['Status'])) {
                    switch ($r['Status']) {
                    case 'authorizing':
                    case 'revoking':
                        $cnt++;
                    }
                }
            }
        }
        if (isset($this->res['DescribeDBSecurityGroupsResult']['DBSecurityGroups']['DBSecurityGroup']['IPRanges'])) {
            foreach ($this->res['DescribeDBSecurityGroupsResult']['DBSecurityGroups']['DBSecurityGroup']['IPRanges'] as $r) {
                if (isset($r['Status'])) {
                    switch ($r['Status']) {
                    case 'authorizing':
                    case 'revoking':
                        $cnt++;
                    }
                }
            }
        }
        return $cnt > 0;
    }

    public function setRules($rules, $ignoreDuplicate = true)
    {
        $url       = '';
        $paramBase = array(
            'Action'                     => 'AuthorizeDBSecurityGroupIngress',
            'DBSecurityGroupName'        => $this->name,
        );
        $rc = true;
        foreach ($rules as $rule) {
            if (isset($rule['CIDRIP'])) {
                $param = $paramBase + array('CIDRIP' => $rule['CIDRIP']);
            } elseif (isset($rule['EC2SecurityGroupName'])) {
                $param = $paramBase + array('EC2SecurityGroupName' => $rule['EC2SecurityGroupName']);
            } else {
                continue;
            }
            $this->call($url, $param);
            $rc = !$this->isError();
            if ($this->isError()) {
                if ($ignoreDuplicate && $this->getLastError() === 'Client.InvalidParameterDuplicate.CidrIp.or.SecurityGroupName') {
                    $rc = true;
                    continue;
                } else {
                    return false;
                }
            }
            do {
                echo '.';
                sleep(1);
            } while ($this->isProcessing());
        }
        return $rc;
    }

}
