<?php

namespace App\Services\Api\Offline;

use Exception;

/**
 * An offline item points to a student / class added on the phone that has
 * not reached the server yet: the phone sends it again later.
 */
class WaitForEarlierItem extends Exception
{
}
