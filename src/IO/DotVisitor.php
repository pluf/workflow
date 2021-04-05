<?php
namespace Pluf\Workflow\IO;

use Pluf\Workflow\Visitor;

interface DotVisitor extends Visitor
{

    /**
     * Create dot file
     *
     * @param
     *            filename name of dot file
     */
    function convertDotFile(string $filename);
}
