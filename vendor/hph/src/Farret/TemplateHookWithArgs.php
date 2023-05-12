<?php
/**
 * {CLASS SUMMARY}
 *
 * Date: 7/27/18
 * Time: 11:31 PM
 * @author Michael Munger <michael@highpoweredhelp.com>
 */

namespace Hph\Farret;


class TemplateHookWithArgs extends AbstractTag
{

    protected $fartDictionary = [];

    public function setup()
    {
        $this->type = AbstractTag::HOOK;
        $this->parseArgs();
    }

    private function trimArg($arg) {

        $arg = str_replace("|",'',$arg);
        $arg = trim($arg);
        return $arg;
    }

    public function parseArgs() {
        $argsPattern = '/(\|(?:{{){0,1}([A-Za-z0-9-]+)(?:}}){0,1})/';
        $matches = [];

        preg_match_all($argsPattern, $this->tag, $matches, PREG_PATTERN_ORDER);

        //Clean up the args.
        $this->args = array_map([$this,'trimArg'], $matches[0]);

    }

    public function getLabel()
    {
        $matches = [];
        $result = (preg_match_all($this->pattern, $this->tag,$matches, PREG_SET_ORDER) !== false);

        return ($result ? $matches[0][1] : false);
    }

    public function fart($dictionary) {
        $TagFactory = new TagFactory();

        for($x = 0; $x < count($this->args); $x++) {
            $Tag = $TagFactory->getTag($this->args[$x]);

            //If this argument is NOT a tag like {{FIRSTNAME}}
            if($Tag === false) {
                foreach($dictionary as $find => $replace) {
                    $this->args[$x] = str_replace($find, $replace, $this->args[$x]);
                }
                continue;
            }

            //This is some sort of tag. Use the Tag class to do the replacement.
            $Tag->fart($dictionary);
            $this->args[$x] = $Tag->getReplacement();
        }
    }

    public function getTag()
    {
//        $pattern = '/(?:{%)\s{0,}([A-Z]+)\s{0,}\|.*\s{0,}(?:%})/m';
//        $matches = [];
//        $result = (preg_match_all($pattern, $this->tag,$matches, PREG_SET_ORDER) !== false);
//
//        return ($result ? $matches[0][1] : false);
        return $this->tag;
    }

    public function getReplacement()
    {
        return $this->replacement;
    }
}