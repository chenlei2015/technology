<?php
/**
 * RSA加密解密
 * @desc 此类主要提供三个方法操作RSA
 *       setKey: 设置秘钥
 *       privateEncrypt:RSA私钥加密
 *       publicDecrypt:RSA公钥解密(私钥加密的内容通过公钥可以解密出来)
 *       publicEncrypt:RSA公钥加密
 *       privateDecrypt:RSA私钥解密
 * User: zhut
 * Date: 2018/8/24
 * Time: 13:41
 */
class Rsa {

    private $private_key = '';
    private $public_key = '';

    /**
     * 设置加密解密钥匙
     * @param string $private_key 私钥存放路径 可为空
     * @param string $public_key 公钥存放路径 可为空
     */
    public function setKey($private_key = '',$public_key = ''){
        if (!empty($private_key)){
            $this->private_key = $private_key;
        }

        if (!empty($public_key)){
            $this->public_key = $public_key;
        }
    }

    /**
     * RSA私钥加密
     * @param string $data 要加密的字符串
     * @return string $encrypted 返回加密后的字符串
     */
    function privateEncrypt($data){
        $fp=fopen($this->private_key,"r");
//        extension_loaded('openssl') or die('php需要openssl扩展支持');
        $this->private_key = fread($fp,filesize($this->private_key));

        fclose($fp);

        $encrypted = '';
        $pi_key =  openssl_pkey_get_private($this->private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        //最大允许加密长度为117，得分段加密
        $plainData = str_split($data, 100);//生成密钥位数 1024 bit key
        foreach($plainData as $chunk){
            $partialEncrypted = '';
            $encryptionOk = openssl_private_encrypt($chunk,$partialEncrypted,$pi_key);//私钥加密
            if($encryptionOk === false){
                return false;
            }
            $encrypted .= $partialEncrypted;
        }

        $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    /**
     * RSA公钥解密(私钥加密的内容通过公钥可以解密出来)
     * @param string $data 私钥加密后的字符串
     * @return string $decrypted 返回解密后的字符串
     */
    function publicDecrypt($data){
        $fp=fopen($this->public_key,"r");

        $this->public_key = fread($fp,filesize($this->public_key));
        fclose($fp);

        $decrypted = '';
        $pu_key = openssl_pkey_get_public($this->public_key);//这个函数可用来判断公钥是否是可用的
        $plainData = str_split(base64_decode($data), 128);//生成密钥位数 1024 bit key
        foreach($plainData as $chunk){
            $str = '';
            $decryptionOk = openssl_public_decrypt($chunk,$str,$pu_key);//公钥解密
            if($decryptionOk === false){
                return false;
            }
            $decrypted .= $str;
        }
        return $decrypted;
    }

//    /**
//     * RSA公钥加密
//     * @param string $data 要加密的字符串
//     * @return bool|string 返回加密后的字符串
//     */
//    function publicEncrypt($data){
//
//        $fp=fopen($this->public_key,"r");
//
//        $this->public_key = fread($fp,filesize($this->public_key));
//        fclose($fp);
//
//        $encrypted = '';
//        $pu_key = openssl_pkey_get_public($this->public_key);
//
//        openssl_public_encrypt($data,$encrypted,$pu_key);//公钥加密
//
//        $encrypted = base64_encode($encrypted);
//        return $encrypted;
//    }
//
//    /**
//     * RSA私钥解密
//     * @param string $data 公钥加密后的字符串
//     * @return bool|string 返回解密后的字符串
//     */
//    function privateDecrypt($data){
//        $fp=fopen($this->private_key,"r");
////        extension_loaded('openssl') or die('php需要openssl扩展支持');
//        $this->private_key = fread($fp,filesize($this->private_key));
//
//        fclose($fp);
//
//        $decrypted = '';
//        $pi_key = openssl_pkey_get_private($this->private_key);
//        $plainData = base64_decode($data);
//
////        pr($pi_key);die;
//
//        $decryptionOk = openssl_private_decrypt($plainData,$decrypted,$pi_key);//私钥解密
//        if($decryptionOk === false){
//            return false;
//        }
//
//        return $decrypted;
//    }

    /**
     * RSA公钥加密
     * @param string $data 要加密的字符串
     * @return bool|string 返回加密后的字符串
     */
    function publicEncrypt($data){

        $fp=fopen($this->public_key,"r");

        $this->public_key = fread($fp,filesize($this->public_key));
        fclose($fp);

        $encrypted = '';
        $pu_key = openssl_pkey_get_public($this->public_key);
        $dataArray = str_split($data, 117);

        foreach ($dataArray as $value) {
            $encryptedTemp = "";
            openssl_public_encrypt($value,$encryptedTemp,$pu_key);//公钥加密
            $encrypted .= base64_encode($encryptedTemp);
            pr($encrypted);
        }

        return $encrypted;
    }

    /**
     * RSA私钥解密
     * @param string $data 公钥加密后的字符串
     * @return bool|string 返回解密后的字符串
     */
    function privateDecrypt($data){
        $fp=fopen($this->private_key,"r");
//        extension_loaded('openssl') or die('php需要openssl扩展支持');
        $this->private_key = fread($fp,filesize($this->private_key));

        fclose($fp);

        $decrypted = '';
        $pi_key = openssl_pkey_get_private($this->private_key);
        $enArray = str_split($data, 172);
    
        foreach ($enArray as $va) {
            $decryptedTemp = '';
            $va = base64_decode($va);
            $decryptionOk = openssl_private_decrypt($va,$decryptedTemp,$pi_key);//私钥解密
            if($decryptionOk === false){
                return false;
            }
            $decrypted .= $decryptedTemp;
        }

        return $decrypted;
    }


}