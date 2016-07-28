<?php
/**
 * Created by PhpStorm.
 * User: akent
 * Date: 7/26/2016
 * Time: 1:03 PM
 */

namespace AlanKent\MagentoDev;

/**
 * Exception to throw to make command line display a message and return an exit status.
 */
class MdException extends \Exception
{
    public function __construct($msg, $exitCode)
    {
        parent::__construct($msg, $exitCode);
    }
}