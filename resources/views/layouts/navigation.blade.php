<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <!-- Если авторизован - на дашборд, если гость - на главную -->
                    <a href="{{ auth()->check() ? route('dashboard') : url('/') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-blue-600" />
                    </a>
                </div>

                <!-- Navigation Links (Десктоп) -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            Главная
                        </x-nav-link>
                    @endauth

                    <x-nav-link :href="url('/')" :active="request()->is('/')">Справочник</x-nav-link>

                    @auth
                        @if(auth()->user()->hasRole('advanced_student') || auth()->user()->hasRole('student'))
                            <x-nav-link :href="route('assignments.progress')" :active="request()->routeIs('assignments.progress')">
                                Мой прогресс
                            </x-nav-link>
                        @endif

                        @can('use-tasks')
                            <x-nav-link :href="route('tasks.index')" :active="request()->routeIs('tasks.*')">База задач</x-nav-link>
                            <x-nav-link :href="route('works.index')" :active="request()->routeIs('works.*')">Работы</x-nav-link>
                            <x-nav-link :href="route('tutors.index')" :active="request()->routeIs('tutors.*')">Мои ученики</x-nav-link>
                            <x-nav-link :href="route('tutors.matrix')" :active="request()->routeIs('tutors.matrix')">Сводная матрица</x-nav-link>
                            <x-nav-link :href="route('rewards.journal')" :active="request()->routeIs('rewards.journal')">Журнал наград</x-nav-link>
                            <x-nav-link :href="route('rewards.issued')" :active="request()->routeIs('rewards.issued')">Реестр наград</x-nav-link>
                        @endcan

                        @can('manage-references')
                            <div class="hidden sm:flex sm:items-center">
                                <x-dropdown align="left" width="48">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out h-16">
                                            <div>Справочники</div>
                                            <div class="ms-1">
                                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </div>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('topics.index')">Темы</x-dropdown-link>
                                        <x-dropdown-link :href="route('sources.index')">Источники</x-dropdown-link>
                                        <x-dropdown-link :href="route('groups.index')">Учебные группы</x-dropdown-link>
                                        <x-dropdown-link :href="route('users.index')">Пользователи</x-dropdown-link>
                                        <x-dropdown-link :href="route('substances.index')">Свойства веществ</x-dropdown-link>
                                        <x-dropdown-link :href="route('rewards.index')">Каталог наград</x-dropdown-link>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        @endcan
                    @endauth
                </div>
            </div>

            <!-- Правая часть меню -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <!-- Профиль пользователя -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                                <div class="ms-1"><svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Настройки профиля</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 font-bold">
                                    Выйти
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <!-- Кнопка входа для гостей -->
                    <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-800 transition px-4 py-2 border border-blue-600 rounded-md hover:bg-blue-50">
                        Войти в систему
                    </a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Мобильные) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Главная</x-responsive-nav-link>
            @endauth

            <x-responsive-nav-link :href="url('/')" :active="request()->is('/')">Справочник</x-responsive-nav-link>

            @auth
                @if(auth()->user()->hasRole('advanced_student') || auth()->user()->hasRole('student'))
                    <x-responsive-nav-link :href="route('assignments.progress')" :active="request()->routeIs('assignments.progress')">Мой прогресс</x-responsive-nav-link>
                @endif

                @can('use-tasks')
                    <x-responsive-nav-link :href="route('tasks.index')" :active="request()->routeIs('tasks.*')">База задач</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('works.index')" :active="request()->routeIs('works.*')">Работы</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('tutors.index')" :active="request()->routeIs('tutors.*')">Мои ученики</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('tutors.matrix')" :active="request()->routeIs('tutors.matrix')">Сводная матрица</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('rewards.journal')" :active="request()->routeIs('rewards.journal')">Журнал наград</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('rewards.issued')" :active="request()->routeIs('rewards.issued')">Реестр наград</x-responsive-nav-link>
                @endcan

                @can('manage-references')
                    <div class="border-t border-gray-200 my-2"></div>
                    <div class="px-4 py-2 text-xs text-gray-400 uppercase tracking-widest font-bold">Справочники</div>
                    <x-responsive-nav-link :href="route('topics.index')">Темы</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('sources.index')">Источники</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('groups.index')">Учебные группы</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('users.index')">Пользователи</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('substances.index')">Свойства веществ</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('rewards.index')">Каталог наград</x-responsive-nav-link>
                @endcan
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">Настройки профиля</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 font-bold">
                            Выйти
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('login')" class="text-blue-600 font-bold">
                        Войти в систему
                    </x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>