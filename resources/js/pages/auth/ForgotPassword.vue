<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Forgot password')" />

    <div
        v-if="status"
        class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700"
    >
        {{ status }}
    </div>

    <div class="space-y-5">
        <Form
            v-bind="email.form()"
            disable-while-processing
            v-slot="{ errors, processing }"
            class="grid gap-5 inert:pointer-events-none inert:opacity-70"
        >
            <FormField
                :label="t('Email address')"
                for-id="email"
                :error="errors.email"
                class="[&_label]:text-[0.8125rem] [&_label]:font-semibold [&_label]:text-slate-700"
            >
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    autofocus
                    placeholder="email@example.com"
                    class="h-12 rounded-xl border-0 bg-[#f5f5f7] px-4 text-base shadow-none ring-1 ring-black/6 transition-shadow placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#0071e3] md:text-base"
                />
            </FormField>

            <SubmitButton
                :processing="processing"
                test-id="email-password-reset-link-button"
                class="h-12 rounded-xl bg-[#0071e3] text-base font-semibold text-white shadow-none hover:bg-[#0077ed] active:scale-[0.99]"
            >
                {{ t('Email password reset link') }}
            </SubmitButton>
        </Form>

        <div class="space-x-1 text-center text-sm text-slate-500">
            <span>{{ t('Or, return to') }}</span>
            <TextLink
                :href="login()"
                class="font-medium text-[#0071e3] no-underline hover:underline"
            >
                {{ t('log in') }}
            </TextLink>
        </div>
    </div>
</template>
