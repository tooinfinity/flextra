<script>
    import GuestLayout from '@auth/Layouts/GuestLayout.svelte'
    import InputError from '@auth/Components/InputError.svelte'
    import InputLabel from '@auth/Components/InputLabel.svelte'
    import PrimaryButton from '@auth/Components/PrimaryButton.svelte'
    import TextInput from '@auth/Components/TextInput.svelte'
    import { useForm } from '@inertiajs/svelte'

    let { email, token } = $props()

    const form = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    })

    function submit(e) {
        e.preventDefault()

        $form.post(route('password.store'), {
            onFinish: () => $form.reset('password', 'password_confirmation'),
        })
    }
</script>

<svelte:head>
    <title>Reset Password</title>
</svelte:head>

<GuestLayout>
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

        <div class="mt-4">
            <InputLabel for="password" value="Password" />

            <TextInput
                id="password"
                type="password"
                class="mt-1 block w-full"
                bind:value={$form.password}
                required
                autocomplete="new-password"
            />

            <InputError class="mt-2" message={$form.errors.password} />
        </div>

        <div class="mt-4">
            <InputLabel for="password_confirmation" value="Confirm Password" />

            <TextInput
                id="password_confirmation"
                type="password"
                class="mt-1 block w-full"
                bind:value={$form.password_confirmation}
                required
                autocomplete="new-password"
            />

            <InputError class="mt-2" message={$form.errors.password_confirmation} />
        </div>

        <div class="mt-4 flex items-center justify-end">
            <PrimaryButton class={$form.processing && 'opacity-25'} disabled={$form.processing}
                >Reset Password</PrimaryButton
            >
        </div>
    </form>
</GuestLayout>
