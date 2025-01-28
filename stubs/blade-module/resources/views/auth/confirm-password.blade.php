<x-{{moduleNameLower}}::guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-{{moduleNameLower}}::input-label for="password" :value="__('Password')" />

            <x-{{moduleNameLower}}::text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-{{moduleNameLower}}::input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-{{moduleNameLower}}::primary-button>
                {{ __('Confirm') }}
            </x-{{moduleNameLower}}::primary-button>
        </div>
    </form>
</x-{{moduleNameLower}}::guest-layout>
