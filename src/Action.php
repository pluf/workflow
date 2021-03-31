<?php
namespace Pluf\Workflow;

interface Action
{

    public static const MAX_WEIGHT = PHP_INT_MAX - 1;

    public static const BEFORE_WEIGHT = 100;

    public static const NORMAL_WEIGHT = 0;

    public static const EXTENSION_WEIGHT = - 10;

    public static const AFTER_WEIGHT = - 100;

    public static const MIN_WEIGHT = PHP_INT_MIN + 1;

    public static const IGNORE_WEIGHT = PHP_INT_MIN;
}

