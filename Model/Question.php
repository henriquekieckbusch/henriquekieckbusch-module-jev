<?php
/**
 * Copyright © Henrique Kieckbusch. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace HenriqueKieckbusch\Jev\Model;

/**
 * Immutable definition of one Jev question: the code, the human label, the
 * instructions sent to the model and the allowed answers (choice criteria).
 */
class Question
{
    public const ATTRIBUTE_PREFIX = 'jev_';

    /**
     * @param string $code Question code, e.g. "order_risk"
     * @param string $label Human readable label, e.g. "Order Risk"
     * @param string $instructions Question text sent to Jev
     * @param array<string,string|null> $options Allowed answers: option code => short description
     */
    public function __construct(
        private readonly string $code,
        private readonly string $label,
        private readonly string $instructions,
        private readonly array $options
    ) {
    }

    /**
     * Question code, e.g. "order_risk".
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Attribute / column name that stores the answer, e.g. "jev_order_risk".
     *
     * @return string
     */
    public function getAttributeCode(): string
    {
        return self::ATTRIBUTE_PREFIX . $this->code;
    }

    /**
     * Human readable label, e.g. "Order Risk".
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Question text sent to Jev.
     *
     * @return string
     */
    public function getInstructions(): string
    {
        return $this->instructions;
    }

    /**
     * Allowed answers: option code => short description.
     *
     * @return array<string, string|null>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * List the allowed option codes.
     *
     * @return string[]
     */
    public function getOptionCodes(): array
    {
        return array_keys($this->options);
    }

    /**
     * Whether the given option code is allowed.
     *
     * @param string $optionCode
     * @return bool
     */
    public function hasOption(string $optionCode): bool
    {
        return array_key_exists($optionCode, $this->options);
    }

    /**
     * Human label for an option code, e.g. "approve_with_monitoring" => "Approve With Monitoring".
     *
     * @param string $optionCode
     * @return string
     */
    public function getOptionLabel(string $optionCode): string
    {
        return ucwords(str_replace('_', ' ', $optionCode));
    }

    /**
     * Longer explanation of an option, or null when the option name is self-explanatory.
     *
     * @param string $optionCode
     * @return string|null
     */
    public function getOptionDescription(string $optionCode): ?string
    {
        $description = $this->options[$optionCode] ?? null;
        return $description === null || $description === '' ? null : $description;
    }

    /**
     * Question payload in the Typesafe "choice" format.
     *
     * @return array<string, mixed>
     */
    public function toApiPayload(): array
    {
        return [
            'type' => 'choice',
            'instructions' => $this->instructions,
            'criteria' => $this->options,
        ];
    }
}
