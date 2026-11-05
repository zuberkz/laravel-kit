<?php

namespace Zuber\Kit\Setup;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Zuber\Kit\Exceptions\DomainException;

/**
 * Обработка исключений (bootstrap/app.php → withExceptions) — единственное место, где отказы по
 * бизнес-правилам пишутся в лог и показываются (правило exceptions.md).
 */
final class ExceptionsSetup
{
    public function __invoke(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen($this->wantsJson(...));
        $exceptions->report($this->reportRefusal(...))->stop();
        $exceptions->render($this->renderRefusal(...));
    }

    private function wantsJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Отказ по бизнес-правилу — не ошибка: пишется в канал domain (info), а не в laravel.log.
     */
    private function reportRefusal(DomainException $exception): void
    {
        Log::channel('domain')->info($exception->getMessage(), [
            'exception' => $exception::class,
            ...$exception->context(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * Обычный запрос возвращается назад с сообщением в сессии, JSON получает 422.
     */
    private function renderRefusal(DomainException $exception, Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return back()->withInput()->with('error', $exception->getMessage());
    }
}
