
<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/3/21
 * Time: 09:03
 * 微信公共类 暂时此平台需要用到的除了支付的微信调用的都在这里
 */


class Wechat
{
    private $appId;
    private $appSecret;

    public function __construct($appId=null, $appSecret=null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;

    }

    /*获取access_token*/
    public function getAccessToken(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appId . "&secret=" . $this->appSecret;
        $res = $this->curl_get($url);
        $res = json_decode($res,true);
        return $res;
    }

    public function curl_get($url){
        $headers = array('User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36');
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 20);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    //获取微信B接口二维码 wxacode.getUnlimited
    public function getWxQrcode($access_token,$qrcode){
        $url='https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;
        $result = $this->httpRequest($url, $qrcode,"POST");
        return $result;
    }

    //把请求发送到微信服务器换取二维码
    function httpRequest($url, $data='', $method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    //小程序获取openid
    public function getOpenid($code){
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->appId . '&secret=' . $this->appSecret . '&js_code=' . $code . '&grant_type=authorization_code';
        return $this->curlGet($url);
    }

    //获取openid  请求
    function curlGet($url, &$httpCode = 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //不做证书校验,部署在linux环境下请改为true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $file_contents;
    }

    //公众号   生成二维码
    public function createQrcode($data){
        $token=$this->getAccessToken();
        if($token['access_token']) {
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $token['access_token'];
            $ticket_data = json_encode($data);
            $result = $this->httpRequest($url,$ticket_data,'POST');
            $jsoninfo = json_decode($result,true);
            if($jsoninfo['ticket'] && $jsoninfo['url']){
                $url='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$jsoninfo['ticket'];
                $qrcode=array('code'=>200,'qrcode'=>$url);
            }else{
                $qrcode=array('code'=>400);
            }
        }else{
            $qrcode=array('code'=>400);
        }
        return $qrcode;

    }
}
