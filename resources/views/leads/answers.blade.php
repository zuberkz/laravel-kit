{{-- Ответы заявки в окне админки. Props: lead — Zuber\Kit\Leads\Models\Lead. --}}
<dl class="grid gap-4">
    @foreach ($lead->answers as $answer)
        <div>
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $answer['label'] }}</dt>
            <dd class="mt-1 whitespace-pre-line text-gray-950 dark:text-white">{{ $answer['value'] }}</dd>
        </div>
    @endforeach
    @if ($lead->url)
        <div>
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Страница') }}</dt>
            <dd class="mt-1"><a href="{{ $lead->url }}" target="_blank" class="text-primary-600 underline">{{ $lead->url }}</a></dd>
        </div>
    @endif
</dl>
