<?php
declare(strict_types=1);

namespace SWF\PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;

class InstanceDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'i';
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        if (count($args) === 0) {
            return null;
        }

        $type = $scope->getType($args[0]->value);
        if (!$type instanceof ConstantStringType || !$type->isClassStringType()->yes()) {
            return null;
        }

        $objectType = $type->getClassStringObjectType();
        if (!$objectType->hasMethod('getInstance')->yes()) {
            return new ObjectType($type->getValue());
        }

        $method = $objectType->getMethod('getInstance', $scope)->getPrototype();
        if ($method instanceof PhpMethodReflection) {
            foreach ($method->getVariants() as $variant) {
                return $variant->getReturnType();
            }
        }

        return null;
    }
}
