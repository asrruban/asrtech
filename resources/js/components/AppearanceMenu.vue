<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();
const options = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] as const;
const selected = computed(
    () =>
        options.find((option) => option.value === appearance.value) ??
        options[2],
);

function selectAppearance(value: unknown): void {
    const option = options.find((option) => option.value === value);

    if (option) {
        updateAppearance(option.value);
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                class="h-10 px-2.5 sm:px-3"
                :aria-label="`Appearance: ${selected.label}`"
                :title="`Appearance: ${selected.label}`"
            >
                <component
                    :is="selected.icon"
                    class="size-4"
                    aria-hidden="true"
                />
                <span class="hidden sm:inline">Appearance</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuLabel>Appearance</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="selectAppearance"
            >
                <DropdownMenuRadioItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                    class="min-h-10"
                >
                    <component
                        :is="option.icon"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ option.label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
