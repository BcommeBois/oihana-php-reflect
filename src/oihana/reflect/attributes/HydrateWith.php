<?php

namespace oihana\reflect\attributes;

use Attribute;

/**
 * Specifies the class to use when hydrating each element of an array property.
 *
 * This attribute is used by the `Reflection::hydrate()` method to determine the class to instantiate
 * for each element in an array when the property type is simply `array` or the type hint lacks precision.
 *
 * It is particularly useful for collections of objects where the target class cannot be inferred directly
 * from the property type or PHPDoc.
 *
 * @example
 * Hydrate an array of objects
 * ```php
 * class Comment
 * {
 *     public string $text;
 * }
 *
 * class Post
 * {
 *     #[HydrateWith(Comment::class)]
 *     public array $comments;
 * }
 *
 * $data = ['comments' => [['text' => 'Hello'], ['text' => 'World']]];
 * $post = (new Reflection())->hydrate($data, Post::class);
 * echo $post->comments[1]->text; // "World"
 * ```
 *
 * Hydrate an array of DTOs when type is ambiguous
 * ```php
 * class Metric
 * {
 *     public string $label;
 * }
 *
 * class Dashboard
 * {
 *     #[HydrateWith(Metric::class)]
 *     public array $metrics;
 * }
 * ```
 *
 * @package oihana\reflect\attributes
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HydrateWith
{
    public function __construct( ...$classes )
    {
        $this->classes = $classes;
    }

    public array $classes ;
}