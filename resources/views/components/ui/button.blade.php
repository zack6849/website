<a {{ $attributes->merge(['class' => 'whitespace-no-wrap inline-flex items-center justify-center px-4 py-2 border border-transparent text-base leading-6 font-medium rounded-md focus:outline-none ' . $colorClasses()]) }}>
    {{$slot}}
</a>
