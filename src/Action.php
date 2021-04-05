<?php
namespace Pluf\Workflow;

interface Action
{

    public const MAX_WEIGHT = PHP_INT_MAX - 1;

    public const BEFORE_WEIGHT = 100;

    public const NORMAL_WEIGHT = 0;

    public const EXTENSION_WEIGHT = - 10;

    public const AFTER_WEIGHT = - 100;

    public const MIN_WEIGHT = PHP_INT_MIN + 1;

    public const IGNORE_WEIGHT = PHP_INT_MIN;
}

