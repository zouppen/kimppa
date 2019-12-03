<?php
# Requires php-xml

//ini_set('display_errors', 1);

require_once __DIR__ . '/scraper.php';

class HttpHelper {
    function __construct() {
        // Fetch HTML with cURL
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIEFILE => $_SERVER['HOME']."/.wifistock_cookies",
            CURLOPT_COOKIEJAR => $_SERVER['HOME']."/.wifistock_cookies",
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 5,
        ]);
    }

    function fetch($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        return curl_exec($this->ch);
    }
}

$http = new HttpHelper();
$url = @$_GET["url"];

if ($url === NULL) {
    $stuff = ["error" => 'Get parameter "url" missing'];
} else {
	// Is it safe?
    $safe = preg_match("|^https://www\.wifi-stock\.com/details/[^?/]+\.html$|", $url);

    if ($safe !== 1) {
        $stuff = ["error" => "Not Wifistock /details/ URL"];
    } else {
        $xp = new Scraper($http->fetch($url));

        // Change currency if needed
        $eurotest = [
            'xpath' => '//*[@itemprop="priceCurrency"]/@content',
            'test' => 'EUR',
        ];
        if (!$xp->scrape($eurotest)) {
            $xp = new Scraper($http->fetch("https://www.wifi-stock.com/currency/eur.html"));
        }
        $is_euro = $xp->scrape($eurotest);
        
        // Fetch interesting parts with XPath
        $stuff = [
            "price" => floatval($xp->scrape([
                'xpath' => '//*[@itemprop="price"]/@content',
            ])),
            "code" => $xp->scrape([
                'xpath' => '//input[@name="id"]/@value',
            ]),
            "title" => $xp->scrape([
                'xpath' => '//h1',
                'regex' => "/^(.*?)( \([^(]*)?$/",
                'group' => 1,
            ]),
            "stock" => $xp->scrape([
                'xpath' => '//link[@itemprop="availability"]/@href',
                'test' => "http://schema.org/InStock",
            ]),
        ];

        // Check if the currency is properly selected
        if (!$is_euro) $stuff['error'] = "Internal error, currency is not euro";
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
print(json_encode($stuff));
