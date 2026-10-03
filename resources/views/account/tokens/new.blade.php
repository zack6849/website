<div>
    <h1>New Token</h1>
    <form>
        <div class="py-4">
            <label for="token-name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
            @error('newTokenName')
            <span class="error">{{ $message }}</span>
            @enderror
            <input id="token-name" type="text" wire:model="newTokenName" placeholder="My really cool token name">
        </div>

        <x-ui.button type="success" @click="showNewTokenForm = false; $wire.createToken()">Create Token</x-ui.button>
    </form>
</div>
