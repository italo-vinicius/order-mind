<?php

namespace App\AI\Tools;

use App\Models\User;
use InvalidArgumentException;

class ToolRegistry
{
    /** @param  iterable<AssistantTool>  $tools */
    public function __construct(private iterable $tools) {}

    /** @return array<int, array<string, mixed>> */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->tools as $tool) {
            $definitions[] = $tool->definition();
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(User $user, string $name, array $arguments): array
    {
        foreach ($this->tools as $tool) {
            if ($tool->name() === $name) {
                return $tool->execute($user, $arguments);
            }
        }

        throw new InvalidArgumentException('Ferramenta não autorizada.');
    }
}
