<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, LogOut, Settings, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';

const admin = computed(() => usePage().props.auth.admin);
const permissions = computed<string[]>(
    () => (usePage().props.adminPermissions as string[] | undefined) ?? [],
);
const canManageSettings = computed(
    () =>
        permissions.value.includes('*') ||
        permissions.value.includes('settings.manage'),
);
const { isMobile, state } = useSidebar();

const logout = () => {
    router.flushAll();
    router.post('/admin/logout');
};
</script>

<template>
    <SidebarMenuItem>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <SidebarMenuButton
                    size="lg"
                    class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                >
                    <UserInfo :user="admin" />
                    <ChevronsUpDown class="ml-auto size-4" />
                </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                :side="
                    isMobile
                        ? 'bottom'
                        : state === 'collapsed'
                          ? 'left'
                          : 'bottom'
                "
                align="end"
                :side-offset="4"
            >
                <DropdownMenuLabel class="p-0 font-normal">
                    <div
                        class="flex items-center gap-2 px-1 py-1.5 text-left text-sm"
                    >
                        <UserInfo :user="admin" :show-email="true" />
                    </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                    <DropdownMenuItem :as-child="true">
                        <Link
                            href="/admin/security"
                            class="block w-full cursor-pointer"
                        >
                            <ShieldCheck class="mr-2 size-4" />
                            Security
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="canManageSettings" :as-child="true">
                        <Link
                            href="/admin/settings/general"
                            class="block w-full cursor-pointer"
                        >
                            <Settings class="mr-2 size-4" />
                            Settings
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem class="cursor-pointer" @select="logout">
                    <LogOut class="mr-2 size-4" />
                    Log out
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </SidebarMenuItem>
</template>
