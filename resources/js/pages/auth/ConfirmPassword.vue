<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { useTranslations } from '@/composables/useTranslations';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Confirm password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
    },
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Confirm password')" />

    <Form
        v-bind="store.form()"
        reset-on-success
        disable-while-processing
        v-slot="{ errors, processing }"
        class="inert:pointer-events-none inert:opacity-70"
    >
        <div class="grid gap-5">
            <FormField
                :label="t('Password')"
                for-id="password"
                :error="errors.password"
                class="[&_label]:text-[0.8125rem] [&_label]:font-semibold [&_label]:text-slate-700"
            >
                <PasswordInput
                    id="password"
                    name="password"
                    class="h-12 rounded-xl border-0 bg-[#f5f5f7] px-4 pr-12 text-base shadow-none ring-1 ring-black/6 transition-shadow placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#0071e3] md:text-base"
                    required
                    autocomplete="current-password"
                    autofocus
                    :placeholder="t('Password')"
                />
            </FormField>

            <SubmitButton
                :processing="processing"
                test-id="confirm-password-button"
                class="h-12 rounded-xl bg-[#0071e3] text-base font-semibold text-white shadow-none hover:bg-[#0077ed] active:scale-[0.99]"
            >
                {{ t('Confirm password') }}
            </SubmitButton>
        </div>
    </Form>
</template>
