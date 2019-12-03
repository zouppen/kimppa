<?php
class Scraper {
    function __construct($html) {
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
