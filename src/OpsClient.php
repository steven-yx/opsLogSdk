<?php
namespace OpsLog;

class OpsClient
{

    /**
     * @var null
     */
    private $host=null;

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

    /**
     * @var null
     */
    private $biz=null;

    /**
     * @var null
     */
    private $biz_id=null;

    /**
     * @var array|false|mixed|string|null
     */
    private $localIp=null;

    /**
     * @var null
     */
    private $errMsg=null;

    /**
     * @var null
     */
    private $timeout=null;

    //保存
    const PATH="/save";
    //搜索
    const SearchPath="/search";

    /**
     * OpsClient constructor.
     * @param string $host
     * @param int $appId
     * @param int $timeout
     */
    public function __construct(string $host,int $appId,int $timeout=200){

        $this->host=$host;
        $this->appId=$appId;
        $this->timeout=$timeout;

        $this->localIp=$this->get_server_ip();
    }

    /**
     * @param array $search_data
     * @param $data
     * @param $msg
     * @return bool
     */
    public function search(array $search_data,&$data,&$msg){
        $search=[
            'app_id'=>(int)($search_data['app_id']??0),
            'biz'=>(string)($search_data['biz']??''),
            'biz_id'=>(int)($search_data['biz_id']??0),
            'keywords'=>(string)($search_data['keywords']??''),
            'page'=>(int)($search_data['page']??1),
            'page_size'=>(int)($search_data['page_size']??20),
            'start_time'=>(string)($search_data['start_time']??''),
            'end_time'=>(string)($search_data['end_time']??'')
        ];
        try{
            if (!preg_match('/(http:\/\/)|(https:\/\/)/i', $this->host)){
                $this->host="http://".$this->host;
            }
            $url=trim($this->host).self::SearchPath;
            $res=$this->httpGet($url,$search,$this->timeout);
            if (!$res || !isset($res['code'])){
                throw new \Exception("请求接口超时");
            }
            if ($res['code']!=0){
                $msg=$res['message']??'';
                return false;
            }
            $data=$res['data']??[];
            return true;
        }catch (\Exception $e) {
            $msg = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string $biz
     * @param int $biz_id
     * @param string $desc
     * @param array $data
     * @return bool
     */
    public function save(string $biz='',int $biz_id=0,string $desc='',array $data=[]){

        try{

            $request_data=[
                'app_id'=>$this->appId,
                'biz'=>(string)$biz,
                'biz_id'=>(int)$biz_id,
                'desc'=>(string)$desc,
                'operator'=>$this->operator?json_encode($this->operator,JSON_UNESCAPED_UNICODE):'',
                'data'=>$data?json_encode($data,JSON_UNESCAPED_UNICODE):'',
            ];
            $url=$this->host.self::PATH;
            $res=$this->httpPost($url,$request_data,$this->timeout);

            if(!isset($res['code'])||$res['code']!=0){
                throw new \Exception($res['message']??"请求超时");
            }
            return true;
        }catch (\Exception $e){
            $this->errMsg=$e->getMessage();
            return false;
        }
    }

    /**
     * @param array $operator
     * @return $this
     */
    public function initOperator(array $operator=[]){
        $this->operator['uid']=(string)($operator['uid']??'');
        $this->operator['name']=(string)($operator['name']??'');
        $this->operator['mobile']=(string)($operator['mobile']??'');
        $this->operator['ip']=(string)($operator['ip']??$this->localIp);
        return $this;
    }

    /**
     * @return null
     */
    public function getErrMsg(){
        return $this->errMsg;
    }

    /**
     * diff struct
     * @param array $before
     * @param array $after
     * @return array
     */
    public function DiffStruct(array $before,array $after){

        $diff=[];
        if (empty($after)){
            return $before;
        }

        foreach ($after as $k=>$v){
            if (!isset($after[$k])){
                $diff[$k]=$v;
                continue;
            }
            if (isset($before[$k]) && $v!==$before[$k]){
                $diff[$k]=$v;
            }
        }
        return $diff;
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

    /**
     * @param $url
     * @param $params
     * @param int $timeout_ms
     * @return mixed
     */
    private function httpGet($url,$params,$timeout_ms=1000 {
        $ch = curl_init();
        $data_string=json_encode($params);
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt ( $ch, CURLOPT_TIMEOUT_MS, $timeout_ms);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt ( $ch, CURLOPT_POSTFIELDS,$data_string);
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        return json_decode($result,true);
    }
}
