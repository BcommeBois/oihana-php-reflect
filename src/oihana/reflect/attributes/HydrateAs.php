<?php

namespace oihana\reflect\attributes;

use Attribute;

/**
 * Specifies the class to use when hydrating a property, overriding the declared type hint.
 *
 * This attribute is useful when the property's declared type is too generic (`object`, `array`, `mixed`, or a union like `Interface|Other`)
 * and a specific class should be instantiated during hydration.
 *
 * Used by the `Reflection::hydrate()` method to enforce the correct target class when creating nested objects or arrays of objects.
 *
 * @example Hydrate a property typed as `object`
 * ```php
 * class UserDTO
 * {
 *     public string $name;
 * }
 *
 * class Wrapper
 * {
 *     #[HydrateAs(UserDTO::class)]
 *     public object $payload;
 * }
 *
 * $data = ['payload' => ['name' => 'Alice']];
 * $wrapper = (new Reflection())->hydrate($data, Wrapper::class);
 * echo $wrapper->payload->name; // "Alice"
 * ```
 *
 * @example Hydrate a nullable property with union types
 * ```php
 * class Address
 * {
 *     public string $city;
 * }
 *
 * class User
 * {
 *     #[HydrateAs(Address::class)]
 *     public Address|null $address;
 * }
 *
 * $data = ['address' => ['city' => 'Paris']];
 * $user = (new Reflection())->hydrate($data, User::class);
 * echo $user->address->city; // "Paris"
 * ```
 *
 * @example
 * Hydrate an array of objects
 * ```php
 * class Tag
 * {
 *     public string $label;
 * }
 *
 * class Post
 * {
 *     #[HydrateAs(Tag::class)]
 *     public array $tags;
 * }
 *
 * $data = ['tags' => [['label' => 'PHP'], ['label' => 'Reflections']]];
 * $post = (new Reflection())->hydrate($data, Post::class);
 * echo $post->tags[1]->label; // "Reflections"
 * ```
 *
 * Hydrate a `mixed` property
 * ```php
 * class Meta
 * {
 *     public string $type;
 * }
 *
 * class Envelope
 * {
 *     #[HydrateAs(Meta::class)]
 *     public mixed $meta;
 * }
 *
 * $data =
 * [
 *     'meta' => [ 'type' => 'image' ]
 * ];
 *
 * $envelope = Reflection::hydrate(Envelope::class, $data);
 *
 * echo get_class( $envelope->meta ) . PHP_EOL; // Meta
 * echo $envelope->meta->type . PHP_EOL; // 'image'
 * ```
 *
 * You can also specify multiple possible classes:
 *
 * ```php
 * class ImageMeta extends Meta { public string $url; }
 * class VideoMeta extends Meta { public int $duration; }
 *
 * class Envelope
 * {
 *     #[HydrateAs([ImageMeta::class, VideoMeta::class])]
 *     public mixed $meta;
 * }
 *
 * $data =
 * [
 *     'meta' => [ 'type' => 'video', 'duration' => 42 ]
 * ];
 *
 * $envelope = Reflection::hydrate(Envelope::class, $data);
 *
 * echo get_class($envelope->meta) . PHP_EOL; // VideoMeta
 * echo $envelope->meta->duration . PHP_EOL;  // 42
 * ```
 * When multiple classes are provided, the hydrator automatically
 * chooses the most suitable one based on matching properties.
 *
 * @package oihana\reflect\attributes
 * @author Marc Alcaraz (ekameleon)
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HydrateAs
{
    public function __construct( public string $class ) {}
}