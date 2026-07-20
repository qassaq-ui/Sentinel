<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormField from '@/components/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Reset password')" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        disable-while-processing
        v-slot="{ errors, processing }"
        class="inert:pointer-events-none inert:opacity-70"
    >
        <div class="grid gap-5">
            <FormField
                :label="t('Email')"
                for-id="email"
                :error="errors.email"
                class="[&_label]:text-[0.8125rem] [&_label]:font-semibold [&_label]:text-slate-700"
            >
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    class="h-12 rounded-xl border-0 bg-slate-100 px-4 text-base text-slate-500 shadow-none ring-1 ring-black/6 md:text-base"
                    readonly
                />
            </FormField>

            <FormField
                :label="t('Password')"
                for-id="password"
                :error="errors.password"
                class="[&_label]:text-[0.8125rem] [&_label]:font-semibold [&_label]:text-slate-700"
            >
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    class="h-12 rounded-xl border-0 bg-[#f5f5f7] px-4 pr-12 text-base shadow-none ring-1 ring-black/6 transition-shadow placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#0071e3] md:text-base"
                    autofocus
                    :placeholder="t('Password')"
                    :passwordrules="passwordRules"
                />
            </FormField>

            <FormField
                :label="t('Confirm password')"
                for-id="password_confirmation"
                :error="errors.password_confirmation"
                class="[&_label]:text-[0.8125rem] [&_label]:font-semibold [&_label]:text-slate-700"
            >
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="h-12 rounded-xl border-0 bg-[#f5f5f7] px-4 pr-12 text-base shadow-none ring-1 ring-black/6 transition-shadow placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#0071e3] md:text-base"
                    :placeholder="t('Confirm password')"
                    :passwordrules="passwordRules"
                />
            </FormField>

            <SubmitButton
                :processing="processing"
                test-id="reset-password-button"
                class="h-12 rounded-xl bg-[#0071e3] text-base font-semibold text-white shadow-none hover:bg-[#0077ed] active:scale-[0.99]"
            >
                {{ t('Reset password') }}
            </SubmitButton>
        </div>
    </Form>
</template>
