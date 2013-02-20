<?php
namespace Pagon\Helper;

class XML
{
    public static function toArray($content)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($content);
        return self::_structureArray($dom);
    }

    /**
     * 处理成数组
     * @static
     * @param \DOMNode $node
     * @return array|string
     */
    protected static function _structureArray(\DOMNode $node)
    {
        $occurance = array();
        $result = array();


        if ($node->childNodes) {
            foreach ($node->childNodes as $child) {
                $occurance[$child->nodeName]++;
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
                        if ($occurance[$child->nodeName] > 1) {
                            $result[$child->nodeName][] = self::_structureArray($child);
                        } else {
                            $result[$child->nodeName] = self::_structureArray($child);
                        }
                    } else {
                        if ($child->nodeName == '#text') {
                            $text = self::_structureArray($child);

                            if (trim($text) != '') {
                                $result[$child->nodeName] = self::_structureArray($child);
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

    /**
     * 从ARRAY转换
     *
     * @static
     * @param        $object
     * @param string $root
     * @param string $unknown
     * @param string $doc_type
     * @return string
     */
    public static function fromArray($object, $root = 'root', $unknown = 'item', $doc_type = '<?xml version="1.0" encoding="utf-8"?>')
    {
        return $doc_type . "<{$root}>" . self::toXml($object, $unknown) . "</{$root}>";
    }

    /**
     * 处理XML
     *
     * @static
     * @param        $array
     * @param string $unknown
     * @return string
     */
    public static function toXml($array, $unknown = 'item')
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
                $xml .= self::toXml($v, $unknown);
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


}

// END