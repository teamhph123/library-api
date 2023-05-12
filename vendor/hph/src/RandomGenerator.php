<?php
/**
 * Generates random values
 *
 * Date: 9/17/18
 * Time: 2:52 PM
 * @author Michael Munger <mj@hph.io>
 * @copyright (c) 2017-2018 High Powered Help, Inc. All rights reserved.
 */

namespace Hph;


use Exception;

class RandomGenerator
{
    /**
     * Returns a v4 UUID.
     *
     * @return string
     */
    public function uuidv4()
    {
        $arr = array_values(unpack('N1a/n4b/N1c', random_bytes(16)));
        $arr[2] = ($arr[2] & 0x0fff) | 0x4000;
        $arr[3] = ($arr[3] & 0x3fff) | 0x8000;
        return vsprintf('%08x-%04x-%04x-%04x-%04x%08x', $arr);
    }

    /**
     * @return false|string
     * @throws Exception
     * @codeCoverageIgnore
     */
    public function bearerToken() {
        $seed = bin2hex(random_bytes(64));
        return hash_hmac('sha256',$seed, self::uuidv4());
    }
}
