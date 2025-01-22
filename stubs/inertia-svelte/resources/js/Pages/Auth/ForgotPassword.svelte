<script>
    import GuestLayout from '@auth/Layouts/GuestLayout.svelte'
    import InputError from '@auth/Components/InputError.svelte'
    import InputLabel from '@auth/Components/InputLabel.svelte'
    import PrimaryButton from '@auth/Components/PrimaryButton.svelte'
    import TextInput from '@auth/Components/TextInput.svelte'
    import { route } from 'momentum-trail'
    import { useForm } from '@inertiajs/svelte'

    let { status } = $props()

    const form = useForm({
        email: '',
    })

    function submit(e) {
        e.preventDefault()

        $form.post(route('password.email'))
    }
</script>

<svelte:head>
    <title>Forgot Password</title>
</svelte:head>

<GuestLayout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Forgot your password? No problem. Just let us know your email address and we will email you a password reset
        link that will allow you to choose a new one.
    </div>

    {#if status}
        <div class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
            {status}
        </div>
    {/if}

    <form onsubmit={submit}>
        <div>
            <InputLabel for="email" value="Email" />

            <TextInput
                id="email"
                type="email"
                class="mt-1 block w-full"
                bind:value={$form.email}
                required
                autofocus
                autocomplete="username"
            />

            <InputError class="mt-2" message={$form.errors.email} />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <PrimaryButton class={$form.processing && 'opacity-25'} disabled={$form.processing}>
                Email Password Reset Link
            </PrimaryButton>
        </div>
    </form>
</GuestLayout>
