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
     * @codeCoverageIgnore
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
     * @param array|null $array an array to transform
     *
     * @return string
     *
     * @throws TransformationFailedException when the given value is not an array
     */
    public function transform($array): string
    {
        // Check value type to transform
        if (null !== $array && !\is_array($array)) {
            throw new TransformationFailedException('An array is expected to obtain a string!');
        }
        // Stop value transformation by returning an empty string
        if (null === $array || empty($array)) {
            return '';
        }
        // Implode array with defined delimiter
        $string = trim(implode($this->delimiter, $array));
        // Get a trimmed and cleaned (by keeping one space after delimiter) delimited string
        $string = preg_replace(
            '/(\s*)' . $this->delimiter . '(\s*)/',
            $this->delimiter . ' ', // keep one space
            $string
        );

        return $string;
    }

    /**
     * Reverse-transform (from view to model) a delimited string into an array.
     *
     * @param string|null $string a string to transform
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
        // Stop transformation if value is equal to "null" by returning an empty array
        if (null === $string) {
            return [];
        }
        // Stop transformation if value is empty when space or delimiter are excluded by returning an empty array
        if (0 === \strlen(preg_replace('/\s|' . $this->delimiter . '/', '', $string))) {
            return [];
        }
        // Explode trimmed and cleaned (by keeping one space after delimiter) string depending on defined delimiter
        $string = preg_replace(
            '/(\s*)' . $this->delimiter . '(\s*)/',
            $this->delimiter . ' ', // keep one space
            $string
        );
        $array = explode($this->delimiter . ' ', trim($string));

        return $array;
    }
}
