<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 04/01/2018
 * Time: 00:25
 */
class FD_Bittrex
{

    protected $API_KEY;
    protected $secretkey;
    protected $header;

    function FD_Bittrex($key, $secretkey)
    {
        //$this->ini_key = parse_ini_file("/config/cryptocurrency.ini");
        $this->API_KEY = $key;
        $this->secretkey = $secretkey;
        /*$nonce=time();
        $uri='https://bittrex.com/api/v1.1/market/getopenorders?apikey='.$key.'&nonce='.$nonce;
        $sign=hash_hmac('sha512',$uri,$secretkey);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        $execResult = curl_exec($ch);*/
        //$obj = json_decode($execResult);
    }

    /*********************************
     * PUBLIC
     *********************************/

    public function getmarkets()
    {
        $url = "https://bittrex.com/api/v1.1/public/getmarkets";
        return $this->SendBittrexRequest($url, true);
    }

    public function getcurrencies()
    {
        $url = "https://bittrex.com/api/v1.1/public/getcurrencies";
        return $this->SendBittrexRequest($url, true);
    }

    public function getticker($market)
    {
        $url = "https://bittrex.com/api/v1.1/public/getticker?market=" . $market;
        return $this->SendBittrexRequest($url, true);
    }

    public function getmarketsummaries()
    {
        $url = "https://bittrex.com/api/v1.1/public/getmarketsummaries";
        return $this->SendBittrexRequest($url, true);
    }

    public function getmarketsummary($market)
    {
        $url = "https://bittrex.com/api/v1.1/public/getmarketsummary?market=" . $market;
        return $this->SendBittrexRequest($url, true);
    }

    public function getorderbook($market, $type)
    {
        $url = "https://bittrex.com/api/v1.1/public/getorderbook?market=" . $market . "&type=" . $type;
        return $this->SendBittrexRequest($url, true);
    }

    public function getmarkethistory($market)
    {
        $url = "https://bittrex.com/api/v1.1/public/getmarkethistory?market=" . $market;
        return $this->SendBittrexRequest($url, true);
    }

    /*********************************
     * MARKET
     *********************************/

    public function buylimit($market, $quantity, $rate)
    {
        $url = "https://bittrex.com/api/v1.1/market/buylimit?apikey=" . $this->API_KEY . "&market=" . $market . "&quantity=" . $quantity . "&rate=" . $rate;
        return $this->SendBittrexRequest($url);
    }

    public function selllimit($market, $quantity, $rate)
    {
        $url = "https://bittrex.com/api/v1.1/market/selllimit?apikey=" . $this->API_KEY . "&market=" . $market . "&quantity=" . $quantity . "&rate=" . $rate;
        return $this->SendBittrexRequest($url);
    }

    public function cancel($ORDER_UUID)
    {
        $url = "https://bittrex.com/api/v1.1/market/cancel?apikey=" . $this->API_KEY . "&uuid=" . $ORDER_UUID;
        return $this->SendBittrexRequest($url);
    }

    public function getopenorders($market)
    {
        $url = "https://bittrex.com/api/v1.1/market/cancel?apikey=" . $this->API_KEY . "&market=" . $market;
        return $this->SendBittrexRequest($url);
    }

    /*********************************
     * ACCOUNT
     *********************************/

    public function getbalances($currency = "")
    {
        if ($currency == "") {
            $url = "https://bittrex.com/api/v1.1/account/getbalances?apikey=" . $this->API_KEY;
        } else {
            $url = "https://bittrex.com/api/v1.1/account/getbalances?apikey=" . $this->API_KEY . "&currency=" . $currency;
        }
        return $this->SendBittrexRequest($url);
    }

    public function getdepositaddress($currency)
    {
        $url = "https://bittrex.com/api/v1.1/account/getdepositaddress?apikey=" . $this->API_KEY . "&currency=" . $currency;
        return $this->SendBittrexRequest($url);
    }

    public function withdraw($currency, $quantity, $address)
    {
        $url = "https://bittrex.com/api/v1.1/account/withdraw?apikey=" . $this->API_KEY . "&currency=" . $currency . "&quantity=" . $quantity . "&address=" . $address;
        return $this->SendBittrexRequest($url);
    }

    public function getorder($ORDER_UUID)
    {
        $url = "https://bittrex.com/api/v1.1/account/getorder?uuid=" . $ORDER_UUID;
        return $this->SendBittrexRequest($url);
    }

    public function getorderhistory()
    {
        $url = "https://bittrex.com/api/v1.1/account/getorderhistory";
        return $this->SendBittrexRequest($url);
    }

    public function getwithdrawalhistory($currency){
        $url = "https://bittrex.com/api/v1.1/account/getwithdrawalhistory?currency=" . $currency;
        return $this->SendBittrexRequest($url);
    }

    public function getdeposithistory($currency){
        $url = "https://bittrex.com/api/v1.1/account/getdeposithistory?currency=" . $currency;
        return $this->SendBittrexRequest($url);
    }

    /**
     * @return mixed
     * FUNZIONI PRIVATE
     */

    //Metodo per la richiesta POST al server API
    private function SendBittrexRequest($url, $public = false)
    {
        $nonce = time();
        if ($public == false) {
            $url = $url . "&nonce=" . $nonce;
        }
        $sign = hash_hmac('sha512', $url, $this->secretkey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:' . $sign));
        $execResult = curl_exec($ch);
        //$obj = json_decode($execResult);
        return $execResult;
    }

}