<?php

// Файл из zuberkz/laravel-kit: composer update перезаписывает его (тег kit-arch). Меняется в пакете,
// правила одного проекта — в соседнем файле tests/Arch/ProjectArchTest.php.

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\Data;
use Symfony\Component\Finder\Finder;

/*
 * Архитектурные правила студии (CLAUDE.md, .claude/rules). Ослаблять их нельзя — исправляйте код.
 */

arch('php preset')->preset()->php();

arch('security preset')->preset()->security();

arch('laravel preset')->preset()->laravel();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('no strict types, everything typed instead')
    ->expect('App')
    ->not->toUseStrictTypes();

arch('enums are string-backed and end with Enum')
    ->expect('App\Enums')
    ->toBeStringBackedEnums()
    ->toHaveSuffix('Enum');

// В Pest нет готового правила для имён вариантов: проверяем рефлексией каждый enum из app/Enums.
test('enum cases are named in UPPER_SNAKE_CASE', function (): void {
    collect(appClassesWithSuffix('App\Enums', 'Enum'))->each(function (string $enum): void {
        collect(new ReflectionEnum($enum)->getCases())
            ->each(fn (ReflectionEnumUnitCase $case) => expect($case->getName())->toMatch('/^[A-Z][A-Z0-9]*(_[A-Z0-9]+)*$/', $enum . '::' . $case->getName()));
    });
})->skip(fn (): bool => appClassesWithSuffix('App\Enums', 'Enum') === [], 'enums appear with the first controlled vocabulary');

arch('no enums outside App\Enums')
    ->expect('App')
    ->not->toBeEnums()
    ->ignoring('App\Enums');

arch('data objects live in App\Data and end with Data')
    ->expect('App\Data')
    ->classes()
    ->toExtend(Data::class)
    ->toHaveSuffix('Data');

arch('no data objects outside App\Data')
    ->expect('App')
    ->classes()
    ->not->toExtend(Data::class)
    ->ignoring('App\Data');

arch('data objects hold data, not dependencies')
    ->expect('App\Data')
    ->not->toUse(['App\Services', 'App\Clients', 'App\Actions', 'App\Repositories', DB::class, Http::class]);

arch('incoming data is laravel-data, not FormRequest')
    ->expect('App')
    ->not->toExtend(FormRequest::class);

arch('services end with Service')
    ->expect('App\Services')
    ->classes()
    ->toHaveSuffix('Service')
    ->toBeFinal()
    ->toBeReadonly();

arch('clients of external APIs end with Client')
    ->expect('App\Clients')
    ->classes()
    ->toHaveSuffix('Client')
    ->toBeFinal()
    ->toBeReadonly();

arch('only clients talk HTTP to external APIs')
    ->expect([Http::class, 'Illuminate\Http\Client', 'GuzzleHttp'])
    ->toOnlyBeUsedIn('App\Clients');

arch('clients are transport only')
    ->expect('App\Clients')
    ->not->toUse(['App\Models', 'App\Services', 'App\Actions', 'App\Repositories', DB::class]);

arch('actions are one scenario with a single handle method')
    ->expect('App\Actions')
    ->classes()
    ->toHaveSuffix('Action')
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveMethod('handle')
    ->not->toHavePublicMethodsBesides(['__construct', 'handle']);

arch('gateways implement a domain contract and end with Gateway')
    ->expect('App\Gateways')
    ->classes()
    ->toHaveSuffix('Gateway')
    ->toBeFinal()
    ->toBeReadonly();

arch('only repositories use the DB facade')
    ->expect(DB::class)
    ->toOnlyBeUsedIn('App\Repositories');

arch('models have no query scopes')
    ->expect('App\Models')
    ->not->toUse(Scope::class);

arch('services never see the HTTP layer')
    ->expect('App\Services')
    ->not->toUse(['App\Http', 'Illuminate\Http\Request']);

arch('repositories are used only by services')
    ->expect('App\Repositories')
    ->toOnlyBeUsedIn('App\Services');

arch('repositories end with Repository')
    ->expect('App\Repositories')
    ->classes()
    ->toHaveSuffix('Repository')
    ->toBeFinal()
    ->toBeReadonly();

arch('support helpers end with Support')
    ->expect('App\Support')
    ->classes()
    ->toHaveSuffix('Support');

arch('contracts are interfaces ending with Interface')
    ->expect('App\Contracts')
    ->toBeInterfaces()
    ->toHaveSuffix('Interface');

arch('no interfaces outside App\Contracts')
    ->expect('App')
    ->not->toBeInterfaces()
    ->ignoring('App\Contracts');

arch('exceptions live in App\Exceptions and end with Exception')
    ->expect('App\Exceptions')
    ->classes()
    ->toHaveSuffix('Exception')
    ->toImplement(Throwable::class);

arch('controllers are thin: no database, no clients, no repositories')
    ->expect('App\Http')
    ->not->toUse([DB::class, 'App\Clients', 'App\Repositories']);

arch('models hold no business logic')
    ->expect('App\Models')
    ->not->toUse(['App\Services', 'App\Clients', 'App\Actions', 'App\Repositories', 'App\Http', Http::class]);

arch('dependencies come through the constructor, not the container')
    ->expect(['app', 'resolve'])
    ->not->toBeUsedIn(['App\Services', 'App\Clients', 'App\Actions', 'App\Repositories', 'App\Gateways', 'App\Data', 'App\Models']);

arch('actions orchestrate services, they do not touch repositories or clients')
    ->expect('App\Actions')
    ->not->toUse(['App\Repositories', 'App\Clients']);

arch('a class over 250 lines does more than one thing')
    ->expect('App')
    ->toHaveLineCountLessThan(250);

/*
 * Суффикс и папка связаны в обе стороны: *Service живёт только в App\Services, *Client — только
 * в App\Clients и так далее, — иначе имя врёт о том, что это за класс.
 */
dataset('layer suffixes', [
    'Service' => ['Service', ['App\Services']],
    'Client' => ['Client', ['App\Clients']],
    'Data' => ['Data', ['App\Data']],
    'Enum' => ['Enum', ['App\Enums']],
    'Support' => ['Support', ['App\Support']],
    'Interface' => ['Interface', ['App\Contracts']],
    'Repository' => ['Repository', ['App\Repositories']],
    'Gateway' => ['Gateway', ['App\Gateways']],
    'Exception' => ['Exception', ['App\Exceptions']],
    'Controller' => ['Controller', ['App\Http\Controllers']],
    'Policy' => ['Policy', ['App\Policies']],
    'Command' => ['Command', ['App\Console\Commands']],
    'Job' => ['Job', ['App\Jobs']],
    'Notification' => ['Notification', ['App\Notifications']],
    // Filament-действия — UI (кнопка + модалка), они живут рядом с ресурсом
    'Action' => ['Action', ['App\Actions', 'App\Filament']],
]);

test('a class with a layer suffix lives in its layer', function (string $suffix, array $namespaces): void {
    $misplaced = collect(appClassesWithSuffix('App', $suffix))
        ->reject(fn (string $class): bool => collect($namespaces)->contains(fn (string $namespace): bool => str_starts_with($class, $namespace . '\\')))
        ->values()
        ->all();

    expect($misplaced)->toBeEmpty();
})->with('layer suffixes');

/*
 * Запросы к базе — только в репозиториях: статический вызов построителя запросов у модели
 * (Order::query(), Order::where(), Order::find()) или локальный scope (scopeActive) вне
 * App\Repositories — нарушение слоя.
 */
test('database queries live only in repositories', function (): void {
    $app = dirname(__DIR__, 2) . '/app';
    $models = collect(Finder::create()->files()->name('*.php')->in($app . '/Models'))
        ->map(fn (SplFileInfo $file): string => $file->getBasename('.php'))
        ->implode('|');
    $queryCall = '/\\b(' . $models . ')::(query|where\\w*|find\\w*|first\\w*|create|insert\\w*|update\\w*|upsert|destroy|all|count|exists|pluck|latest|oldest|with)\\s*\\(/';

    $violations = collect(Finder::create()->files()->name('*.php')->in($app)->notPath('Repositories'))
        ->flatMap(fn (SplFileInfo $file): array => collect(preg_grep($queryCall, file($file->getPathname()) ?: []))
            ->keys()
            ->map(fn (int $line): string => $file->getRelativePathname() . ':' . ($line + 1))
            ->all())
        ->merge(collect(Finder::create()->files()->name('*.php')->in($app . '/Models')->contains('/function scope[A-Z]/'))
            ->map(fn (SplFileInfo $file): string => $file->getRelativePathname() . ': scope'))
        ->values()
        ->all();

    expect($violations)->toBeEmpty();
});

/*
 * В коде приложения бросаются только свои исключения (App\Exceptions): отказ по правилу — наследник
 * DomainException, сбой — своё исключение с понятным сообщением. Голые \Exception, \RuntimeException
 * и т. п. ничего не говорят о причине и не доходят до пользователя понятным текстом.
 */
test('the application throws only its own exceptions', function (): void {
    $genericThrow = '/throw\s+new\s+\\\\?(Exception|RuntimeException|LogicException|InvalidArgumentException|DomainException|UnexpectedValueException)\s*\(/';

    $violations = collect(Finder::create()->files()->name('*.php')->in(dirname(__DIR__, 2) . '/app'))
        ->flatMap(fn (SplFileInfo $file): array => collect(preg_grep($genericThrow, file($file->getPathname()) ?: []))
            ->keys()
            ->map(fn (int $line): string => $file->getRelativePathname() . ':' . ($line + 1))
            ->all())
        ->values()
        ->all();

    expect($violations)->toBeEmpty();
});

/**
 * Классы пространства имён App\… с суффиксом: файлы читаются до загрузки приложения.
 *
 * @return list<string>
 */
function appClassesWithSuffix(string $namespace, string $suffix): array
{
    $directory = rtrim(dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($namespace, strlen('App\\'))), '/');

    if (! is_dir($directory)) {
        return [];
    }

    return collect(Finder::create()->files()->name("*{$suffix}.php")->in($directory))
        ->map(fn (SplFileInfo $file): string => $namespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()))
        ->values()
        ->all();
}
