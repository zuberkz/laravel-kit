<?php

namespace Zuber\Kit\Modules\Seo\Http\Controllers;

use Illuminate\Http\Response;
use Zuber\Kit\Modules\Seo\Services\RobotsService;

final class RobotsController
{
    public function __invoke(RobotsService $robots): Response
    {
        return new Response($robots->text(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
