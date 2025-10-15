<?php

namespace oihana\reflect\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of the Json Schema drafts.
 *
 * @see https://json-schema.org/understanding-json-schema/reference/schema#schema
 *
 * @package oihana\reflect\traits
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.4
 */
final class JsonSchemaDraft
{
    use ConstantsTrait ;

    /**
     * The identifier for Draft 4.
     */
    public const string DRAFT_4 = 'http://json-schema.org/draft-04/schema#';

    /**
     * The identifier for Draft 6.
     */
    public const string DRAFT_6 = 'http://json-schema.org/draft-06/schema#';

    /**
     * The identifier for Draft 7.
     */
    public const string DRAFT_7 = 'http://json-schema.org/draft-07/schema#';

    /**
     * The identifier for Draft 2019-09.
     */
    public const string DRAFT_2019_09 = 'https://json-schema.org/draft/2019-09/schema';

    /**
     * The identifier for Draft 2020-12.
     * Therefore most of the time, you'll want this at the root of your schema.
     */
    public const string DRAFT_2020_12 = 'https://json-schema.org/draft/2020-12/schema';
}