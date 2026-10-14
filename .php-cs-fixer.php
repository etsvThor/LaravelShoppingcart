<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use AdamWojs\PhpCsFixerPhpdocForceFQCN\Fixer\Phpdoc\ForceFQCNFixer;

$rules = [
    'array_indentation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => [
        'default' => 'single_space',
    ],
    'blank_line_after_namespace' => true,
    'blank_line_after_opening_tag' => true,
    'blank_line_before_statement' => [
        'statements' => ['return', 'exit'],
    ],

    // Braces related settings.
    'single_space_around_construct' => [
        'constructs_contain_a_single_space' => ['yield_from'],
        'constructs_followed_by_a_single_space' => [
            'abstract', 'as', 'attribute', 'break', 'case', 'catch', 'class', 'clone', 'comment', 'const',
            'const_import', 'continue', 'do', 'echo', 'else', 'elseif', 'enum', 'extends', 'final', 'finally', 'for',
            'foreach', 'function', 'function_import', 'global', 'goto', 'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'match', 'named_argument', 'namespace', 'new', 'open_tag_with_echo',
            'php_doc', 'php_open', 'print', 'private', 'protected', 'public', 'readonly', 'require', 'require_once',
            'return', 'static', 'switch', 'throw', 'trait', 'try', 'type_colon', 'use', 'use_lambda', 'use_trait',
            'var', 'while', 'yield', 'yield_from',
        ],
        'constructs_preceded_by_a_single_space' => ['as', 'use_lambda'],
    ],
    'control_structure_braces' => true,
    'control_structure_continuation_position' => true,
    'declare_parentheses' => true,
    'no_multiple_statements_per_line' => true,
    'braces_position' => [
        'allow_single_line_anonymous_functions' => true,
        'allow_single_line_empty_anonymous_classes' => true,
        'anonymous_classes_opening_brace' => 'same_line',
        'anonymous_functions_opening_brace' => 'same_line',
        'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'control_structures_opening_brace' => 'same_line',
        'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
    ],
    'statement_indentation' => [
        'stick_comment_to_next_continuous_control_statement' => false,
    ],

    'cast_spaces' => true,
    'class_attributes_separation' => [
        'elements' => [
            'const' => 'only_if_meta',
            'method' => 'one',
            'property' => 'one',
            'trait_import' => 'none',
        ],
    ],
    'class_definition' => [
        'multi_line_extends_each_single_line' => true,
        'single_item_single_line' => true,
        'single_line' => true,
    ],
    'concat_space' => [
        'spacing' => 'none',
    ],
    'constant_case' => ['case' => 'lower'],
    'declare_equal_normalize' => true,
    'elseif' => true,
    'encoding' => true,
    'full_opening_tag' => true,
    'fully_qualified_strict_types' => false,
    // added by Shift
    'function_declaration' => true,
    'type_declaration_spaces' => [
        'elements' => [
            'function',
            'property',
        ],
    ],
    'general_phpdoc_tag_rename' => true,
    'heredoc_to_nowdoc' => true,
    'include' => true,
    'increment_style' => ['style' => 'post'],
    'indentation_type' => true,
    'linebreak_after_opening_tag' => true,
    'line_ending' => true,
    'lowercase_cast' => true,
    'lowercase_keywords' => true,
    'lowercase_static_reference' => true,
    'magic_method_casing' => true,
    'magic_constant_casing' => true,
    'method_argument_space' => [
        'on_multiline' => 'ignore',
    ],
    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line',
    ],
    'native_function_casing' => true,
    'no_alias_functions' => true,
    'no_extra_blank_lines' => [
        'tokens' => [
            'extra',
            'throw',
            'use',
            'switch',
            'case',
            'default',
            'attribute',
        ],
    ],
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_closing_tag' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => [
        'use' => 'echo',
    ],
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_short_bool_cast' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_spaces_after_function_name' => true,
    'no_spaces_around_offset' => [
        'positions' => [
            'inside',
            'outside',
        ],
    ],
    'spaces_inside_parentheses' => [
        'space' => 'none',
    ],
    'no_trailing_comma_in_singleline' => [
        'elements' => [
            'arguments',
            'array_destructuring',
            'array',
            'group_import',
        ],
    ],
    'trailing_comma_in_multiline' => [
        'elements' => [
            'arguments',
            'array_destructuring',
            'arrays',
            'match',
            'parameters',
        ],
    ],

    'no_trailing_whitespace' => true,
    'no_trailing_whitespace_in_comment' => true,
    'no_unneeded_control_parentheses' => [
        'statements' => [
            'break',
            'clone',
            'continue',
            'echo_print',
            'return',
            'switch_case',
            'yield',
        ],
    ],
    'no_unreachable_default_argument_value' => true,
    'no_useless_return' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'normalize_index_brace' => true,
    'not_operator_with_successor_space' => true,
    'object_operator_without_whitespace' => true,
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
        'imports_order' => [
            'class',
            'function',
            'const',
        ],
    ],
    'psr_autoloading' => true,
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_package' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_scalar' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => false,
    'phpdoc_to_comment' => false,

    'phpdoc_tag_type' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => true,
    'phpdoc_var_without_name' => true,
    'self_accessor' => true,
    'short_scalar_cast' => true,
    'simplified_null_return' => false,
    'single_blank_line_at_eof' => true,
    'blank_lines_before_namespace' => [
        'min_line_breaks' => 2,
        'max_line_breaks' => 2,
    ],
    'single_class_element_per_statement' => [
        'elements' => [
            'const',
            'property',
        ],
    ],
    'single_import_per_statement' => true,
    'single_line_after_imports' => true,
    'single_line_comment_style' => [
        'comment_types' => ['hash'],
    ],
    'single_quote' => true,
    'space_after_semicolon' => true,
    'standardize_not_equals' => true,
    'switch_case_semicolon_to_colon' => true,
    'switch_case_space' => true,
    'ternary_operator_spaces' => true,
    'trim_array_spaces' => true,
    'types_spaces' => [
        'space' => 'single',
    ],
    'unary_operator_spaces' => true,
    'modifier_keywords' => [
        'elements' => [
            'method',
            'property',
            'const',
        ],
    ],
    'whitespace_after_comma_in_array' => true,
    'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
    'simplified_if_return' => true,
    'method_chaining_indentation' => true,

    'ordered_types' => [
        'case_sensitive' => false,
        'sort_algorithm' => 'none',
        'null_adjustment' => 'always_last',
    ],

    'operator_linebreak' => [
        'only_booleans' => true,
        'position' => 'beginning',
    ],

    'phpdoc_order_by_value' => [
        'annotations' => [
            'covers', 'dataProvider', 'throws', 'uses',
        ],
    ],

    'multiline_comment_opening_closing' => false,

    'assign_null_coalescing_to_coalesce_equal' => true,

    'class_keyword' => false,

    'multiline_promoted_properties' => [
        'keep_blank_lines' => true,
        'minimum_number_of_parameters' => 1,
    ],

    'numeric_literal_separator' => false,

    'ordered_attributes' => [
        'sort_algorithm' => 'alpha',
    ],
    'php_unit_attributes' => true,
    'attribute_empty_parentheses' => true,

    'blank_line_between_import_groups' => true,

    'compact_nullable_type_declaration' => true,

    'long_to_shorthand_operator' => false,

    'single_line_empty_body' => true,

    'single_line_comment_spacing' => true,

    'new_with_parentheses' => [
        'anonymous_class' => false,
        'named_class' => true,
    ],

    'return_type_declaration' => [
        'space_before' => 'none',
    ],

    'no_break_comment' => [
        'comment_text' => 'no break',
    ],
    'no_space_around_double_colon' => true,

    // not enabled, so when some code is commented out for local testing, imports are not removed on auto formatting
    'no_unused_imports' => false,

    // not enabled, as grouping is sometimes better
    'single_trait_insert_per_statement' => false,

    // not enabled, can have unexpected consequences when using raw queries
    'multiline_string_to_heredoc' => false,

    'ordered_class_elements' => [
        'case_sensitive' => false,
        'order' => [
            'use_trait', 'constant', 'case', 'property', 'construct', 'destruct', 'phpunit', 'method', 'magic',
        ],
        'sort_algorithm' => 'none',
    ],

    'AdamWojs/phpdoc_force_fqcn_fixer' => true,
];


$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

/** @var  \PhpCsFixer\ConfigInterface&\PhpCsFixer\ParallelAwareConfigInterface  $config */
$config = (new Config)
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->registerCustomFixers([
        new ForceFQCNFixer(),
    ]);

if (php_uname('s') === 'Darwin' && php_uname('m') === 'arm64') {
    // Probably running apple m1/m2, in which parallel is bugged.
    return $config;
}

return $config->setParallelConfig(ParallelConfigFactory::detect());
