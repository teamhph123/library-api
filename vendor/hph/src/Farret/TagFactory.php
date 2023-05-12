<?php
/**
 * {CLASS SUMMARY}
 *
 * Date: 7/27/18
 * Time: 10:53 PM
 * @author Michael Munger <michael@highpoweredhelp.com>
 */

namespace Hph\Farret;


class TagFactory extends AbstractTagFactory
{
    public function getTag($tag) {

        $templatePattern             = '/^(?:{{)\s{0,}([A-Z]+)\s{0,}(?:}})$/m';
        $templateHookPattern         = '/(?:{%)\s{0,}([A-Z]+)\s{0,}(?:%})/';
        $templateHookWithArgsPattern = '/(?:{%)\s{0,}([A-Z]+)\|([a-zA-Z0-9-{}|]+)\s{0,}(?:%})/';

        $matches = [];

        if(preg_match($templatePattern, $tag) === 1 ) {
            return new TemplateTag($templatePattern, $tag);
        }

        if(preg_match($templateHookPattern, $tag) === 1 ) {
            return new TemplateHook($templateHookPattern, $tag);
        }

        if(preg_match($templateHookWithArgsPattern, $tag) === 1 ) {
            return new TemplateHookWithArgs($templateHookWithArgsPattern, $tag);
        }

        return false;
    }
}