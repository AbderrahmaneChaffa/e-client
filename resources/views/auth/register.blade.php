<x-guest-layout>
    <div class="login-container px-8 py-10">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO">
        </div>

        <!-- Title -->
        <h1 class="form-title">Create Account</h1>
        <p class="form-subtitle">Join us today</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div class="mb-5">
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input id="name" class="block mt-2 w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email Address -->
            <div class="mb-5">
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-2 w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mb-5">
                <x-input-label for="password" :value="__('Password')" />
                <div class="password-input-wrapper mt-2">
                    <x-text-input id="password" class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition pr-12"
                                    type="password"
                                    name="password"
                                    required autocomplete="new-password" />
                    <span class="password-toggle" onclick="togglePassword('password')" title="Show/Hide Password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <div class="password-input-wrapper mt-2">
                    <x-text-input id="password_confirmation" class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition pr-12"
                                    type="password"
                                    name="password_confirmation" required autocomplete="new-password" />
                    <span class="password-toggle" onclick="togglePassword('password_confirmation')" title="Show/Hide Password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <a class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline transition" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="btn-login px-6 py-3 text-white font-semibold rounded-lg">
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </form>

        <!-- Login Link -->
        <div class="mt-8 text-center border-t pt-6">
            <p class="text-gray-600 text-sm">
                Have an account? 
                <a href="{{ route('login') }}" class="text-indigo-600 font-semibold hover:text-indigo-800 transition">
                    Sign in now
                </a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</x-guest-layout>
