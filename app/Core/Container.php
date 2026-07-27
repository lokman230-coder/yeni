<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Minimal PSR-11 uyumlu Dependency Injection Container.
 * Singleton + factory + otomatik constructor injection.
 */
final class Container
{
    private static ?Container $instance = null;

    /** @var array<string, Closure|string> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $singleton = false): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
        $this->singletons[$abstract] = $singleton;
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
    }

    public function get(string $abstract): mixed
    {
        // Cached instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        $object = $concrete instanceof Closure
            ? $concrete($this)
            : $this->build($concrete);

        if (($this->singletons[$abstract] ?? false) === true) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Bir class'ı reflection ile inşa et — constructor parametrelerini otomatik resolve eder.
     */
    public function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Class '{$class}' bulunamadı.");
        }

        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new RuntimeException("Class '{$class}' instantiable değil.");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            return new $class();
        }

        $params = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $params[] = $this->get($type->getName());
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "'{$class}' için '{$param->getName()}' parametresi resolve edilemedi."
            );
        }

        return $ref->newInstanceArgs($params);
    }

    public function call(callable|array $callback, array $params = []): mixed
    {
        if (is_array($callback)) {
            [$class, $method] = $callback;
            $instance = is_object($class) ? $class : $this->get($class);
            $ref = new \ReflectionMethod($instance, $method);
            $resolved = $this->resolveMethodParams($ref, $params);
            return $ref->invokeArgs($instance, $resolved);
        }

        return $callback(...$params);
    }

    private function resolveMethodParams(\ReflectionMethod $ref, array $given): array
    {
        $out = [];
        foreach ($ref->getParameters() as $i => $param) {
            $name = $param->getName();
            if (array_key_exists($name, $given)) {
                $out[] = $given[$name];
                continue;
            }
            if (array_key_exists($i, $given)) {
                $out[] = $given[$i];
                continue;
            }
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $out[] = $this->get($type->getName());
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $out[] = $param->getDefaultValue();
                continue;
            }
            $out[] = null;
        }
        return $out;
    }
}
