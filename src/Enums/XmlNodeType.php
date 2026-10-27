<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Kind of XML node a schema maps to (`Xml.nodeType`, 3.2), replacing `attribute` and `wrapped`.
 */
enum XmlNodeType: string
{
    case Element = 'element';
    case Attribute = 'attribute';
    case Text = 'text';
    case Cdata = 'cdata';
    case None = 'none';
}
