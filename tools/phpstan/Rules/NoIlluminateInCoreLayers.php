<?php

declare(strict_types=1);

namespace Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Use_>
 */
final class NoIlluminateInCoreLayers implements Rule
{
	/**
	 * Returns the node type handled by this rule.
	 */
	public function getNodeType(): string
	{
		return Use_::class;
	}

	/**
	 * Validates that shared core-style folders do not import Illuminate classes.
	 *
	 * @param Use_ $node
	 * @return list<IdentifierRuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$file = str_replace('\\', '/', $scope->getFile());

		if (! preg_match('#/src/[^/]+/(Core|Domain|Application)/#', $file)) {
			return [];
		}

		$errors = [];

		foreach ($node->uses as $use) {
			$name = $use->name->toString();

			if (str_starts_with($name, 'Illuminate\\')) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Core layer must not import "%s". Use a Port interface or infrastructure adapter instead.',
					$name,
				))
					->identifier('architecture.coreLayer.noIlluminateImport')
					->build();
			}
		}

		return $errors;
	}
}
