<script lang="ts">
    import DemoRolePicker from '@/components/app/DemoRolePicker.svelte';
    import { t } from '@/lib/i18n.svelte';
</script>

<div class="space-y-2">
    <p class="text-xs font-medium" style="color: var(--th-text)">
        {t('dashboard.demo_switch_role')}
    </p>
    <DemoRolePicker variant="toggles" />
</div>
