<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    Inbox,
    LibraryBig,
    LayoutGrid,
    Settings,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import { index as dictionariesIndex } from '@/routes/dictionaries';
import { index as inquiriesIndex } from '@/routes/inquiries';
import { index as rolesIndex } from '@/routes/roles-permissions';
import { index as settingsIndex } from '@/routes/settings';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const { t } = useTranslations();
const page = usePage();

const can = computed(() => page.props.auth.can);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('Dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(can.value.inquiriesView
        ? [
              {
                  title: t('Inquiries'),
                  href: inquiriesIndex(),
                  icon: Inbox,
              },
          ]
        : []),
    ...(can.value.dictionariesView
        ? [
              {
                  title: t('Dictionaries'),
                  href: dictionariesIndex(),
                  icon: LibraryBig,
              },
          ]
        : []),
    ...(can.value.usersView || can.value.rolesView
        ? [
              {
                  title: t('Users'),
                  href: can.value.usersView ? usersIndex() : rolesIndex(),
                  icon: Users,
              },
          ]
        : []),
    ...(can.value.settingsAccess
        ? [
              {
                  title: t('Settings'),
                  href: settingsIndex(),
                  icon: Settings,
              },
          ]
        : []),
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('Repository'),
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: t('Documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
