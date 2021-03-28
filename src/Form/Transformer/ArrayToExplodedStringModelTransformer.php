<?php

declare(strict_types=1);

namespace App\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class ArrayToExplodedStringModelTransformer
 *
 * Manage transformation from delimited (e.g. comma separated) string to a simple array.
 *
 * Please not that this class is a model transformer (adapt view data to feed a model).
 */
class ArrayToExplodedStringModelTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private string $delimiter;

    /**
     * ArrayToExplodedStringModelTransformer constructor.
     *
     * @param string $delimiter the delimiter to use when transforming from
     *                          a string to an array and vice-versa
     */
    public function __construct(string $delimiter = ',')
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Get defined delimiter.
     *
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Transform (from model to view) an array into a delimited string.
     *
     * @param array|null $array Array to transform
     *
     * @return string
     *
     * @throws TransformationFailedException when the given value is not an array
     */
    public function transform($array): string
    {
        // Check value type to transform
        if (!\is_array($array)) {
            throw new TransformationFailedException('An array is expected to obtain a string!');
        }
        // Stop transformation by returning an empty string
        if (null === $array) {
            return '';
        }
        // Implode array with defined delimiter
        $string = trim(implode($this->delimiter, $array));

        return $string;
    }

    /**
     * Transform (from view to model) a delimited string into an array.
     *
     * @param string $string String to transform
     *
     * @return array
     *
     * @throws TransformationFailedException when the given value is not a string
     */
    public function reverseTransform($string): array
    {
        // Check value type to reverse-transform
        if (null !== $string && !\is_string($string)) {
            throw new TransformationFailedException('A string is expected to obtain an array!');
        }
        // Stop transformation if values are empty when delimiter is excluded by returning an empty array
        if (null === $string || 0 === \strlen(str_replace($this->delimiter, '',  $string = trim($string)))) {
            return [];
        }
        // Explode string depending on defined delimiter
        $array = explode($this->delimiter, $string);

        return $array;
    }
}