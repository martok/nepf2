<?php
/**
 * Nepf2 Framework - Util
 *
 * @link       https://github.com/martok/nepf2
 * @copyright  Copyright (c) 2023- Martok & Contributors.
 * @license    Apache License
 */

namespace Nepf2\Util;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

class ClassUtil
{
    public static function ImplementsInterface(string $className, string $intfName): bool
    {
        return in_array($intfName, class_implements($className));
    }

    public static function IsClass(string $typeName): bool
    {
        try {
            return !!new \ReflectionClass($typeName);
        } catch (\ReflectionException) {
            return false;
        }
    }

    public static function IsClassOf(string $className, string $typeName): bool
    {
        return is_a($className, $typeName, true);
    }

    public static function GetConst(string $classname, string $field, $default = null): mixed
    {
        $ref = new ReflectionClass($classname);
        return $ref->getConstant($field) ?: $default;
    }

    public static function ParamIsClass(ReflectionParameter $parameter, string $class): bool
    {
        return $parameter->hasType() &&
            ($typ = $parameter->getType()) &&
            ($typ instanceof ReflectionNamedType) &&
            self::IsClass($typ->getName()) &&
            self::IsClassOf($typ->getName(), $class);
    }
}