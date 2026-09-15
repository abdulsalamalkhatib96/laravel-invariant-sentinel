<?php

namespace Evolvex\InvariantSentinel\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class MakeInvariantCommand extends Command
{
    protected $signature = 'make:invariant {name}';
    protected $description = 'Create a Sentinel invariant class';
    public function handle(Filesystem $files): int
    {
        $name = str_replace(['/', '\\\\'], '\\\\', $this->argument('name'));
        $class = class_basename($name);
        $namespace = 'App\\Invariants';
        $path = app_path('Invariants/'.$class.'.php');
        if ($files->exists($path)) { $this->error('Invariant already exists.'); return self::FAILURE; }
        $files->ensureDirectoryExists(dirname($path));
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', preg_replace('/Invariant$/', '', $class)));
        $stub = <<<PHP
<?php

namespace {$namespace};

use Evolvex\\InvariantSentinel\\Invariant;
use Evolvex\\InvariantSentinel\\ValueObjects\\SubjectRef;

final class {$class} extends Invariant
{
    public static function key(): string { return '{$key}'; }
    public function checks(): array { return []; }
    public function resolveSubject(SubjectRef \$subject): mixed { return null; }
}
PHP;
        $files->put($path, $stub."\n");
        $this->info("Created {$path}");
        return self::SUCCESS;
    }
}
