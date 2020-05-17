<?php

namespace Creonit\MarkupBundle\Twig\Extension;

use Creonit\MarkupBundle\Markup;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MarkupExtension extends AbstractExtension
{
    /**
     * @var Markup
     */
    protected $markup;

    public function __construct(Markup $markup)
    {
        $this->markup = $markup;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('shuffle', [$this, 'shuffle']),
            new TwigFilter('bem', [$this, 'bem'])
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('load', [$this->markup, 'load']),
        ];
    }

    public function shuffle($array)
    {
        if ($array instanceof \Traversable) {
            $array = iterator_to_array($array, false);
        }
        shuffle($array);
        return $array;
    }

    public function bem($string, $mods, $isAppendSelf = false)
    {
        $array = [];
        if ( is_iterable($mods) ) {
            foreach($mods as $k=>$v) {
                if ( is_int($k) ) {
                    $array[] = $this->bem($string, $v);
                }
                else if ( $v ) {
                    $array[] = $this->bem($string, $k);
                }
            }
        }
        else if ( is_string($mods) ) {
            $array = explode(' ', $mods);
        }

        if ( $isAppendSelf ) {
            $array = array_merge([$string], $array);
        }

        $result = array_reduce($array, function ($carry, $item) use ($string) {
            if ( preg_match('/^_/', $item) ) {
                return $carry .' '. $string . $item;
            }
            return $carry .' '. $item;
        }, '');

        return $result;
    }
}