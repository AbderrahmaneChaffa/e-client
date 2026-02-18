<x-guest-layout>
    <div class="login-container px-8 py-10">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO">
        </div>

        <!-- Title -->
        <h1 class="form-title">Confirm Password</h1>
        <p class="form-subtitle">This is a secure area, please verify your password</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="mt-6">
            @csrf

            <!-- Password -->
            <div class="mb-6">
                <x-input-label for="password" :value="__('Password')" />
                <div class="password-input-wrapper mt-2">
                    <x-text-input id="password" class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition pr-12"
                                    type="password"
                                    name="password"
                                    required autocomplete="current-password" />
                    <span class="password-toggle" onclick="togglePassword('password')" title="Show/Hide Password">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <x-primary-button class="btn-login px-6 py-3 text-white font-semibold rounded-lg">
                    {{ __('Confirm') }}
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
