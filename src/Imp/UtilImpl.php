<?php
namespace Pluf\Workflow\Imp;

class UtilImpl
{
    
    public static function isReservedKey($key):bool{
       return $key == 'from' ||
       $key == 'to' ||
       $key == 'event' ||
       $key == 'context' ||
       $key == 'stateMachine' ||
       $key == 'position' ||
       $key == 'stateMachineImplementation' ||
        is_numeric($key);
    }
}

