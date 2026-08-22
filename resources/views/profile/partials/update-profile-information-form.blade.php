<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Информация профиля
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Здесь вы можете обновить свой Email.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- БЛОК ФИО (Только для чтения) -->
        <div>
            <x-input-label value="Фамилия и Имя" />
            <div class="mt-1 block w-full text-gray-900 font-bold bg-gray-50 px-3 py-2 rounded-md border border-gray-200 shadow-sm">
                {{ $user->last_name }} {{ $user->first_name }}
            </div>
            <p class="text-xs text-gray-500 mt-1">Изменение ФИО заблокировано. Если вы нашли ошибку, обратитесь к преподавателю.</p>
        </div>

        <!-- БЛОК EMAIL (Можно менять) -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Сохранить</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 font-bold"
                >Сохранено.</p>
            @endif
        </div>
    </form>
</section>