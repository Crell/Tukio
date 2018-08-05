<?php
declare(strict_types=1);

namespace Crell\Tukio;


class ProviderCompiler
{
    /**
     * @param ProviderBuilder $listeners
     *   The set of listeners to compile.
     * @param resource $stream
     *   A writeable stream to which to write the compiled class.
     * @param string $class
     *   The un-namespaced class name to compile to.
     * @param string $namespace
     *   the namespace for the compiled class.
     */
    public function compile(ProviderBuilder $listeners, $stream, string $class = 'CompiledListenerProvider', string $namespace = '\\Crell\\Tukio\\Compiled') : void
    {
        fwrite($stream, $this->createPreamble($class, $namespace));

        /** @var CompileableListenerEntryInterface $listenerEntry */
        foreach ($listeners as $listenerEntry) {
            $item = $this->createEntry($listenerEntry);
            fwrite($stream, $item);
        }

        fwrite($stream, $this->createClosing());
    }

    protected function createEntry(CompileableListenerEntryInterface $listenerEntry) : string
    {
        return var_export($listenerEntry->getProperties(), true) . ',' . PHP_EOL;
    }

    protected function createPreamble(string $class, string $namespace) : string
    {
        return <<<END
<?php
declare(strict_types=1);

namespace $namespace;

use Crell\Tukio\CompiledListenerProviderBase;
use Psr\Event\Dispatcher\EventInterface;

class $class extends CompiledListenerProviderBase
{
  protected const LISTENERS = [

END;

    }

    protected function createClosing() : string
    {
        return <<<'END'
    ];
}
END;
    }
}
