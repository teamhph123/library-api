<?php
/**
 * {CLASS SUMMARY}
 *
 * Date: 7/27/18
 * Time: 10:35 PM
 * @author Michael Munger <michael@highpoweredhelp.com>
 */

namespace Hph\Farret;


abstract class AbstractTagFactory
{
    abstract function getTag($tag);
}