<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;
use UnitEnum;
use BackedEnum;

/**
 * Reflection-only project map generator.
 *
 * Rules that keep this safe to run anywhere (including prod):
 *   - never touches the database — models are only instantiated, never queried;
 *   - every model/resource is wrapped in try/catch, so one bad class cannot
 *     abort the run;
 *   - the only methods ever invoked are (a) relation methods, identified by a
 *     declared Relation return type, and (b) zero-arg public statics declared
 *     in app/ — both inside try/catch.
 */
class GenerateProjectMap extends Command
{
    protected $signature = 'project:map';

    protected $description = 'Generate PROJECT-MAP.md from the codebase via reflection';

    /** @var array<int, string> */
    protected array $out = [];

    public function handle(): int
    {
        $this->out = [];

        $this->writeHeader();
        $this->writeModels();
        $this->writeResources();
        $this->writePolicies();
        $this->writeConventions();

        $path = base_path('PROJECT-MAP.md');
        File::put($path, implode("\n", $this->out) . "\n");

        $this->info("Wrote {$path}");

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────── header

    protected function writeHeader(): void
    {
        $this->push('# COMANTSYS — Project Map (auto-generated)');
        $this->push('');
        $this->push('Generated at: ' . $this->stamp());
        $this->push('');
        $this->push('Do not edit by hand — regenerate with `php artisan project:map`.');
    }

    /**
     * Short git SHA when git is reachable, otherwise the date. Git lives on the
     * host in this setup, so the exec is expected to fail inside the container.
     */
    protected function stamp(): string
    {
        try {
            if (function_exists('exec') && ! in_array('exec', $this->disabledFunctions(), true)) {
                $output = [];
                $code = 1;
                @exec('git rev-parse --short HEAD 2>/dev/null', $output, $code);

                $sha = trim((string) ($output[0] ?? ''));

                if ($code === 0 && $sha !== '') {
                    return "git {$sha}";
                }
            }
        } catch (Throwable) {
            // fall through to the date
        }

        return now()->toDateTimeString() . ' (git unavailable)';
    }

    /** @return array<int, string> */
    protected function disabledFunctions(): array
    {
        return array_map('trim', explode(',', (string) ini_get('disable_functions')));
    }

    // ─────────────────────────────────────────────── 1. models

    protected function writeModels(): void
    {
        $this->section('1. Models');

        foreach ($this->classesIn(app_path('Models'), 'App\\Models\\') as $class) {
            try {
                $this->writeModel($class);
            } catch (Throwable $e) {
                $this->push('### ' . $class);
                $this->push('');
                $this->push('- **Reflection failed:** `' . $this->esc($e->getMessage()) . '`');
                $this->push('');
            }
        }
    }

    protected function writeModel(string $class): void
    {
        $ref = new ReflectionClass($class);

        if (! $ref->isInstantiable() || ! $ref->isSubclassOf(Model::class)) {
            return;
        }

        /** @var Model $model */
        $model = new $class;

        $this->push('### ' . $class);
        $this->push('');
        $this->push('- **Table:** `' . $model->getTable() . '`');
        $this->push('- **Fillable:** ' . $this->codeList($model->getFillable()));

        $casts = [];
        foreach ($model->getCasts() as $attr => $cast) {
            $casts[] = $attr . ' → ' . (is_string($cast) ? $cast : gettype($cast));
        }
        $this->push('- **Casts:** ' . $this->codeList($casts));

        $this->writeRelations($ref, $model);
        $this->writeAccessors($ref);
        $this->writeStaticMaps($ref);

        $this->push('');
    }

    protected function writeRelations(ReflectionClass $ref, Model $model): void
    {
        $rows = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            $type = $method->getReturnType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $returns = $type->getName();

            // Only a declared Relation subclass qualifies — this is the gate
            // that makes invoking the method acceptable.
            if (! is_a($returns, Relation::class, true)) {
                continue;
            }

            $related = '?';

            try {
                $relation = $model->{$method->getName()}();
                $related = $relation instanceof Relation ? get_class($relation->getRelated()) : '?';
            } catch (Throwable) {
                $related = '? (not resolvable without a persisted record)';
            }

            $rows[] = [
                $method->getName() . ($this->isAppMethod($method) ? '' : ' *(inherited)*'),
                class_basename($returns),
                $related,
            ];
        }

        if ($rows === []) {
            $this->push('- **Relations:** —');

            return;
        }

        $this->push('- **Relations:**');
        $this->push('');
        $this->push('  | method | type | related |');
        $this->push('  | --- | --- | --- |');

        foreach ($rows as [$name, $type, $related]) {
            $this->push('  | `' . $this->esc($name) . '` | ' . $this->esc($type) . ' | `' . $this->esc($related) . '` |');
        }

        $this->push('');
    }

    protected function writeAccessors(ReflectionClass $ref): void
    {
        $accessors = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($method->isStatic() || ! Str::is('get*Attribute', $name) || $name === 'getAttribute') {
                continue;
            }

            $attribute = Str::snake(Str::between($name, 'get', 'Attribute'));

            if ($attribute !== '') {
                $accessors[] = $attribute;
            }
        }

        $this->push('- **Accessors:** ' . $this->codeList($accessors));
    }

    /**
     * Zero-arg public statics declared in app/ (statuses(), units(),
     * statusColors(), …). Anything returning a scalar=>scalar map is reported
     * by key; other return shapes are skipped rather than guessed at.
     */
    protected function writeStaticMaps(ReflectionClass $ref): void
    {
        $maps = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
            if ($method->getNumberOfRequiredParameters() > 0 || Str::startsWith($method->getName(), '__')) {
                continue;
            }

            if (! $this->isAppMethod($method)) {
                continue;
            }

            try {
                $value = $method->invoke(null);
            } catch (Throwable) {
                continue;
            }

            if (! is_array($value) || $value === [] || ! $this->isScalarMap($value)) {
                continue;
            }

            $pairs = [];
            foreach ($value as $key => $item) {
                $pairs[] = $key . ' → ' . $item;
            }

            $short = implode(', ', $pairs);

            $maps[] = '  - `' . $method->getName() . '()` — ' . (Str::length($short) <= 160
                ? $this->esc($short)
                : $this->esc(implode(', ', array_map('strval', array_keys($value)))));
        }

        if ($maps === []) {
            $this->push('- **Coded maps:** —');

            return;
        }

        $this->push('- **Coded maps:**');
        foreach ($maps as $map) {
            $this->push($map);
        }
    }

    /** @param array<mixed, mixed> $value */
    protected function isScalarMap(array $value): bool
    {
        if (array_is_list($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            if (! is_scalar($key) || ! is_scalar($item)) {
                return false;
            }
        }

        return true;
    }

    // ─────────────────────────────────────────────── 2. filament resources

    protected function writeResources(): void
    {
        $this->section('2. Filament Resources');

        $files = File::exists(app_path('Filament/Resources'))
            ? File::allFiles(app_path('Filament/Resources'))
            : [];

        $classes = [];

        foreach ($files as $file) {
            if (! Str::endsWith($file->getFilename(), 'Resource.php')) {
                continue;
            }

            $classes[] = $this->classFromPath($file->getPathname());
        }

        sort($classes);

        foreach ($classes as $class) {
            try {
                $this->writeResource($class);
            } catch (Throwable $e) {
                $this->push('### ' . $class);
                $this->push('');
                $this->push('- **Reflection failed:** `' . $this->esc($e->getMessage()) . '`');
                $this->push('');
            }
        }
    }

    protected function writeResource(string $class): void
    {
        if (! class_exists($class)) {
            return;
        }

        $ref = new ReflectionClass($class);

        if ($ref->isAbstract()) {
            return;
        }

        $this->push('### ' . $class);
        $this->push('');
        $this->push('- **Model:** `' . $this->guard(fn () => $class::getModel()) . '`');
        $this->push('- **Navigation group:** ' . $this->guard(fn () => $this->stringify($class::getNavigationGroup())));
        $this->push('- **Navigation label:** ' . $this->guard(fn () => $class::getNavigationLabel()));
        $this->push('- **Navigation sort:** ' . $this->guard(fn () => $class::getNavigationSort() ?? '—'));

        $pages = [];
        try {
            $pages = array_keys($class::getPages());
        } catch (Throwable) {
            $pages = [];
        }

        $this->push('- **Pages:** ' . $this->codeList($pages));

        // infolist() is defined on the base Resource, so method_exists() is
        // useless here — only the declaring class tells us if this resource
        // actually has a read-only view schema.
        $definesInfolist = false;
        try {
            $definesInfolist = $ref->hasMethod('infolist')
                && $ref->getMethod('infolist')->getDeclaringClass()->getName() === $class;
        } catch (Throwable) {
            $definesInfolist = false;
        }

        $this->push('- **Defines `infolist()`:** ' . ($definesInfolist ? 'yes' : 'no')
            . ' · **`view` page:** ' . (in_array('view', $pages, true) ? 'yes' : 'no'));
        $this->push('');
    }

    // ─────────────────────────────────────────────── 3. policies

    protected function writePolicies(): void
    {
        $this->section('3. Policies');

        $dir = app_path('Policies');

        if (! File::exists($dir)) {
            $this->push('- —');

            return;
        }

        $names = collect(File::files($dir))
            ->map(fn ($file) => $file->getFilename())
            ->sort()
            ->values();

        foreach ($names as $name) {
            $this->push('- `app/Policies/' . $name . '`');
        }

        $this->push('');
    }

    // ─────────────────────────────────────────────── 4. conventions

    protected function writeConventions(): void
    {
        $this->section('4. Conventions');

        $this->push('- **Dates:** DB stores Gregorian, UI displays Jalali — `->jalali()` on form DatePickers, `->jalaliDate()` / `->jalaliDateTime()` on TextColumn and TextEntry.');
        $this->push('- **i18n:** UI labels go through `__()` and live in `lang/`; user-entered data is never translated.');
        $this->push('- **Access control:** permissions and roles live in the DB (Filament Shield), not in code — regenerate and assign per environment.');
        $this->push('');
    }

    // ─────────────────────────────────────────────── helpers

    protected function section(string $title): void
    {
        $this->push('');
        $this->push('---');
        $this->push('');
        $this->push('## ' . $title);
        $this->push('');
    }

    /** Buffers one output line. Named push() — line() is taken by Command. */
    protected function push(string $text): void
    {
        $this->out[] = $text;
    }

    /** @return array<int, string> */
    protected function classesIn(string $dir, string $namespace): array
    {
        if (! File::exists($dir)) {
            return [];
        }

        $classes = [];

        foreach (glob(rtrim($dir, '/') . '/*.php') ?: [] as $file) {
            $class = $namespace . basename($file, '.php');

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    protected function classFromPath(string $path): string
    {
        $relative = Str::after($path, app_path() . DIRECTORY_SEPARATOR);

        return 'App\\' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
    }

    protected function isAppMethod(ReflectionMethod $method): bool
    {
        $file = $method->getFileName();

        return is_string($file) && Str::startsWith($file, app_path() . DIRECTORY_SEPARATOR);
    }

    protected function guard(callable $callback): string
    {
        try {
            $value = $callback();
        } catch (Throwable $e) {
            return '_unavailable (' . $this->esc($e->getMessage()) . ')_';
        }

        return $this->esc((string) ($value ?? '—'));
    }

    protected function stringify(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return $value::class . '::' . $value->name . ' (' . $value->value . ')';
        }

        if ($value instanceof UnitEnum) {
            return $value::class . '::' . $value->name;
        }

        return (string) ($value ?? '—');
    }

    /** @param array<int, string> $items */
    protected function codeList(array $items): string
    {
        if ($items === []) {
            return '—';
        }

        return implode(', ', array_map(fn ($item) => '`' . $this->esc((string) $item) . '`', $items));
    }

    protected function esc(string $text): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], $text);
    }
}
