<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import CertificateTemplatePreview from '@/components/CertificateTemplatePreview.svelte';
    import type { CertificateTemplateField } from '@/components/CertificateTemplatePreview.svelte';
    import { t } from '@/lib/i18n.svelte';

    type Template = {
        id: number;
        name: string;
        status: string;
        is_default: boolean;
        image_url: string | null;
        width?: number;
        height?: number;
        fields_count: number;
        fields?: CertificateTemplateField[];
    };

    /**
     * Certificate-template card for the supervisor dashboard: a shared preview
     * (background + overlaid variable fields) with the template name and field
     * count. The whole card links to `href` (e.g. the template editor).
     */
    let {
        template,
        href,
    }: {
        template: Template;
        href: string;
    } = $props();
</script>

<Link
    {href}
    class="group flex flex-col overflow-hidden rounded-[20px] bg-white shadow-[8px_8px_48px_8px_rgba(0,0,0,0.08)] transition-all hover:-translate-y-1 hover:shadow-[12px_12px_56px_12px_rgba(0,0,0,0.1)]"
>
    <CertificateTemplatePreview
        imageUrl={template.image_url}
        name={template.name}
        width={template.width}
        height={template.height}
        isDefault={template.is_default}
        status={template.status}
        fields={template.fields}
    />

    <div class="flex flex-col gap-1 p-4">
        <p class="text-start text-[14px] font-medium text-black">
            {template.name}
        </p>
        <p class="text-start text-[12px] text-[#7e7e7e]">
            {t('certificate_templates.fields_count', {
                count: template.fields_count,
            })}
        </p>
    </div>
</Link>
