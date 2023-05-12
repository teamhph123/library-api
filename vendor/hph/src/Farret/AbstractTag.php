<?php
/**
 * {CLASS SUMMARY}
 *
 * Date: 7/27/18
 * Time: 11:14 PM
 * @author Michael Munger <michael@highpoweredhelp.com>
 */

namespace Hph\Farret;

abstract class AbstractTag
{
    protected $pattern;
    protected $tag;
    protected $args = [];
    protected $replacement = null;
    protected $this = null;

    const TAG  = 0;
    const HOOK = 1;

    public function __construct($pattern, $tag)
    {
        $this->pattern = $pattern;
        $this->tag = $tag;
        $this->setup();
    }

    public function getArgs() {
        return $this->args;
    }

    public function setReplacement($replacement) {
        $this->replacement = $replacement;
    }

    abstract public function getLabel();
    abstract public function setup();
    abstract public function fart($dictionary);
    abstract public function getTag();
    abstract public function getReplacement();
}