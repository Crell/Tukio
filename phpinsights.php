<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | This option controls the default preset that will be used by PHP Insights
    | to make your code reliable, simple, and clean. However, you can always
    | adjust the `Metrics` and `Insights` below in this configuration file.
    |
    | Supported: "default", "laravel", "symfony", "magento2", "drupal"
    |
    */

    'preset' => 'default',

    /*
    |--------------------------------------------------------------------------
    | IDE
    |--------------------------------------------------------------------------
    |
    | This options allow to add hyperlinks in your terminal to quickly open
    | files in your favorite IDE while browsing your PhpInsights report.
    |
    | Supported: "textmate", "macvim", "emacs", "sublime", "phpstorm",
    | "atom", "vscode".
    |
    | If you have another IDE that is not in this list but which provide an
    | url-handler, you could fill this config with a pattern like this:
    |
    | myide://open?url=file://%f&line=%l
    |
    */

    'ide' => 'phpstorm',

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may adjust all the various `Insights` that will be used by PHP
    | Insights. You can either add, remove or configure `Insights`. Keep in
    | mind, that all added `Insights` must belong to a specific `Metric`.
    |
    */

    'exclude' => [
        //  'path/to/directory-or-file'
        'benchmarks',
    ],

    'add' => [
        //  ExampleMetric::class => [
        //      ExampleInsight::class,
        //  ]
    ],

    'remove' => [
        // In hindsight I agree with these, but it would be an API break to change. Consider doing that in the next major.
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff::class,
        SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff::class,
        // Public properties on internal classes are fine.
        ObjectCalisthenics\Sniffs\Classes\ForbiddenPublicPropertySniff::class,
        // It's not unusual for an implementing class to not need every optional parameter.
        SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff::class,
        // This is broken, because sometimes you have to late static bind a constant
        // if the constant won't be defined until the child class, which is exactly what we
        // do in the compiled provider.
        SlevomatCodingStandard\Sniffs\Classes\DisallowLateStaticBindingForConstantsSniff::class,
        // There's nothing wrong with the short ternary, WTF?
        SlevomatCodingStandard\Sniffs\ControlStructures\DisallowShortTernaryOperatorSniff::class,
        // Sometimes a swallowing catch really is correct. I'm not sure how to not disable this globally.
        PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\EmptyStatementSniff::class,
        // This is just a stupid rule.
        SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff::class,
        // This is a well-meaning but over-aggressive rule.
        SlevomatCodingStandard\Sniffs\Operators\RequireOnlyStandaloneIncrementAndDecrementOperatorsSniff::class,
        // I don't even know why this exists.
        SlevomatCodingStandard\Sniffs\Commenting\InlineDocCommentDeclarationSniff::class,
        // Most of the time this is good, but OrderedCollection has to support mixed.
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
        // Now you're just being stupid.
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits::class,
        // Oh hells no.  Keep that anal retentive stupidity out of my code base.
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class,
        // I just don't agree with this one.
        PhpCsFixer\Fixer\CastNotation\CastSpacesFixer::class,
        // Or this one.
        PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff::class,
    ],

    'config' => [
        \ObjectCalisthenics\Sniffs\NamingConventions\ElementNameMinimalLengthSniff::class => [
            'minLength' => 3,
            'allowedShortNames' => ['i', 'id', 'to', 'up', 'e'],
        ],
        \ObjectCalisthenics\Sniffs\Metrics\MaxNestingLevelSniff::class => [
            'maxNestingLevel' => 3,
        ],
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class => [
            'lineLimit' => 80,
            'absoluteLineLimit' => 120,
            'ignoreComments' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Requirements
    |--------------------------------------------------------------------------
    |
    | Here you may define a level you want to reach per `Insights` category.
    | When a score is lower than the minimum level defined, then an error
    | code will be returned. This is optional and individually defined.
    |
    */

    'requirements' => [
//        'min-quality' => 0,
//        'min-complexity' => 0,
//        'min-architecture' => 0,
//        'min-style' => 0,
//        'disable-security-check' => false,
    ],

];
