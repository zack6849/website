<div class="bg-emerald-950 px-4 py-6 text-white sm:px-6 lg:px-8">
    <div class="flex justify-between">
        <div class="mx-auto w-full max-w-(--breakpoint-2xl)">  <div>
                Website and Images &copy; Zachary Craig {{ date('Y') }}
            </div>
        </div>
        <div>
            @guest
                <a href="{{url('/login')}}" class="block lg:inline-block lg:mt-0 nav-link mr-4">
                    Login
                </a>
            @endguest
        </div>
    </div>

</div>
