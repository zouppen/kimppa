<?php
# Requires php-xml

ini_set('display_errors', 1);

class HtmlXpath {
    function __construct($url) {
        // Fetch HTML with cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_COOKIEFILE => $_SERVER['HOME']."/.wifistock_cookies",
            CURLOPT_COOKIEJAR => $_SERVER['HOME']."/.wifistock_cookies",
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 5,
        ]);

        $html = curl_exec($ch);

        // Try to get something to XPath with
        $doc = new DOMDocument();
        libxml_disable_entity_loader(TRUE);
        libxml_use_internal_errors(TRUE);
        $doc->loadHTML($html);
        $this->xpath = new DOMXpath($doc);
    }

    function query($path) {
        return $this->xpath->query($path)[0]->nodeValue;
    }
}

$url = @$_GET["url"];

if ($url === NULL) {
    $stuff = ["error" => 'Get parameter "url" missing'];
} else {
	// Is it safe?
    $safe = preg_match("|^https://www\.wifi-stock\.com/details/[^?/]+\.html$|", $url);

    if ($safe !== 1) {
        $stuff = ["error" => "Not Wifistock /details/ URL"];
    } else {
        $xp = new HtmlXpath($url);

        // Change currency if needed
        if ('EUR' != $xp->query('//*[@itemprop="priceCurrency"]/@content')) {
            $xp = new HtmlXpath("https://www.wifi-stock.com/currency/eur.html");
        }
        
        preg_match("/^(.*?)( \([^(]*)?$/", $xp->query('//h1'), $title);

        // Fetch interesting parts with XPath
        $stuff = [
            "price" => floatval($xp->query('//*[@itemprop="price"]/@content')),
            "id" => $xp->query('//input[@name="id"]/@value'),
            "title" => $title[1],
            "stock" => $xp->query('//link[@itemprop="availability"]/@href') === "http://schema.org/InStock",
        ];

        $currency = $xp->query('//*[@itemprop="priceCurrency"]/@content');
        if ($currency !== "EUR") $stuff['error'] = "Internal error, currency is " . $currency;
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
print(json_encode($stuff));
