<?php

namespace Zuber\Kit\Modules\Seo\Http\Controllers;

use Illuminate\Http\Response;
use Zuber\Kit\Modules\Seo\Services\LlmsTxtService;

final class LlmsTxtController
{
    public function __invoke(LlmsTxtService $llms): Response
    {
        return new Response($llms->text(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
