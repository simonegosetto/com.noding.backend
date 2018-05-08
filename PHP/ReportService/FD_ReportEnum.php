<?php

abstract class Enum
{
    var $reflectionClass;

    function __construct()
    {
        $this->reflectionClass = new ReflectionClass($this);
    }

    public function getConst()
    {
        return $this->reflectionClass->getConstants();
    }
}

/////////////////////////////////////////////////////

final class PAGE_SIZE extends Enum
{
    const A3 = "A3";
    const A4 = "A4";
    const A5 = "A5";
    const LETTER = "Letter";
    const LEGAL = "Legal";
}

final class PAGE_UNIT extends Enum
{
    const PT = "pt";
    const MM = "mm";
    const CM = "cm";
    const IN = "in";
}

final class PAGE_ORIENTATION extends Enum
{
    const PORTRAIT = "P";
    const LANDSCAPE = "L";
}

final class FONT_FAMILY extends Enum
{
    const ARIAL = "Arial";
    const COURIER = "Courier";
    const HELVETICA = "Helvetica";
    const TIMES = "Times";
    const SYMBOL = "Symbol";
    const ZAPFDINGBATS = "ZapfDingbats";
}

final class FONT_STYLE extends Enum
{
    const NORMAL = "";
    const BOLD = "B";
    const ITALIC = "I";
    const UNDERLINE = "U";
}
