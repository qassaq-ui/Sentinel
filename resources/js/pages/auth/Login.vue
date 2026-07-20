<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('Log in')" />

    <div
        v-if="status"
        class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        disable-while-processing
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5 inert:pointer-events-none inert:opacity-70"
    >
        <div class="grid gap-5">
            <FormField
                :label="t('Email address')"
                forId="email"
                :error="errors.email"
                class="[&_label]:text-[0.8125rem] [&_label]:font-semibold [&_label]:text-slate-700"
            >
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="h-12 rounded-xl border-0 bg-[#f5f5f7] px-4 text-base shadow-none ring-1 ring-black/6 transition-shadow placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#0071e3] md:text-base"
                />
            </FormField>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label
                        for="password"
                        class="text-[0.8125rem] font-semibold text-slate-700"
                    >
                        {{ t('Password') }}
                    </Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-[0.8125rem] font-medium text-[#0071e3] no-underline hover:text-[#0077ed] hover:underline"
                        :tabindex="5"
                    >
                        {{ t('Forgot your password?') }}
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('Password')"
                    class="h-12 rounded-xl border-0 bg-[#f5f5f7] px-4 pr-12 text-base shadow-none ring-1 ring-black/6 transition-shadow placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-[#0071e3] md:text-base"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label
                    for="remember"
                    class="flex cursor-pointer items-center gap-3 text-sm font-normal text-slate-600"
                >
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>{{ t('Remember me') }}</span>
                </Label>
            </div>

            <SubmitButton
                :processing="processing"
                tabindex="4"
                test-id="login-button"
                class="h-12 rounded-xl bg-[#0071e3] text-base font-semibold text-white shadow-none hover:bg-[#0077ed] active:scale-[0.99]"
            >
                {{ t('Log in') }}
            </SubmitButton>
        </div>
    </Form>
</template>
