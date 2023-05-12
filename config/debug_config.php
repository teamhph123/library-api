<?php
/**
 * @namspace ${NAMESPACE}
 * @name ${NAME}
 * Summary: #$END$#
 *
 * Date: 2022-11-01
 * Time: 12:11 PM
 *
 * @author Michael Munger <mj@hph.io>
 * @copyright (c) 2022 High Powered Help, Inc. All Rights Reserved.
 */

include('config.php');
print_r(json_decode(json_encode(getConfigValues(), true)));
