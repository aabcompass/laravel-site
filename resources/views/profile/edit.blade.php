<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- ПЕРСОНАЛЬНЫЙ QR-КОД -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg mb-8 flex items-center gap-6">
                <div>
                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate(url('/my_assignments.php?token=' . $user->auth_token)) !!}
                </div>
                <div>
                    <h2 class="text-lg font-medium text-gray-900">Ваш персональный ключ для входа</h2>
                    <p class="mt-1 text-sm text-gray-600">Наведите камеру смартфона на этот QR-код, чтобы автоматически войти на сайт без ввода логина и пароля.</p>
                    <input type="text" readonly value="{{ url('/my_assignments.php?token=' . $user->auth_token) }}" class="mt-3 w-full border-gray-300 bg-gray-50 text-xs rounded-md shadow-sm" onclick="this.select();">
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
