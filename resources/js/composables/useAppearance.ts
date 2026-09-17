import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const appearance = ref<Appearance>('system');
const systemPrefersDark = ref(false);
let initialized = false;

const isAppearance = (value: unknown): value is Appearance =>
    value === 'light' || value === 'dark' || value === 'system';

const resolvedAppearance = computed<ResolvedAppearance>(() =>
    appearance.value === 'system'
        ? systemPrefersDark.value
            ? 'dark'
            : 'light'
        : appearance.value,
);

function getStoredAppearance(): Appearance {
    try {
        const stored = window.localStorage.getItem('appearance');

        if (isAppearance(stored)) {
            return stored;
        }
    } catch {
        // Restricted storage must not prevent the interface from loading.
    }

    try {
        const cookie = document.cookie
            .split('; ')
            .find((value) => value.startsWith('appearance='))
            ?.slice('appearance='.length);

        if (isAppearance(cookie)) {
            return cookie;
        }
    } catch {
        // The system preference remains available when cookies are blocked.
    }

    return 'system';
}

function setAppearanceCookie(value: Appearance): void {
    try {
        document.cookie = `appearance=${value};path=/;max-age=31536000;SameSite=Lax`;
    } catch {
        // Theme changes still work for this page when persistence is blocked.
    }
}

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    const dark =
        value === 'dark' ||
        (value === 'system' &&
            window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
}

export function initializeTheme(): void {
    if (typeof window === 'undefined' || initialized) {
        return;
    }

    initialized = true;
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    systemPrefersDark.value = mediaQuery.matches;
    appearance.value = getStoredAppearance();
    updateTheme(appearance.value);

    mediaQuery.addEventListener('change', (event) => {
        systemPrefersDark.value = event.matches;
        updateTheme(appearance.value);
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== 'appearance' && event.key !== null) {
            return;
        }

        if (event.newValue !== null && !isAppearance(event.newValue)) {
            return;
        }

        appearance.value = event.newValue ?? 'system';
        setAppearanceCookie(appearance.value);
        updateTheme(appearance.value);
    });
}

function updateAppearance(value: Appearance): void {
    if (!isAppearance(value)) {
        return;
    }

    initializeTheme();
    appearance.value = value;

    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem('appearance', value);
    } catch {
        // A cookie can preserve the preference even if local storage is blocked.
    }

    setAppearanceCookie(value);
    updateTheme(value);
}

export function useAppearance(): UseAppearanceReturn {
    initializeTheme();

    return { appearance, resolvedAppearance, updateAppearance };
}
