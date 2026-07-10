import { usePage } from '@inertiajs/vue3';

type Replacements = Record<string, string | number>;

export function useTranslations() {
    const page = usePage();

    function translate(key: string, replacements: Replacements = {}): string {
        let message = page.props.locale.messages[key] ?? key;

        Object.entries(replacements).forEach(([name, value]) => {
            message = message.replaceAll(`:${name}`, String(value));
        });

        return message;
    }

    return { t: translate };
}
