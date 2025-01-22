<script>
    let {
        class: className,
        children,
        value,
        ...attrs
    } = $props()
</script>

<label {...attrs} class="block text-sm font-medium text-gray-700 dark:text-gray-300 {className}">
    {#if value}
        {value}
    {:else if children}
        {@render children()}
    {/if}
</label>
