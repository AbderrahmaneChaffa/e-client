<x-guest-layout>
    <div class="login-container px-8 py-10">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO">
        </div>

        <!-- Title -->
        <h1 class="form-title">Reset Password</h1>
        <p class="form-subtitle">Enter your email to receive a reset link</p>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-5">
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-2 w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:outline-none transition" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <a class="text-sm text-indigo-600 hover:text-indigo-800 hover:underline transition" href="{{ route('login') }}">
                    {{ __('Back to login') }}
                </a>

                <x-primary-button class="btn-login px-6 py-3 text-white font-semibold rounded-lg">
                    {{ __('Send Reset Link') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
