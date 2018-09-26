<?php
namespace common\components\passwordStore;

/**
 * An exception which indicates that the operation was not attempted for some
 * reason, most likely because not all of the backends were available.
 */
class NotAttemptedException extends \Exception
{
}
