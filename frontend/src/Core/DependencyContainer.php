<?php declare(strict_types=1);

namespace PN\Weblight\Core;

class DependencyContainer
{
  private $implementations = [ ], $singletons = [ ], $constructors = [ ];

  public function get(string $classOrInterface)
  {
    if ($classOrInterface === static::class) {
      return $this;
    }

    if (array_key_exists($classOrInterface, $this->implementations)) {
      $class = $this->implementations[$classOrInterface];
    } else {
      $class = $classOrInterface;
    }

    if (array_key_exists($class, $this->constructors)) {
      return $this->constructors[$class]($this);
    }

    $instance = $this->instantiate($class);
    $this->singletons[$class] = $instance;
    return $instance;
  }

  public function instantiate(string $className)
  {
    $class = new \ReflectionClass($className);

    if ($class->isAbstract()) {
      throw new \Exception("Cannot instantiate abstract class {$className}");
    } else if ($class->isInterface()) {
      throw new \Exception("Interface {$className} not satisfied");
    }

    $constructor = $class->getConstructor();
    if ($constructor === null) {
      return new $className();
    }

    $arguments = [ ];
    $parameters = $constructor->getParameters();

    foreach ($parameters as $parameter) {
      $type = $parameter->getType();
      if ($type === null) {
        $arguments[] = null;
      } else {
        try {
          $arguments[] = $this->get($type->getName());
        } catch (\Throwable $err) {
          if ($parameter->allowsNull()) {
            $arguments[] = null;
          } else {
            throw $err;
          }
        }
      }
    }

    return new $className(...$arguments);
  }

  public function provides(string $interface, $classOrConstructor)
  {
    if (is_callable($classOrConstructor)) {
      $this->constructors[$interface] = $classOrConstructor;
    } else if (is_string($classOrConstructor)) {
      $this->implementations[$interface] = $classOrConstructor;
    } else {
      $this->singletons[$interface] = $classOrConstructor;
    }
  }

  public function store($instance)
  {
    $type = get_class($instance);
    $this->singletons[$type] = $interface;
  }

  public function invoke($classOrInstance, string $methodName, ...$extraArgs)
  {
    if (is_string($classOrInstance)) {
      $type = $classOrInstance;
      $instance = $this->get($classOrInstance);
    } else {
      $type = get_class($classOrInstance);
      $instance = $classOrInstance;
    }

    $class = new \ReflectionClass($type);
    $method = $class->getMethod($methodName);

    if (is_object($extraArgs[0] ?? null)) {
      $firstArgType = get_class($extraArgs[0]);
    } else if (count($extraArgs) > 0) {
      $firstArgType = gettype($extraArgs[0]);
    } else {
      $firstArgType = null;
    }

    $arguments = [ ];
    foreach ($method->getParameters() as $parameter) {
      if ( ! $parameter->hasType()) {
        break;
      }

      $type = $parameter->getType()->getName();
      if ($type === $firstArgType) {
        break;
      }

      try {
        $arguments[] = $this->get($type);
      } catch (\Throwable $err) {
        if ($parameter->allowsNull()) {
          $arguments[] = null;
        } else {
          throw $err;
        }
      }
    }
    $arguments = array_merge($arguments, $extraArgs);

    return $method->invoke($instance, ...$arguments);
  }
}
