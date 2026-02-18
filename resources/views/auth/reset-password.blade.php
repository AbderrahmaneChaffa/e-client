<x-guest-layout>
    <div class="login-container px-8 py-10">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO">
        </div>

        <!-- Title -->
        <h1 class="form-title">Create New Password</h1>
        <p class="form-subtitle">Enter your new password below</p>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div class="mb-5">
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-2 w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mb-5">
                <x-input-label for="password" :value="__('Password')" />
                <div class="password-input-wrapper mt-2">
                    <x-text-input id="password" class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition pr-12" type="password" name="password" required autocomplete="new-password" />
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

            <div class="flex items-center justify-end mt-6">
                <x-primary-button class="btn-login px-6 py-3 text-white font-semibold rounded-lg">
                    {{ __('Reset Password') }}
                </x-primary-button>
            </div>
        </form>
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
