<?php namespace App\libs;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\Regions\Endpoint;
use Aliyun\Core\Regions\EndpointConfig;
use Aliyun\Core\Regions\EndpointProvider;
use Aliyun\DM\Request\SingleSendMailRequest;

class AliApi{
    /**
     * 访问信息
     * @var array
     */
    private $config;
    /**
     * @var \Aliyun\Core\Regions\Endpoint
     */
    private $endpoint;
    /**
     * @var \Aliyun\Core\Profile\DefaultProfile
     */
    private $profile;
    /**
     * @var
     */
    private $user;
    /**
     * @var mixed|null|\SimpleXMLElement
     */
    private $title;
    /**
     * @var mixed|null|\SimpleXMLElement
     */
    private $body;
    /**
     * @var mixed|null|\SimpleXMLElement
     */

    public function __construct()
    {
        $this->config = $this->setConfig();
        $this->endpoint = $this->setEndpoint();
        $this->profile = $this->setProfile();
    }

    /**
     *
     */
    private function setConfig()
    {
        $config = [
            'AccessKeyId'=>ApiConf::OSS_ACCESS_KEY,
            'AccessKeySecret'=>ApiConf::OSS_SECRET_KEY,
        ];
        return $config;
    }

    /**
     *
     */
    private function setEndpoint()
    {
        $endpoint = new Endpoint('cn-hangzhou', EndpointConfig::getRegionIds(), EndpointConfig::getProductDomains());
        EndpointProvider::setEndpoints([ $endpoint ]);
        return $endpoint;
    }

    /**
     *
     */
    private function setProfile()
    {
        $profile = DefaultProfile::getProfile('cn-hangzhou', $this->config['AccessKeyId'], $this->config['AccessKeySecret']);
        return $profile;
    }

    /**
     * @return mixed|null|\SimpleXMLElement
     */
    public function send()
    {
        $request = new SingleSendMailRequest();
        $request->setAccountName('info@novaby.com');
        $request->setFromAlias('Novaby');
        $request->setAddressType(1);
        $request->setReplyToAddress('true');
        $request->setToAddress($this->user);
        $request->setSubject($this->title);
        $request->setHtmlBody($this->body);

        $client  = new DefaultAcsClient($this->profile);
        $response = $client->getAcsResponse($request);
        return $response;
    }

    /**
     * @param $user
     */
    public function setUser($user)
    {
        $this->user =$user;
    }
    /**
     * @param $title
     */
    public function setTitle($title)
    {
        $this->title =$title;
    }
    /**
     * @param $body
     */
    public function setBody($body)
    {
        $this->body =$body;
    }

}