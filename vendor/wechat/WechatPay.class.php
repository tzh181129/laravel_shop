<?php
//微信支付功能封装

class WechatPay {

    //统一下单
    public function pay($new_price, $body, $order_no, $openid,$notify_url)
    {
        $appid = 'wxe8dab1ad1f733476';
        $pay_config = M('wechat_config')->find();
        $mch_id = $pay_config['mch_id'];   //商户号id
        $key = $pay_config['mch_key'];      //商户秘钥
        $fee = $new_price;
        $nonce_str = $this->nonce_str();
        $out_trade_no = $order_no;
        $total_fee = $fee * 100;
        $trade_type = 'JSAPI';
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;
        $post['notify_url'] = $notify_url;
        $post['openid'] = $openid;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = '114.215.81.213';      // ip地址
        $post['total_fee'] = $total_fee;
        $post['trade_type'] = $trade_type;
        $sign = $this->sign($post, $key);      //签名
        $post_xml = '<xml><appid>' . $appid . '</appid><body>' . $body . '</body><mch_id>' . $mch_id . '</mch_id><nonce_str>' . $nonce_str . '</nonce_str><notify_url>' . $notify_url . '</notify_url>
                    <openid>' . $openid . '</openid><out_trade_no>' . $out_trade_no . '</out_trade_no><spbill_create_ip>' . $post['spbill_create_ip'] . '</spbill_create_ip><total_fee>' . $post['total_fee'] . '</total_fee>
                    <trade_type>' . $trade_type . '</trade_type><sign>' . $sign . '</sign></xml>';
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url, $post_xml);
        $array = $this->xml($xml);
        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS') {
            $time = time();
            $tmp = '';      //临时数组用于签名       
            $tmp['appId'] = $appid;
            $tmp['nonceStr'] = $nonce_str;
            $tmp['package'] = 'prepay_id=' . $array['PREPAY_ID'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";
            $data['state'] = 200;
            $data['timeStamp'] = "$time";             //时间戳       
            $data['nonceStr'] = $nonce_str;        //随机字符串       
            $data['signType'] = 'MD5';                //签名算法，暂支持 MD5       
            $data['package'] = 'prepay_id=' . $array['PREPAY_ID'];   //统一下单接口返回的 prepay_id 参数值，提交格式如：prepay_id=*       
            $data['paySign'] = $this->sign($tmp, $key);      //签名,具体签名方案参见微信公众号支付帮助文档;       
            $data['out_trade_no'] = $out_trade_no;
        } else {
            $data['state'] = 0;
            $data['text'] = "错误";
            $data['RETURN_CODE'] = $array['RETURN_CODE'];
            $data['RETURN_MSG'] = $array['RETURN_MSG'];
        }
        return $data;
    }


    //主动查询订单支付结果并修改订单状态
    public function orderStatus($out_trade_no)
    {
        $appid = 'wxe8dab1ad1f733476';
        $pay_config = M('wechat_config')->find();
        $mch_id = $pay_config['mch_id'];   //商户号id
        $key = $pay_config['mch_key'];      //商户秘钥
        $nonce_str = $this->nonce_str();
        $post['appid'] = $appid;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;
        $post['out_trade_no'] = $out_trade_no;
        $sign = $this->sign($post, $key);      //签名
        $post_xml = '<xml><appid>' . $appid . '</appid><mch_id>' . $mch_id . '</mch_id><nonce_str>' . $nonce_str . '</nonce_str>
                 <out_trade_no>' . $out_trade_no . '</out_trade_no><sign>' . $sign . '</sign></xml>';
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $xml = $this->http_request($url, $post_xml);
        $array = $this->xml($xml);
        return $array;
//        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS' && $array['TRADE_STATE'] == 'SUCCESS') {
//            $out_trade_no = $array['OUT_TRADE_NO'];        //订单号
//            M('wechat_order')->where(['order_no' => $out_trade_no])->save(['status' => 1]);
//        }
    }

    //申请退款
    public function refund($body){
        $post['appid'] = 'wxe8dab1ad1f733476';
        $pay_config = M('wechat_config')->find();
        $post['mch_id'] = $pay_config['mch_id'];   //商户号id
        $key = $pay_config['mch_key'];      //商户秘钥
        $post['nonce_str']=$this->nonce_str();
        $post['total_fee']=$body['shop_total']*100;          //订单金额
        $post['out_trade_no']=$body['out_trade_no'];
        $post['out_refund_no']=$body['out_refund_no'];  //退款号
        $post['refund_fee']=$body['refund_fee']*100;         //退款金额
//        $post['refund_desc']=$body['refund_desc'];       //退款原因
        $sign = $this->refund_sign($post, $key);      //签名
        $post_xml = '<xml><appid>' . $post['appid'] . '</appid><mch_id>' . $post['mch_id'] . '</mch_id><nonce_str>' . $post['nonce_str'] . '</nonce_str>
                 <out_refund_no>' . $post['out_refund_no'] . '</out_refund_no><out_trade_no>' .  $post['out_trade_no'] . '</out_trade_no>
                <refund_fee>' . $post['refund_fee'] . '</refund_fee><total_fee>' . $post['total_fee'] . '</total_fee><sign>' . $sign . '</sign></xml>';
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $res = $this->postXmlCurl($url, $post_xml);
        $array = $this->xml($res);
        return $array;
    }
//
//    //主动查询退款状态
//    public function refundStatus($out_refund_no){
//        $appid = 'wxe8dab1ad1f733476';
//        $pay_config = M('wechat_config')->find();
//        $mch_id = $pay_config['mch_id'];   //商户号id
//        $key = $pay_config['mch_key'];      //商户秘钥
//        $nonce_str = $this->nonce_str();
//        $post['appid'] = $appid;
//        $post['mch_id'] = $mch_id;
//        $post['nonce_str'] = $nonce_str;
//        $post['out_refund_no'] = $out_refund_no;
//        $sign = $this->refund_sign($post, $key);      //签名
//        $post_xml = '<xml><appid>' . $appid . '</appid><mch_id>' . $mch_id . '</mch_id><nonce_str>' . $nonce_str . '</nonce_str>
//                 <out_refund_no>' . $out_refund_no . '</out_refund_no><sign>' . $sign . '</sign></xml>';
//        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
//        $xml = $this->http_request($url, $post_xml);
//        $array = $this->xml($xml);
//        return $array;
//    }


    //常见订单号
    public function order_no($openid)
    {            //订单号
        return md5($openid . time() . rand(10, 99));
    }

    //退款单号
    public function out_refund_no(){
        return md5(date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT));
    }

    public function nonce_str()
    {                   //随机字符串
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i = 0; $i < 32; $i++) {
            $result .= $str[rand(0, 48)];
        }
        return $result;
    }

    public function sign($data, $mch_key)
    {                //签名
        $stringA = '';
        foreach ($data as $key => $value) {
            if (!$value) continue;
            if ($stringA) $stringA .= '&' . $key . "=" . $value;
            else $stringA = $key . "=" . $value;
        }
        $stringSignTemp = $stringA . '&key=' . $mch_key;
        return strtoupper(md5($stringSignTemp));
    }

    //curl请求
    public function http_request($url, $data = null, $headers = array())
    {
        $curl = curl_init();
        if (count($headers) >= 1) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);


        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;

    }

    //获取xml
    public function xml($xml)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $data = "";
        foreach ($index as $key => $value) {
            if ($key == 'xml' || $key == 'XML') continue;
            $tag = $vals[$value[0]]['tag'];
            $value = $vals[$value[0]]['value'];
            $data[$tag] = $value;
        }
        return $data;

    }

    public function refund_sign($data, $mch_key)
    {                //签名
        $stringA = '';
        ksort($data);
        foreach ($data as $key => $value) {
            if (!$value) continue;
            if ($stringA) $stringA .= '&' . $key . "=" . $value;
            else $stringA = $key . "=" . $value;
        }

        $stringSignTemp = $stringA . '&key=' . $mch_key;
        return strtoupper(md5($stringSignTemp));
    }

/**
 * TODO 证书的路径要求为 服务器中的绝对路径
 * TODO 证书是 在微信支付开发文档中有所提及，可自行获取保存
 */
    public function postXmlCurl($url,$xml, $useCert = true, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

//        $proxyHost = "0.0.0.0";
//        $proxyPort = 0;
        //如果有配置代理这里就设置代理
//        if($proxyHost != "0.0.0.0" && $proxyPort != 0){
//            curl_setopt($ch,CURLOPT_PROXY, $proxyHost);
//            curl_setopt($ch,CURLOPT_PROXYPORT, $proxyPort);
//        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            //证书文件请放入服务器的非web目录下
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, 'cert'.DIRECTORY_SEPARATOR.'apiclient_cert.pem');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, 'cert'.DIRECTORY_SEPARATOR.'apiclient_key.pem');
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return $error;
        }
    }

    //添加生成小程序二维码
    public function createQrcode($tel,$qrcode){
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxe8dab1ad1f733476&secret=0b54b876a182d11bac8f500aefd92c16";
        $res=$this->http_request($url);
        $res=json_decode($res,true);
        $access_token=$res['access_token'];
        $result="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $data=[
            'scene'=>$tel.'-'.$qrcode,
            'page'=>'pagesAdd/pages/boxLottery/boxLottery',
            'width'=>300,
            'auto_color'=>false,
            'is_hyaline'=>false
        ];
        $data=json_encode($data);
        $qrcode_res=$this->curl_post_https($result,$data);
        //生成二维码
        file_put_contents("qrcode.png", $qrcode_res);
        $base64_image ="data:image/jpeg;base64,".base64_encode($qrcode_res);
        return $base64_image;
    }

    // 模拟post进行url请求
    public function curl_post_https($url,$data){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }else{
            return $tmpInfo;
        }
    }


    //Native支付   生成二维码
    public function create_qrcode($jine,$body,$out_trade_no){
        $appid = 'wxe8dab1ad1f733476';
        $pay_config = M('wechat_config')->find();
        $mch_id = $pay_config['mch_id'];   //商户号id
        $key = $pay_config['mch_key'];      //商户秘钥
        $fee = $jine*100;
        $nonce_str = $this->nonce_str();
        $notify_url = 'http://tosxcx.xuanweiai.com/WechatCallback/mass_callback';
        $total_fee = $fee ;
        $trade_type = 'NATIVE';
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;
        $post['notify_url'] = $notify_url;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = '114.215.81.213';      // ip地址
        $post['total_fee'] = $total_fee;
        $post['trade_type'] = $trade_type;
        $sign = $this->sign($post, $key);      //签名
        $post_xml = '<xml><appid>' . $appid . '</appid><body>' . $body . '</body><mch_id>' . $mch_id . '</mch_id><nonce_str>' . $nonce_str . '</nonce_str><notify_url>' . $notify_url . '</notify_url>
                   <out_trade_no>' . $out_trade_no . '</out_trade_no><spbill_create_ip>' . $post['spbill_create_ip'] . '</spbill_create_ip><total_fee>' . $post['total_fee'] . '</total_fee>
                    <trade_type>' . $trade_type . '</trade_type><sign>' . $sign . '</sign></xml>';
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url, $post_xml);
        $array = $this->xml($xml);
        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS') {
            $code_url=$array['CODE_URL'];
            if(substr($code_url, 0, 6) == "weixin"){
                $data['status'] = 200;
                $data['code_url'] = $array['CODE_URL'];
            }else{
                $data['status'] = 0;
                $data['info'] = "错误";
            }
        } else {
            $data['status'] = 0;
            $data['info'] = "错误";
        }
        return $data;
    }

    //Native支付   生成二维码支付
    public function qrcode_pay($jine,$body,$out_trade_no,$notify_url){
        $appid = 'wxe8dab1ad1f733476';
        $pay_config = M('wechat_config')->find();
        $mch_id = $pay_config['mch_id'];   //商户号id
        $key = $pay_config['mch_key'];      //商户秘钥
        $fee = $jine*100;
        $nonce_str = $this->nonce_str();
        $total_fee = $fee ;
        $trade_type = 'NATIVE';
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;
        $post['notify_url'] = $notify_url;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = '114.215.81.213';      // ip地址
        $post['total_fee'] = $total_fee;
        $post['trade_type'] = $trade_type;
        $sign = $this->sign($post, $key);      //签名
        $post_xml = '<xml><appid>' . $appid . '</appid><body>' . $body . '</body><mch_id>' . $mch_id . '</mch_id><nonce_str>' . $nonce_str . '</nonce_str><notify_url>' . $notify_url . '</notify_url>
                   <out_trade_no>' . $out_trade_no . '</out_trade_no><spbill_create_ip>' . $post['spbill_create_ip'] . '</spbill_create_ip><total_fee>' . $post['total_fee'] . '</total_fee>
                    <trade_type>' . $trade_type . '</trade_type><sign>' . $sign . '</sign></xml>';
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url, $post_xml);
        $array = $this->xml($xml);
        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS') {
            $code_url=$array['CODE_URL'];
            if(substr($code_url, 0, 6) == "weixin"){
                $data['status'] = 200;
                $data['code_url'] = $array['CODE_URL'];
            }else{
                $data['status'] = 0;
                $data['info'] = "错误";
            }
        } else {
            $data['status'] = 0;
            $data['info'] = "错误";
        }
        return $data;
    }

    //app支付
    public function h_wechat_pay($fee, $body, $out_trade_no,$notify_url){
        $appid = 'wxbf0af520e51986a2';
        $pay_config = M('wechat_config')->find();
        $mch_id = $pay_config['mch_id'];   //商户号id
        $key = $pay_config['mch_key'];      //商户秘钥
        $nonce_str = $this->nonce_str();
        $total_fee = $fee * 100;
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;
        $post['notify_url'] = $notify_url;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = '114.215.81.213';      // ip地址
        $post['total_fee'] = $total_fee;
        $post['trade_type'] = $trade_type='APP';
        $sign = $this->sign($post, $key);      //签名
        $post_xml = '<xml><appid>' . $appid . '</appid><body>' . $body . '</body><mch_id>' . $mch_id . '</mch_id><nonce_str>' . $nonce_str . '</nonce_str><notify_url>' . $notify_url . '</notify_url>
                   <out_trade_no>' . $out_trade_no . '</out_trade_no><spbill_create_ip>' . $post['spbill_create_ip'] . '</spbill_create_ip><total_fee>' . $post['total_fee'] . '</total_fee>
                    <trade_type>' . $trade_type . '</trade_type><sign>' . $sign . '</sign></xml>';
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url, $post_xml);
        $array = $this->xml($xml);
        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS') {
            $data['appid']=$appid;
            $data['noncestr']=$array['NONCE_STR'];
            $data['package']='Sign=WXPay';
            $data['partnerid']=$mch_id;
            $data['prepayid']=$array['PREPAY_ID'];
            $data['timestamp']=time();
            $data['sign'] = $this->sign($data, $key);      //签名
            $data['out_trade_no'] = $out_trade_no;
            $data['state'] = 200;
        } else {
            $data['state'] = 0;
            $data['text'] = "错误";
            $data['RETURN_CODE'] = $array['err_code'];
            $data['RETURN_MSG'] = $array['err_code_des'];
        }
        return $data;
    }

}
