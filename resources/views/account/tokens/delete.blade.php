<div class="bg-white text-black border-gray-700 shadow-xl rounded-2xl p-4">
    <h1 class="text-lg font-weight-bold pb-2">Revoke Token</h1>
    <p>
        Are you sure you want to revoke this token?
    <p class="py-4">
        <b>This action cannot be undone</b>

    <div class="modal-actions">
        <x-ui.button
            @click="$wire.revokeToken(tokenId); showConfirmation = false"
            type="danger"
        >
            Yes, Delete
        </x-ui.button>
        <x-ui.button
            @click="showConfirmation = false"
        >
            Cancel
        </x-ui.button>
    </div>
</div>
