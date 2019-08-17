<?php
namespace OpsLog;

class OpsClient
{

    /**
     * @var null
     */
    private $url=null;

    /**
     * @var null
     */
    private $appId=null;

    /**
     * @var null
     */
    private $operator=[
        "uid"=>"",
        "name"=>"",
        "mobile"=>"",
        "ip"=>""
    ];

    const PATH="/save";

    /**
     * opsLog constructor.
     * @param $url
     * @param $appId
     */
    public function __construct($url,$appId)
    {
        $this->url=$url;
        $this->appId=$appId;
    }

    /**
     * @param $biz
     * @param $biz_id
     * @param $action
     * @param $data
     * @return bool|mixed
     */
    public function save($biz,$biz_id,$action,$data){

        $request_data=[
            'biz'=>(string)$biz,
            'biz_id'=>int($biz_id),
            'action'=>$action,
            'desc'=>(string)($data['desc']??''),
            'before'=>json_encode(($data['before']??[])),
            'after'=>json_encode($data['after']??[]),
            'extra'=>json_encode($data['extra']??[])
        ];
        return $this->httpPost($this->url,$request_data);
    }

    /**
     * @param array $operator
     * @return $this
     */
    public function setOperator($operator=[]){
        $this->operator['uid']=$operator['uid']??'';
        $this->operator['name']=$operator['name']??'';
        $this->operator['mobile']=$operator['name']??'';
        $this->operator['ip']=$operator['ip']??$this->get_server_ip();
        return $this;
    }

    /**
     * @return array|false|mixed|string
     */
    private function get_server_ip()
    {
        $server_ip='';
        if (isset($_SERVER['SERVER_NAME'])) {
            return gethostbyname($_SERVER['SERVER_NAME']);
        } else {
            if (isset($_SERVER)) {
                if (isset($_SERVER['SERVER_ADDR'])) {
                    $server_ip = $_SERVER['SERVER_ADDR'];
                } elseif (isset($_SERVER['LOCAL_ADDR'])) {
                    $server_ip = $_SERVER['LOCAL_ADDR'];
                }
            } else {
                $server_ip = getenv('SERVER_ADDR');
            }
            return $server_ip ? $server_ip : '';
        }
    }


    /**
     * @param $url
     * @param $request
     * @param int $timeout
     * @return bool|mixed
     */
    private function httpPost($url, $request, $timeout = 200){

        $data_string=json_encode($request);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT_MS,$timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 这里设置代理，如果有的话
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));

        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return json_decode($data,true);
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }

}
