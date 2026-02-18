<x-guest-layout>
    <div class="login-container px-8 py-10">
        <!-- Logo Section -->
        <div class="logo-section">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO">
        </div>

        <!-- Title -->
        <h1 class="form-title">Verify Email</h1>
        <p class="form-subtitle">Please verify your email to continue</p>

        <div class="mb-6 mt-6 p-4 bg-indigo-50 border-2 border-indigo-200 rounded-lg">
            <p class="text-sm text-gray-700">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-6 p-4 bg-green-50 border-2 border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </p>
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 mt-8">
            <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
                @csrf
                <x-primary-button class="btn-login w-full px-6 py-3 text-white font-semibold rounded-lg text-center justify-center">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full px-6 py-3 border-2 border-indigo-600 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
