@props(['id'])

<div>
    <div data-dropdown class="relative">
        <button type="button"
                data-dropdown-toggle
                aria-expanded="false"
                aria-controls="{{ $id }}"
                class="block lg:inline-block nav-link mr-4">
            {{ $activator }}
        </button>
        <div id="{{ $id }}"
             data-dropdown-menu
             hidden
             class="z-50 mt-2 min-w-48 rounded bg-white py-1 text-gray-900 shadow-lg lg:absolute lg:right-0">
            {{ $slot }}
        </div>
    </div>
</div>
