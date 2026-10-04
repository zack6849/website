@extends('layouts.app')
@section('title', $title)

@section('content')
    <x-error-handler></x-error-handler>
    @if(!isset($noheader))
        <header class="bg-white">
            <div class="{{ $maxwidth ?? 'max-w-7xl' }} mx-auto mb-0 pt-3 px-4 sm:px-6 lg:px-8">
                <div class="flex">
                    <div class="grow">
                        <h1 class="text-3xl font-bold leading-tight text-gray-900 mb-0">
                            {{$header ?? $title}}
                        </h1>
                        @if(!empty($subtitle))
                            <span class="text-muted">{{$subtitle}}</span>
                        @endif
                    </div>
                    @if(!empty($buttons))
                        <div>
                            {{ $buttons }}
                        </div>
                    @endif
                </div>
            </div>
        </header>
    @endif
    <main class="bg-white">
        <div class="{{ $maxwidth ?? 'max-w-7xl' }} mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 pb-6 sm:px-0">
                {{ $slot }}
            </div>
        </div>
    </main>
@endsection

@if(isset($vue))
    @push('scripts')
        @vite('resources/js/vue.js')
    @endpush
@endif
