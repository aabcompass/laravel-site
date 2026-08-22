<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Диагностика почты (SMTP)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow sm:rounded-lg border p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Тестирование отправки email</h3>
                
                <p class="text-sm text-gray-600 mb-6">
                    Эта утилита проверяет настройки, указанные в файле <code>.env</code> сервера. <br>
                    Введите ваш email, чтобы отправить тестовое письмо. Если сервер Beget откажет в доступе, ниже появится технический лог ошибки.
                </p>

                <form action="{{ route('system.mail-test.send') }}" method="POST" class="flex gap-4 items-start">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email получателя</label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500" required>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow mt-6 transition">
                        Отправить письмо
                    </button>
                </form>
            </div>

            <!-- Блок вывода результатов (появляется только после отправки) -->
            @if(session('log'))
                <div class="bg-white shadow sm:rounded-lg border overflow-hidden">
                    <div class="px-6 py-3 border-b {{ session('status') == 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                        <h3 class="font-bold {{ session('status') == 'success' ? 'text-green-800' : 'text-red-800' }}">
                            {{ session('status') == 'success' ? 'Успешно' : 'Ошибка отправки' }}
                        </h3>
                    </div>
                    <div class="p-6 bg-gray-900 text-green-400 font-mono text-xs overflow-x-auto whitespace-pre-wrap leading-relaxed shadow-inner">
                        {{ session('log') }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>