<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { index as indexRolesPermissions } from '@/routes/roles-permissions';
import { index as indexSettings } from '@/routes/settings';
import type { NavItem } from '@/types';

const { t } = useTranslations();
const page = usePage();
const can = computed(() => page.props.auth.can);

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('General settings'),
        href: indexSettings(),
    },
    ...(can.value.rolesView
        ? [
                {
                    title: t('Roles and permissions'),
                    href: indexRolesPermissions(),
                },
            ]
        : []),
]);

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="flex flex-1 px-4 py-6">
        <div class="flex flex-1 flex-col gap-6 lg:flex-row">
            <aside
                class="w-full max-w-xl lg:w-48 lg:border-r lg:border-sidebar-border/70 lg:pr-4"
            >
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    :aria-label="t('Settings')"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-w-0 flex-1">
                <section class="w-full space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
