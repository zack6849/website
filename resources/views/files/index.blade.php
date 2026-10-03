<x-base-page title="Files">
    <x-slot name="buttons">
        <span class="inline-flex rounded-md shadow-sm">
            <x-ui.button href="{{route('file.create')}}">
                Upload
            </x-ui.button>
            </span>
    </x-slot>
    <livewire:files.file-index/>
</x-base-page>
