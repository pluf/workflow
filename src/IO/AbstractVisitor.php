<?php
namespace Pluf\Workflow\IO;

class AbstractVisitor
{

    public string $buffer = '';

    function writeLine(string $msg): void
    {
        $this->buffer .= $msg . "\n";
    }

    function quoteName(string $id): string
    {
        return "\"" . $id . "\"";
    }

    function saveFile($filename, string $content): void
    {
        // try {
        // FileWriter file = new FileWriter(filename);
        // file.write(content);
        // file.close();
        // } catch (IOException e) {
        // e.printStackTrace();
        // }
    }

    /**
     * get enum name instead of toString value
     *
     * @param
     *            enumObj
     * @return
     */
    static function getName($enumObj): string
    {
        // $stateValue;
        // if (enumObj.getClass().isEnum()) {
        // try {
        // stateValue = (String)enumObj.getClass().getMethod("name").invoke(enumObj, null);
        // } catch (Throwable e) {
        // throw new RuntimeException(e);
        // }
        // } else {
        $stateValue = '' . $enumObj;
        // }
        return $stateValue;
    }

    /**
     * quote with enum name value
     *
     * @param
     *            enumObj
     * @return
     */
    function quoteEnumName($enumObj): string
    {
        return $this->quoteName($this->getName($enumObj));
    }
}

