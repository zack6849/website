<div x-data="{
showConfirmation: false,
 showNewTokenForm: false,
 tokenId: 1,
 openDeleteModal(id) {
  this.tokenId = id;
  this.showConfirmation = true;
 },
}">

    @include('components.error-handler')

    <div class="flex justify-between">
        <h1>API Tokens</h1>
        <x-ui.button type="success" @click="showNewTokenForm = true">
            New Token
        </x-ui.button>
    </div>

    <div x-show="showNewTokenForm">
        @include('account.tokens.new')
    </div>

    @if(!$tokens->isEmpty())
        <table class="w-full text-left table-auto min-w-max border-separate border-spacing-y-2">
            <thead>
            <tr>
                <th>Name</th>
                <th>Created At</th>
                <th>Last Used</th>
                <th>Abilities</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach($tokens as $token)
                <tr>
                    <td>{{$token->name}}</td>
                    <td>{{$token->created_at}}</td>
                    <td>{{ $token->last_used_at ?? 'Never'}}</td>
                    <td>{{implode(' ',$token->abilities)}}</td>
                    <td>
                        <x-ui.button @click="openDeleteModal({{$token->id}})">
                            Revoke
                        </x-ui.button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <div x-show="showConfirmation"
         x-transition
         class="fixed inset-0 grid h-screen w-screen place-items-center backdrop-blur-sm bg-blend-darken"
    >
        @include('account.tokens.delete')
    </div>
</div>
