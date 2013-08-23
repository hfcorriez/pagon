<?php

namespace Pagon\Config;

/**
 * Class Xml
 * functional usage for build and parse the xml
 *
 * @package Pagon
 */
class Xml
{
    /**
     * Xml to array
     *
     * @param string $content
     * @return array|string
     */
    public static function parse($content)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($content);
        return self::structureArray($dom);
    }

    /**
     * Array to xml
     *
     * @static
     * @param array $array
     * @param array $option
     * @return string
     */
    public static function dump($array, $option = array())
    {
        $option += array(
            'doctype' => '<?xml version="1.0" encoding="utf-8"?>',
            'root'    => 'root',
            'item'    => 'item',
        );
        return $option['doctype'] . "<{$option['root']}>" . self::dumpBody($array, $option['item']) . "</{$option['root']}>";
    }

    /**
     * Array item to xml node
     *
     * @static
     * @param array  $array
     * @param string $unknown
     * @return string
     */
    public static function dumpBody($array, $unknown = 'item')
    {
        $xml = '';
        foreach ($array as $k => $v) {
            $tag = $k;
            $attr = '';
            if (preg_match('/^[0-9]+/', $k)) {
                $tag = $unknown;
            }
            if (is_array($v) || is_object($v)) {
                if (is_object($v)) {
                    $v = (array)$v;
                }
                $xml .= "<$tag{$attr}>";
                $xml .= self::dumpBody($v, $unknown);
                $xml .= "</$tag>";
            } else {
                if (!preg_match("/[" . chr(0xa1) . "-" . chr(0xff) . "]+/i", $v) && !preg_match("/[\xa1-\xff]+/i", $v) && !preg_match("/[&]/i", $v)) {
                    $xml .= "<$tag{$attr}>$v</$tag>";
                } else {
                    $xml .= "<$tag{$attr}><![CDATA[$v]]></$tag>";
                }
            }
        }
        return $xml;
    }

    /**
     * Structure the array
     *
     * @static
     * @param \DOMNode $node
     * @return array|string
     */
    protected static function structureArray(\DOMNode $node)
    {
        $occurrence = array();
        $result = array();


        if ($node->childNodes) {
            foreach ($node->childNodes as $child) {
                $occurrence[$child->nodeName]++;
            }
        }

        if ($node->nodeType == XML_TEXT_NODE) {
            $result = html_entity_decode(htmlentities($node->nodeValue, ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'ISO-8859-15');
        } else {
            if ($node->hasChildNodes()) {
                $children = $node->childNodes;

                for ($i = 0; $i < $children->length; $i++) {
                    $child = $children->item($i);

                    if ($child->nodeName != '#text') {
                        if ($occurrence[$child->nodeName] > 1) {
                            $result[$child->nodeName][] = self::structureArray($child);
                        } else {
                            $result[$child->nodeName] = self::structureArray($child);
                        }
                    } else {
                        if ($child->nodeName == '#text') {
                            $text = self::structureArray($child);

                            if (trim($text) != '') {
                                $result[$child->nodeName] = self::structureArray($child);
                            }
                        }
                    }
                }
            }

            if ($node->hasAttributes()) {
                $attributes = $node->attributes;

                if (!is_null($attributes)) {
                    foreach ($attributes as $key => $attr) {
                        $result["@" . $attr->name] = $attr->value;
                    }
                }
            }
        }

        return $result;
    }
}