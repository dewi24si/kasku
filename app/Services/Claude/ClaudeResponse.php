<?php

namespace App\Services\Claude;

final readonly class ClaudeResponse
{
    /**
     * @param  array<int, array{type: 'text', text: string}|array{type: 'function_call', name: string, args: array<string, mixed>}>  $parts
     */
    private function __construct(
        public array $parts,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $blocks = $data['content'] ?? [];

        $parts = [];

        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'tool_use') {
                $parts[] = [
                    'type' => 'function_call',
                    'name' => $block['name'],
                    'args' => $block['input'] ?? [],
                ];
            } elseif (($block['type'] ?? null) === 'text') {
                $parts[] = [
                    'type' => 'text',
                    'text' => $block['text'],
                ];
            }
        }

        return new self($parts);
    }

    /**
     * @return array<int, array{type: 'function_call', name: string, args: array<string, mixed>}>
     */
    public function functionCalls(): array
    {
        return array_values(array_filter($this->parts, fn (array $part): bool => $part['type'] === 'function_call'));
    }

    public function hasFunctionCalls(): bool
    {
        return $this->functionCalls() !== [];
    }
}
