<?php

declare(strict_types=1);

namespace Crell\Tukio;


use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

trait MakeCompiledProviderTrait
{
    /**
     * Converts a builder into a compiled container.
     *
     * Technically this is the core of every test in this class.  However, the build process
     * is identical in all cases so there's little point in repeating the code.
     *
     * @param ProviderBuilder $builder
     *   A builder, ready to compile.
     * @param ContainerInterface $container
     *   A container to provide to the built provider.
     * @param string $class
     *   The class name of the provider to generate.
     * @param string $namespace
     *   The namespace of the provider to generate.
     * @return ListenerProviderInterface
     *   The compiled provider.
     */
    protected function makeProvider(ProviderBuilder $builder, ContainerInterface $container, string $class, string $namespace) : ListenerProviderInterface
    {
        // Write the generated compiler out to a temp file.
        $filename = tempnam(sys_get_temp_dir(), 'compiled');
        if (!$filename) {
            throw new \RuntimeException('Unable to create temp file for compiled provider.');
        }
        $out = fopen($filename, 'w');
        if (!$out) {
            throw new \RuntimeException('Unable to open file to write compiled provider.');
        }

        try {
            $compiler = new ProviderCompiler();

            $compiler->compile($builder, $out, $class, $namespace);
            fclose($out);

            // Now include it.  If there's a parse error PHP will throw a ParseError and PHPUnit will catch it for us.
            include($filename);

            $compiledClassName = "$namespace\\$class";
            /** @var ListenerProviderInterface $provider */
            $provider = new $compiledClassName($container);
        }
        finally {
            unlink($filename);
        }

        return $provider;
    }

    protected function makeAnonymousProvider(ProviderBuilder $builder, ContainerInterface $container): ListenerProviderInterface
    {
        // Write the generated compiler out to a temp file.
        $filename = tempnam(sys_get_temp_dir(), 'compiled');
        if (!$filename) {
            throw new \RuntimeException('Unable to create temp file for compiled provider.');
        }
        $out = fopen($filename, 'w');
        if (!$out) {
            throw new \RuntimeException('Unable to open file to write compiled provider.');
        }

        try {
            $compiler = new ProviderCompiler();

            $compiler->compileAnonymous($builder, $out);
            fclose($out);

            // Now include it.  If there's a parse error PHP will throw a ParseError and PHPUnit will catch it for us.
            $provider = $compiler->loadAnonymous($filename, $container);
        }
        finally {
            unlink($filename);
        }

        return $provider;
    }
}
