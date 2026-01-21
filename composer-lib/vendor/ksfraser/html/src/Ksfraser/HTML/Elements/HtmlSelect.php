<?php

declare(strict_types=1);

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\HtmlAttributeList;

/**
 * HtmlSelect
 *
 * Represents an HTML <select> element with options.
 *
 * This class provides a type-safe, object-oriented way to create HTML select elements
 * with automatic HTML escaping for security. Supports multiple options, selected states,
 * and standard select attributes (multiple, size, disabled, required).
 *
 * Security:
 * - Automatically HTML-escapes name and attribute values
 * - Safe to use with user-provided data
 *
 * Usage:
 * ```php
 * // Basic select
 * $select = new HtmlSelect('country');
 * $select->addOption(new HtmlOption('ca', 'Canada'));
 * $select->addOption(new HtmlOption('us', 'United States'));
 * echo $select->getHtml();
 *
 * // From array with selected value
 * $select = new HtmlSelect('color');
 * $colors = ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue'];
 * $select->addOptionsFromArray($colors, 'green');
 *
 * // With attributes
 * $select = new HtmlSelect('size');
 * $select->setId('size-selector')
 *        ->setClass('form-control')
 *        ->setRequired(true)
 *        ->addOptionsFromArray(['S' => 'Small', 'M' => 'Medium', 'L' => 'Large']);
 *
 * // Multiple select
 * $select = new HtmlSelect('tags[]');
 * $select->setMultiple(true)
 *        ->setSize(5)
 *        ->addOptionsFromArray(['tag1' => 'Tag 1', 'tag2' => 'Tag 2']);
 * ```
 *
 * @package    Ksfraser\HTML
 * @author     Claude AI Assistant
 * @since      20251020
 * @version    1.0.0
 */
class HtmlSelect implements HtmlElementInterface
{
    /**
     * @var string The select element name
     */
    private string $name;

    /**
     * @var HtmlOption[] Array of option elements
     */
    private array $options;

    /**
     * @var HtmlAttributeList List of HTML attributes
     */
    private HtmlAttributeList $attributes;

    /**
     * Constructor
     *
     * @param string $name The select element name
     *
     * @since 20251020
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->options = [];
        $this->attributes = new HtmlAttributeList(new HtmlAttribute("", ""));
    }

    /**
     * Get the select name
     *
     * @return string The select name
     *
     * @since 20251020
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add an option to the select
     *
     * @param HtmlOption $option Option to add
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function addOption(HtmlOption $option): self
    {
        $this->options[] = $option;
        return $this;
    }

    /**
     * Add multiple options from an associative array
     *
     * @param array<string, string> $data          Key-value pairs (value => label)
     * @param string|null           $selectedValue Optional value to mark as selected
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function addOptionsFromArray(array $data, ?string $selectedValue = null): self
    {
        foreach ($data as $value => $label) {
            $isSelected = ($selectedValue !== null && (string)$value === $selectedValue);
            $this->addOption(new HtmlOption((string)$value, $label, $isSelected));
        }
        return $this;
    }

    /**
     * Get all options
     *
     * @return HtmlOption[] Array of options
     *
     * @since 20251020
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get count of options
     *
     * @return int Number of options
     *
     * @since 20251020
     */
    public function getOptionCount(): int
    {
        return count($this->options);
    }

    /**
     * Set the ID attribute
     *
     * @param string $id Element ID
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setId(string $id): self
    {
        $this->attributes->addAttribute(new HtmlAttribute('id', $id));
        return $this;
    }

    /**
     * Set the class attribute
     *
     * @param string $class CSS class name(s)
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setClass(string $class): self
    {
        $this->attributes->addAttribute(new HtmlAttribute('class', $class));
        return $this;
    }

    /**
     * Set multiple attribute
     *
     * @param bool $multiple Whether to allow multiple selections
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setMultiple(bool $multiple): self
    {
        if ($multiple) {
            $this->attributes->addAttribute(new HtmlAttribute('multiple', 'multiple'));
        }
        return $this;
    }

    /**
     * Set size attribute (number of visible options)
     *
     * @param int $size Number of visible options
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setSize(int $size): self
    {
        $this->attributes->addAttribute(new HtmlAttribute('size', (string)$size));
        return $this;
    }

    /**
     * Set disabled attribute
     *
     * @param bool $disabled Whether the select is disabled
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setDisabled(bool $disabled): self
    {
        if ($disabled) {
            $this->attributes->addAttribute(new HtmlAttribute('disabled', 'disabled'));
        }
        return $this;
    }

    /**
     * Set required attribute
     *
     * @param bool $required Whether the select is required
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setRequired(bool $required): self
    {
        if ($required) {
            $this->attributes->addAttribute(new HtmlAttribute('required', 'required'));
        }
        return $this;
    }

    /**
     * Add a custom attribute
     *
     * @param string $name  Attribute name
     * @param string $value Attribute value
     *
     * @return self For fluent interface
     *
     * @since 20251020
     */
    public function setAttribute(string $name, string $value): self
    {
        $this->attributes->addAttribute(new HtmlAttribute($name, $value));
        return $this;
    }

    /**
     * Generate the HTML for this select element
     *
     * @return string The HTML <select> element with all options
     *
     * @since 20251020
     */
    public function getHtml(): string
    {
        $escapedName = htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');

        $html = '<select name="' . $escapedName . '"';

        // Add custom attributes
        $attributesHtml = $this->attributes->getHtml();
        if ($attributesHtml !== '') {
            $html .= ' ' . $attributesHtml;
        }

        $html .= '>';

        // Add all options
        foreach ($this->options as $option) {
            $html .= $option->getHtml();
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Output the HTML for this select element
     *
     * @return void
     *
     * @since 20251020
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
