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

    function scrape($def) {
        $ans = $this->query($def['xpath']);
        if (array_key_exists('regex', $def)) {
            preg_match($def['regex'], $ans, $groups);
            if (array_key_exists('group', $def)) {
                $ans = $groups[$def['group']];
            } else {
                $ans = $groups[0];
            }
        }
        if (array_key_exists('test', $def)) {
            $ans = $ans === $def['test'];
        }
        return $ans;
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
        $eurotest = [
            'xpath' => '//*[@itemprop="priceCurrency"]/@content',
            'test' => 'EUR',
        ];
        if (!$xp->scrape($eurotest)) {
            $xp = new HtmlXpath("https://www.wifi-stock.com/currency/eur.html");
        }
        $is_euro = $xp->scrape($eurotest);
        
        // Fetch interesting parts with XPath
        $stuff = [
            "price" => floatval($xp->scrape([
                'xpath' => '//*[@itemprop="price"]/@content',
            ])),
            "id" => $xp->scrape([
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
